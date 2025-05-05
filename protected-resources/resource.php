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

// Récupérer l'ID utilisateur et le scope du token
$user_id = $data['user_id'];
$scope = $data['scope'];

// Initialiser la connexion à la base de données
require_once '../server-oauth/database.php';

// Vérifier si l'utilisateur a le scope 'read'
$has_read_scope = strpos($scope, 'read') !== false;
$has_admin_scope = strpos($scope, 'admin') !== false;

if (!$has_read_scope && !$has_admin_scope) {
    http_response_code(403);
    echo json_encode(['error' => 'Permissions insuffisantes. Le scope "read" est requis.']);
    exit;
}

// Lister les fichiers disponibles
$directory = __DIR__ . '/ressources/';
if (!is_dir($directory)) {
    http_response_code(500);
    echo json_encode(['error' => 'Le dossier ressources/ est introuvable.']);
    exit;
}

// Si l'utilisateur a le scope admin, il peut voir tous les fichiers
if ($has_admin_scope) {
    $stmt = $pdo->prepare("
        SELECT f.id, f.filename, f.size, 1 as can_read, 1 as can_write, 1 as can_delete
        FROM files f
    ");
    $stmt->execute();
} 
// Sinon, on récupère seulement les fichiers auxquels l'utilisateur a accès
else {
    $stmt = $pdo->prepare("
        SELECT f.id, f.filename, f.size, 
               fp.can_read, fp.can_write, fp.can_delete
        FROM files f
        JOIN files_permissions fp ON f.id = fp.file_id
        WHERE fp.user_id = ? AND fp.can_read = 1
    ");
    $stmt->execute([$user_id]);
}

$files = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Préparer le tableau de réponse
$file_data = [];
foreach ($files as $file) {
    // Vérifier que le fichier existe physiquement
    $path = $directory . $file['filename'];
    if (file_exists($path) && is_file($path)) {
        // Mettre à jour la taille réelle dans la base de données si nécessaire
        $real_size = filesize($path);
        if ($file['size'] != $real_size) {
            $update = $pdo->prepare("UPDATE files SET size = ? WHERE id = ?");
            $update->execute([$real_size, $file['id']]);
            $file['size'] = $real_size;
        }
        
        // Ajouter le fichier à la liste
        $file_data[] = [
            'id' => $file['id'],
            'name' => $file['filename'],
            'size' => $file['size'],
            'permissions' => [
                'read' => (bool)$file['can_read'],
                'write' => (bool)$file['can_write'],
                'delete' => (bool)$file['can_delete']
            ],
            'url' => "http://localhost/oauth2-project/protected-resources/access_file.php?access_token=" . 
                   urlencode($access_token) . "&file=" . urlencode($file['filename'])
        ];
    }
}

echo json_encode(['files' => $file_data]);
?>