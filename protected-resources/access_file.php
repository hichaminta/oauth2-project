<?php
header('Content-Type: application/json');

// Vérifier si le jeton d'accès et le nom de fichier sont fournis
if (!isset($_GET['access_token']) || !isset($_GET['file'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Jeton ou fichier manquant.']);
    exit;
}

$access_token = $_GET['access_token'];
$filename = $_GET['file'];

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

// Vérifier les scopes et permissions
$scopes = explode(' ', $data['scope']);
$user_id = $data['user_id'];

// Connexion à la base de données
require_once __DIR__ . '/database.php';

// Vérifier si l'utilisateur a accès à ce fichier spécifique
$query = "SELECT f.* FROM files f
          INNER JOIN file_permissions fp ON f.id = fp.file_id
          WHERE f.filename = ? AND fp.user_id = ? AND fp.can_read = 1";

// Si l'utilisateur a le scope admin, il peut accéder à tous les fichiers
if (in_array('admin', $scopes)) {
    $query = "SELECT * FROM files WHERE filename = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$filename]);
} else if (!in_array('read', $scopes)) {
    http_response_code(403);
    echo json_encode(['error' => 'Scope insuffisant pour accéder au fichier.']);
    exit;
} else {
    $stmt = $pdo->prepare($query);
    $stmt->execute([$filename, $user_id]);
}

$file = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$file) {
    http_response_code(404);
    echo json_encode(['error' => 'Fichier non trouvé ou accès non autorisé.']);
    exit;
}

// Préparer le chemin du fichier
$file_path = $file['path'];

// Vérifier l'existence du fichier
if (!file_exists($file_path) || !is_file($file_path)) {
    http_response_code(404);
    echo json_encode(['error' => 'Fichier physique non trouvé.']);
    exit;
}

// Envoyer le fichier
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
header('Content-Length: ' . filesize($file_path));

// Nettoyer le tampon avant l'envoi
ob_clean();
flush();
readfile($file_path);
exit;