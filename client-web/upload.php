<?php
session_start();
if (!isset($_SESSION['access_token'])) {
    header("Location: index.php");
    exit;
}

// Valider le token pour récupérer les infos (notamment le scope et user_id)
$validate_url = "http://localhost/oauth2-project/server-oauth/validate_token.php?access_token=" . $_SESSION['access_token'];
$response = file_get_contents($validate_url);
$token_data = json_decode($response, true);

if (!isset($token_data['active']) || !$token_data['active']) {
    $_SESSION = array();
    session_destroy();
    header("Location: index.php?error=invalid_token");
    exit;
}

$scope = isset($token_data['scope']) ? $token_data['scope'] : '';
$user_id = isset($token_data['user_id']) ? $token_data['user_id'] : 0;

// Vérifier si l'utilisateur a le scope 'write' ou 'admin'
$has_write_scope = strpos($scope, 'write') !== false || strpos($scope, 'admin') !== false;

if (!$has_write_scope) {
    header("Location: view.php?error=insufficient_permissions");
    exit;
}

// Traiter le téléversement du fichier
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    // Connexion à la base de données
    require_once '../server-oauth/database.php';
    
    $file = $_FILES['file'];
    $filename = basename($file['name']);
    $target_dir = "../protected-resources/ressources/";
    $target_file = $target_dir . $filename;
    
    // Vérifier si le fichier existe déjà
    if (file_exists($target_file)) {
        header("Location: view.php?error=file_exists");
        exit;
    }
    
    // Déplacer le fichier
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        // Ajouter l'entrée dans la base de données
        $filesize = filesize($target_file);
        $file_type = pathinfo($target_file, PATHINFO_EXTENSION);
        
        // 1. Insérer le fichier
        $stmt = $pdo->prepare("
            INSERT INTO files (filename, type, size, date_added) 
            VALUES (?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE size = ?, date_added = NOW()
        ");
        $stmt->execute([$filename, $file_type, $filesize, $filesize]);
        
        // Récupérer l'ID du fichier
        $file_id = $pdo->lastInsertId();
        if (!$file_id) {
            // Si le fichier existait déjà, récupérer son ID
            $stmt = $pdo->prepare("SELECT id FROM files WHERE filename = ?");
            $stmt->execute([$filename]);
            $file_id = $stmt->fetchColumn();
        }
        
        // 2. Donner des permissions au propriétaire
        $stmt = $pdo->prepare("
            INSERT INTO files_permissions (file_id, user_id, can_read, can_write, can_delete) 
            VALUES (?, ?, 1, 1, 1)
            ON DUPLICATE KEY UPDATE can_read = 1, can_write = 1, can_delete = 1
        ");
        $stmt->execute([$file_id, $user_id]);
        
        header("Location: view.php?success=file_uploaded");
        exit;
    } else {
        header("Location: view.php?error=upload_failed");
        exit;
    }
} else {
    header("Location: view.php");
    exit;
}
?>