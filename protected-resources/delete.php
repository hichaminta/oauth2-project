<?php
header('Content-Type: application/json');

// Fonction de journalisation des accès
include_once 'fonction.php' ;
// Vérifier si le jeton d'accès et l'ID du fichier sont fournis
if (!isset($_GET['access_token']) || !isset($_GET['file_id'])) {
    $message = 'Jeton ou ID de fichier manquant.';
    logAccess(null, $_GET['file_id'] ?? null, null, false, $message,action:'delete',);
    http_response_code(400);
    echo json_encode(['error' => $message]);
    exit;
}

$access_token = $_GET['access_token'];
$file_id = $_GET['file_id'];

// Valider le token via SecureAuth
$validation_url = "http://localhost/oauth2-project/server-oauth/validate_token.php?access_token=" . urlencode($access_token);
$response = @file_get_contents($validation_url);

if ($response === FALSE) {
    $message = 'Impossible de vérifier le token.';
    logAccess(null, $file_id, null, false, $message,action:'delete');
    http_response_code(500);
    echo json_encode(['error' => $message]);
    exit;
}

$data = json_decode($response, true);

if (!isset($data['active']) || !$data['active']) {
    $message = 'Jeton invalide ou expiré.';
    logAccess(null, $file_id, null, false, $message,action:'delete');
    http_response_code(403);
    echo json_encode(['error' => $message]);
    exit;
}

// Vérifier les scopes
$scopes = explode(' ', $data['scope']);
$user_id = $data['user_id'];

// Seuls les utilisateurs avec le scope admin peuvent supprimer des fichiers
if (!in_array('admin', $scopes)) {
    $message = 'Scope insuffisant pour supprimer des fichiers.';
    logAccess($user_id, $file_id, null, false, $message,action:'delete');
    http_response_code(403);
    echo json_encode(['error' => $message]);
    exit;
}

// Connexion à la base de données
require_once __DIR__ . '/../server-oauth/database.php';

// Vérifier si le fichier existe
$stmt = $pdo->prepare("SELECT * FROM files WHERE id = ?");
$stmt->execute([$file_id]);
$file = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$file) {
    $message = 'Fichier non trouvé.';
    logAccess($user_id, $file_id, null, false, $message,action:'delete');
    http_response_code(404);
    echo json_encode(['error' => $message]);
    exit;
}

// Journaliser avant de supprimer (pour garder les infos du fichier)
$filename = $file['filename'];
logAccess($user_id, $file_id, $filename, true, 'Suppression du fichier initiée', 'delete');

// Supprimer les permissions du fichier
$stmt = $pdo->prepare("DELETE FROM file_permissions WHERE file_id = ?");
$stmt->execute([$file_id]);

// Supprimer le fichier de la base de données
$stmt = $pdo->prepare("DELETE FROM files WHERE id = ?");
$stmt->execute([$file_id]);

// Supprimer le fichier physique
$file_path = $file['path'];
$physical_deletion_success = false;

if (file_exists($file_path) && is_file($file_path)) {
    $physical_deletion_success = unlink($file_path);
}

// Journaliser le résultat final de la suppression
$final_message = $physical_deletion_success ? 
    'Fichier supprimé avec succès (DB + disque)' : 
    'Fichier supprimé de la DB mais échec de suppression sur disque';

logAccess($user_id, $file_id, $filename, true, $final_message, 'delete_complete');

echo json_encode([
    'success' => true,
    'message' => 'Fichier supprimé avec succès.',
    'details' => [
        'database_deletion' => true,
        'physical_deletion' => $physical_deletion_success
    ]
]);