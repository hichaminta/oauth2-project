<?php
header('Content-Type: application/json');
// Vérifier si le jeton d'accès et l'ID du fichier sont fournis
if (!isset($_GET['access_token']) || !isset($_GET['file_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Jeton ou ID de fichier manquant.']);
    exit;
}

$access_token = $_GET['access_token'];
$file_id = $_GET['file_id'];

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

// Seuls les utilisateurs avec le scope admin peuvent supprimer des fichiers
if (!in_array('admin', $scopes)) {
    http_response_code(403);
    echo json_encode(['error' => 'Scope insuffisant pour supprimer des fichiers.']);
    exit;
}

// Connexion à la base de données
require_once __DIR__ . '/../server-oauth/database.php';

// Vérifier si le fichier existe
$stmt = $pdo->prepare("SELECT * FROM files WHERE id = ?");
$stmt->execute([$file_id]);
$file = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$file) {
    http_response_code(404);
    echo json_encode(['error' => 'Fichier non trouvé.']);
    exit;
}

// Supprimer les permissions du fichier
$stmt = $pdo->prepare("DELETE FROM file_permissions WHERE file_id = ?");
$stmt->execute([$file_id]);

// Supprimer le fichier de la base de données
$stmt = $pdo->prepare("DELETE FROM files WHERE id = ?");
$stmt->execute([$file_id]);

// Supprimer le fichier physique
$file_path = $file['path'];
if (file_exists($file_path) && is_file($file_path)) {
    unlink($file_path);
}

echo json_encode([
    'success' => true,
    'message' => 'Fichier supprimé avec succès.'
]);