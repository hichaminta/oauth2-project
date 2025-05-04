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
    if (is_file($path)) {
        $file_data[] = [
            'name' => $file,
            'size' => filesize($path),
            'url'  => "http://localhost/oauth2-project/protected-resources/access_file.php?access_token=" . urlencode($access_token) . "&file=" . urlencode($file)
        ];
    }
}

echo json_encode(['files' => $file_data]);
