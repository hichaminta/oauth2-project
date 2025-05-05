<?php
header('Content-Type: application/json');

// Vérifier si le jeton d'accès et le nom de fichier sont fournis
if (!isset($_GET['access_token']) || !isset($_GET['file'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Jeton ou fichier manquant.']);
    exit;
}

$access_token = $_GET['access_token'];
$file = $_GET['file'];

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

// Récupérer l'ID de l'utilisateur et les scopes
$user_id = $data['user_id'];
$scopes = explode(' ', $data['scope']);

// Connecter à la base de données pour vérifier les permissions
require_once __DIR__ . '/../server-oauth/database.php';

// Vérifier si l'utilisateur a accès à ce fichier spécifique
$has_permission = false;

// Les administrateurs ont accès à tous les fichiers
if (in_array('admin', $scopes)) {
    $has_permission = true;
} else {
    // Vérifier si l'utilisateur a une permission explicite
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM files_permissions WHERE user_id = ? AND file_name = ?");
    $stmt->execute([$user_id, basename($file)]);
    $count = $stmt->fetchColumn();
    $has_permission = ($count > 0);
}

if (!$has_permission) {
    http_response_code(403);
    echo json_encode(['error' => 'Vous n\'avez pas accès à ce fichier.']);
    exit;
}

// Préparer le chemin du fichier
$directory = __DIR__ . '/ressources/';
$file_path = $directory . basename($file);

// Vérifier l'existence du fichier
if (!file_exists($file_path) || !is_file($file_path)) {
    http_response_code(404);
    echo json_encode(['error' => 'Fichier non trouvé.']);
    exit;
}

// Envoyer le fichier
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($file) . '"');
header('Content-Length: ' . filesize($file_path));

// Nettoyer le tampon avant l'envoi
ob_clean();
flush();
readfile($file_path);
exit;