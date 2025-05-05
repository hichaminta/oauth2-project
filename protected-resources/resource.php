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

// Vérifier les scopes
$scopes = explode(' ', $data['scope']);
$user_id = $data['user_id'];

if (!in_array('read', $scopes) && !in_array('admin', $scopes)) {
    http_response_code(403);
    echo json_encode(['error' => 'Scope insuffisant pour accéder aux fichiers.']);
    exit;
}

// Connexion à la base de données
require_once __DIR__ . '/../server-oauth/database.php';

// Récupérer les fichiers auxquels l'utilisateur a accès
$query = "SELECT f.* FROM files f
          INNER JOIN file_permissions fp ON f.id = fp.file_id
          WHERE fp.user_id = ? AND fp.can_read = 1";

// Si l'utilisateur a le scope admin, il peut voir tous les fichiers
if (in_array('admin', $scopes)) {
    $query = "SELECT * FROM files";
    $stmt = $pdo->prepare($query);
    $stmt->execute();
} else {
    $stmt = $pdo->prepare($query);
    $stmt->execute([$user_id]);
}

$files = $stmt->fetchAll(PDO::FETCH_ASSOC);

$file_data = [];
foreach ($files as $file) {
    $file_data[] = [
        'id' => $file['id'],
        'name' => $file['filename'],
        'size' => $file['size'],
        'created_at' => $file['created_at'],
        'url' => "http://localhost/oauth2-project/protected-resources/access_file.php?access_token=" . urlencode($access_token) . "&file=" . urlencode($file['filename'])
    ];
}

// L'utilisateur peut-il ajouter des fichiers?
$can_write = in_array('write', $scopes) || in_array('admin', $scopes);

echo json_encode([
    'files' => $file_data,
    'user' => [
        'id' => $user_id,
        'scopes' => $scopes,
        'can_write' => $can_write
    ]
]);