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

// Récupérer l'ID utilisateur et le scope du token
$user_id = $data['user_id'];
$scope = $data['scope'];

// Initialiser la connexion à la base de données
require_once '../server-oauth/database.php';

// Vérifier si l'utilisateur a le scope 'read' ou 'admin'
$has_read_scope = strpos($scope, 'read') !== false;
$has_admin_scope = strpos($scope, 'admin') !== false;

if (!$has_read_scope && !$has_admin_scope) {
    http_response_code(403);
    echo json_encode(['error' => 'Permissions insuffisantes. Le scope "read" est requis.']);
    exit;
}

// Vérifier si l'utilisateur a accès au fichier
if ($has_admin_scope) {
    // Les admins ont accès à tous les fichiers
    $has_access = true;
} else {
    // Vérifier les permissions spécifiques pour ce fichier
    $stmt = $pdo->prepare("
        SELECT fp.can_read
        FROM files f
        JOIN files_permissions fp ON f.id = fp.file_id
        WHERE f.filename = ? AND fp.user_id = ? AND fp.can_read = 1
    ");
    $stmt->execute([$file, $user_id]);
    $has_access = $stmt->rowCount() > 0;
}

if (!$has_access) {
    http_response_code(403);
    echo json_encode(['error' => 'Accès refusé à ce fichier.']);
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
?>