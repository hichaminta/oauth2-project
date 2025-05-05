<?php
header('Content-Type: application/json');

// Vérifier si le jeton d'accès est présent
if (!isset($_GET['access_token'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Jeton d\'accès manquant.']);
    exit;
}

$access_token = $_GET['access_token'];

// Valider le token via SecureAuth
$validation_url = "http://localhost/oauth2-project/server-oauth/validate_token.php?access_token=" . urlencode($access_token);
$response = @file_get_contents($validation_url);

if ($response === FALSE) {
    http_response_code(500);
    echo json_encode(['error' => 'Impossible de vérifier le token.']);
    exit;
}

$data = json_decode($response, true);

if (!isset($data['active']) || !$data['active']) {
    http_response_code(403);
    echo json_encode(['error' => 'Jeton invalide ou expiré.']);
    exit;
}

// Vérifier les scopes
$scopes = explode(' ', $data['scope']);
$user_id = $data['user_id'];

if (!in_array('write', $scopes) && !in_array('admin', $scopes)) {
    http_response_code(403);
    echo json_encode(['error' => 'Scope insuffisant pour téléverser des fichiers.']);
    exit;
}

// Connexion à la base de données
require_once __DIR__ . '/../server-oauth/database.php';

// Traitement du téléversement du fichier
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $file = $_FILES['file'];
    
    // Vérifier les erreurs de téléversement
    if ($file['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['error' => 'Erreur lors du téléversement: ' . $file['error']]);
        exit;
    }
    
    // Vérifier le type MIME et la taille si nécessaire
    $allowed_types = ['application/pdf', 'text/plain', 'image/jpeg', 'image/png'];
    $max_size = 5 * 1024 * 1024; // 5MB
    
    // Vérifier la taille
    if ($file['size'] > $max_size) {
        http_response_code(400);
        echo json_encode(['error' => 'Le fichier est trop volumineux (max 5MB).']);
        exit;
    }
    
    // Générer un nom de fichier sécurisé
    $filename = basename($file['name']);
    $target_path = __DIR__ . '/ressources/' . $filename;
    
    // Vérifier si le fichier existe déjà dans la base de données
    $stmt = $pdo->prepare("SELECT id FROM files WHERE filename = ?");
    $stmt->execute([$filename]);
    $existing_file = $stmt->fetch();
    
    // Déplacer le fichier téléversé
    if (move_uploaded_file($file['tmp_name'], $target_path)) {
        // Ajouter le fichier à la base de données
        if ($existing_file) {
            // Mettre à jour le fichier existant
            $stmt = $pdo->prepare("UPDATE files SET size = ?, path = ?, created_at = NOW() WHERE id = ?");
            $stmt->execute([filesize($target_path), $target_path, $existing_file['id']]);
            $file_id = $existing_file['id'];
        } else {
            // Créer un nouveau fichier
            $stmt = $pdo->prepare("INSERT INTO files (filename, path, size, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$filename, $target_path, filesize($target_path)]);
            $file_id = $pdo->lastInsertId();
            
            // Par défaut, seul l'utilisateur courant a accès au fichier
            $stmt = $pdo->prepare("INSERT INTO file_permissions (file_id, user_id, can_read, can_write) VALUES (?, ?, 1, 1)");
            $stmt->execute([$file_id, $user_id]);
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Fichier téléversé avec succès',
            'file' => [
                'id' => $file_id,
                'name' => $filename,
                'size' => filesize($target_path)
            ]
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Impossible d\'enregistrer le fichier.']);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée ou fichier manquant.']);
}