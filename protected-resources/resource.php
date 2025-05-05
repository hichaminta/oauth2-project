<?php
header('Content-Type: application/json');

// Vérifier si le jeton d'accès est présent
if (!isset($_GET['access_token'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Jeton d\'accès manquant.']);
    exit;
}

$access_token = $_GET['access_token'];

// Appeler le serveur OAuth pour valider le token
$validation_url = "http://localhost/oauth2-project/server-oauth/validate_token.php?access_token=" . urlencode($access_token);
$response = @file_get_contents($validation_url);

if ($response === FALSE) {
    http_response_code(500);
    echo json_encode(['error' => 'Impossible de vérifier le token.']);
    exit;
}

$data = json_decode($response, true);

// Vérifier si le token est actif
if (!isset($data['active']) || !$data['active']) {
    http_response_code(403);
    echo json_encode(['error' => 'Jeton invalide ou expiré.']);
    exit;
}

// Vérifier le scope minimal requis (read)
$scopes = explode(' ', $data['scope']);
if (!in_array('read', $scopes) && !in_array('admin', $scopes)) {
    http_response_code(403);
    echo json_encode(['error' => 'Permissions insuffisantes. Le scope "read" est requis.']);
    exit;
}

// Récupérer l'ID de l'utilisateur
$user_id = $data['user_id'];

// Connecter à la base de données pour vérifier les permissions
require_once __DIR__ . '/../server-oauth/database.php';

// Récupérer les fichiers auxquels l'utilisateur a accès
if (in_array('admin', $scopes)) {
    // Les administrateurs ont accès à tous les fichiers
    $stmt = $pdo->query("SELECT DISTINCT file_name FROM files_permissions");
    $permitted_files = $stmt->fetchAll(PDO::FETCH_COLUMN);
} else {
    // Les utilisateurs normaux n'ont accès qu'aux fichiers autorisés
    $stmt = $pdo->prepare("SELECT file_name FROM files_permissions WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $permitted_files = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// Lister les fichiers disponibles
$directory = __DIR__ . '/ressources/';
if (!is_dir($directory)) {
    http_response_code(500);
    echo json_encode(['error' => 'Le dossier ressources/ est introuvable.']);
    exit;
}

$files = scandir($directory);
$files = array_diff($files, ['.', '..']);

$file_data = [];
foreach ($files as $file) {
    $path = $directory . $file;
    if (is_file($path) && (in_array($file, $permitted_files) || in_array('admin', $scopes))) {
        $file_data[] = [
            'name' => $file,
            'size' => filesize($path),
            'url'  => "http://localhost/oauth2-project/protected-resources/access_file.php?access_token=" . urlencode($access_token) . "&file=" . urlencode($file)
        ];
    }
}

// Inclure une indication si l'utilisateur peut ajouter des fichiers
$can_upload = in_array('write', $scopes) || in_array('admin', $scopes);

echo json_encode([
    'files' => $file_data,
    'can_upload' => $can_upload
]);