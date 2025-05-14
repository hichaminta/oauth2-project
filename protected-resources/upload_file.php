<?php
header('Content-Type: application/json');

// Fonction de journalisation des accès
include_once 'fonction.php' ;

// Vérifier si le jeton d'accès est présent
if (!isset($_GET['access_token'])) {
    $message = 'Jeton d\'accès manquant.';
    logAccess(null, null, null, false, $message,action:'upload');
    http_response_code(401);
    echo json_encode(['error' => $message]);
    exit;
}

$access_token = $_GET['access_token'];

// Valider le token via SecureAuth
$validation_url = "http://localhost/oauth2-project/server-oauth/validate_token.php?access_token=" . urlencode($access_token);
$response = @file_get_contents($validation_url);

if ($response === FALSE) {
    $message = 'Impossible de vérifier le token.';
    logAccess(null, null, null, false, $message,action:'upload');
    http_response_code(500);
    echo json_encode(['error' => $message]);
    exit;
}

$data = json_decode($response, true);

if (!isset($data['active']) || !$data['active']) {
    $message = 'Jeton invalide ou expiré.';
    logAccess(null, null, null, false, $message,action:'upload');
    http_response_code(403);
    echo json_encode(['error' => $message]);
    exit;
}

// Vérifier les scopes
$scopes = explode(' ', $data['scope']);
$user_id = $data['user_id'];

if (!in_array('write', $scopes) && !in_array('admin', $scopes)) {
    $message = 'Scope insuffisant pour téléverser des fichiers.';
    logAccess($user_id, null, null, false, $message,action:'upload');
    http_response_code(403);
    echo json_encode(['error' => $message]);
    exit;
}

// Connexion à la base de données
require_once __DIR__ . '/../server-oauth/database.php';

// Traitement du téléversement du fichier
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $file = $_FILES['file'];
    $filename = basename($file['name']);
    
    // Journaliser le début de la tentative d'upload
    logAccess($user_id, null, $filename, true, 'Tentative de téléversement', 'upload_start');
    
    // Vérifier les erreurs de téléversement
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error_messages = [
            UPLOAD_ERR_INI_SIZE => 'Le fichier dépasse la taille maximale définie dans php.ini',
            UPLOAD_ERR_FORM_SIZE => 'Le fichier dépasse la taille maximale spécifiée dans le formulaire',
            UPLOAD_ERR_PARTIAL => 'Le fichier n\'a été que partiellement téléversé',
            UPLOAD_ERR_NO_FILE => 'Aucun fichier n\'a été téléversé',
            UPLOAD_ERR_NO_TMP_DIR => 'Dossier temporaire manquant',
            UPLOAD_ERR_CANT_WRITE => 'Échec de l\'écriture du fichier sur le disque',
            UPLOAD_ERR_EXTENSION => 'Une extension PHP a arrêté le téléversement'
        ];
        
        $error_code = $file['error'];
        $error_message = isset($error_messages[$error_code]) ? 
                         $error_messages[$error_code] : 
                         'Erreur inconnue: ' . $error_code;
        
        $message = 'Erreur lors du téléversement: ' . $error_message;
        logAccess($user_id, null, $filename, false, $message,action:'upload');
        http_response_code(400);
        echo json_encode(['error' => $message]);
        exit;
    }
    
    // Vérifier le type MIME et la taille si nécessaire
    $allowed_types = ['application/pdf', 'text/plain', 'image/jpeg', 'image/png'];
    $max_size = 5 * 1024 * 1024; // 5MB
    
    // Vérifier la taille
    if ($file['size'] > $max_size) {
        $message = 'Le fichier est trop volumineux (max 5MB).';
        logAccess($user_id, null, $filename, false, $message,action:'upload');
        http_response_code(400);
        echo json_encode(['error' => $message]);
        exit;
    }
    
    // Générer un nom de fichier sécurisé
    $target_path = __DIR__ . '/ressources/' . $filename;
    
    // Vérifier si le fichier existe déjà dans la base de données
    $stmt = $pdo->prepare("SELECT id FROM files WHERE filename = ?");
    $stmt->execute([$filename]);
    $existing_file = $stmt->fetch();
    $is_update = $existing_file ? true : false;
    
    // Déplacer le fichier téléversé
    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        // Ajouter le fichier à la base de données
        if ($is_update) {
            // Mettre à jour le fichier existant
            $file_id = $existing_file['id'];
            $stmt = $pdo->prepare("UPDATE files SET size = ?, path = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([filesize($target_path), $target_path, $file_id]);
            
            $message = 'Mise à jour du fichier existant';
            logAccess($user_id, $file_id, $filename, true, $message, 'upload_update');
        } else {
            // Créer un nouveau fichier
            $stmt = $pdo->prepare("INSERT INTO files (filename, path, size, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$filename, $target_path, filesize($target_path)]);
            $file_id = $pdo->lastInsertId();
            
            // Par défaut, seul l'utilisateur courant a accès au fichier
            $stmt = $pdo->prepare("INSERT INTO file_permissions (file_id, user_id, can_read, can_write) VALUES (?, ?, 1, 1)");
            $stmt->execute([$file_id, $user_id]);
            
            $message = 'Création d\'un nouveau fichier';
            logAccess($user_id, $file_id, $filename, true, $message, 'upload_new');
        }
        
        $final_message = 'Fichier téléversé avec succès';
        logAccess($user_id, $file_id, $filename, true, $final_message, 'upload_complete');
          $upload_file = [
            'timestamp' => time(),
            'user_id' => $user_id,
            'file_id' => $file_id,
            'filename' => $filename,
            'action' => 'Telechargement',
            'success' => true,
            'message' => 'Téléchargement du fichier réussi',
            'ip_address' => $_SERVER['REMOTE_ADDR'],
            'access_token' => $access_token,
            'scope' => $data['scope'] ?? ''
        ];
        publishToBlockchain('upload_file_log', $upload_file);
        echo json_encode([
            'success' => true,
            'message' => $final_message,
            'file' => [
                'id' => $file_id,
                'name' => $filename,
                'size' => filesize($target_path),
                'is_update' => $is_update
            ]
        ]);
    } else {
        $message = 'Impossible d\'enregistrer le fichier.';
        logAccess($user_id, null, $filename, false, $message,action:'upload');
        http_response_code(500);
        echo json_encode(['error' => $message]);
    }
} else {
    $message = 'Méthode non autorisée ou fichier manquant.';
    logAccess($user_id, null, null, false, $message,action:'upload');
    http_response_code(405);
    echo json_encode(['error' => $message]);
}