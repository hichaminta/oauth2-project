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

// Vérifier si l'utilisateur a le scope 'write' ou 'admin'
$scopes = explode(' ', $data['scope']);
if (!in_array('write', $scopes) && !in_array('admin', $scopes)) {
    http_response_code(403);
    echo json_encode(['error' => 'Permissions insuffisantes. Le scope "write" est requis.']);
    exit;
}

// Vérifier si un fichier a été téléchargé
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    $error_message = isset($_FILES['file']) ? 'Erreur lors du téléchargement: ' . $_FILES['file']['error'] : 'Aucun fichier envoyé.';
    http_response_code(400);
    echo json_encode(['error' => $error_message]);
    exit;
}

// Vérifier la taille du fichier (max 5 MB)
if ($_FILES['file']['size'] > 5 * 1024 * 1024) {
    http_response_code(400);
    echo json_encode(['error' => 'Le fichier est trop volumineux (max 5 MB).']);
    exit;
}

// Récupérer l'ID de l'utilisateur
$user_id = $data['user_id'];

// Nettoyer le nom du fichier
$filename = basename($_FILES['file']['name']);
$target_file = __DIR__ . '/ressources/' . $filename;

// Vérifier si le fichier existe déjà
if (file_exists($target_file)) {
    http_response_code(409);
    echo json_encode(['error' => 'Le fichier existe déjà.']);
    exit;
}

// Déplacer le fichier téléchargé
if (move_uploaded_file($_FILES['file']['tmp_name'], $target_file)) {
    // Ajouter la permission pour ce fichier dans la base de données
    require_once __DIR__ . '/../server-oauth/database.php';
    
    // L'utilisateur qui a téléchargé le fichier aura toujours accès à celui-ci
    $stmt = $pdo->prepare("INSERT INTO files_permissions (file_name, user_id, access_type) VALUES (?, ?, 'read')");
    $stmt->execute([$filename, $user_id]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Fichier téléchargé avec succès.',
        'file' => [
            'name' => $filename,
            'size' => $_FILES['file']['size']
        ]
    ]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Échec du téléchargement du fichier.']);
}

