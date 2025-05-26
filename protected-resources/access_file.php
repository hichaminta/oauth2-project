<?php
header('Content-Type: application/json');

// Fonction de journalisation des accès
include_once 'fonction.php' ;


// Vérifier si le jeton d'accès et le nom de fichier sont fournis
if (!isset($_GET['access_token']) || !isset($_GET['file'])) {
    $message = 'Jeton ou fichier manquant.';
    logAccess(null, null, $_GET['file'] ?? null, false, $message, 'download');
    http_response_code(400);
    echo json_encode(['error' => $message]);
    exit;
}

$access_token = $_GET['access_token'];
$filename = $_GET['file'];

// Valider le token via SecureAuth
$validation_url = "http://localhost/oauth2-project/server-oauth/validate_token.php?access_token=" . urlencode($access_token);
$response = @file_get_contents($validation_url);

if ($response === FALSE) {
    $message = 'Impossible de vérifier le token.';
    logAccess(null, null, $filename, false, $message, 'download');
    http_response_code(500);
    echo json_encode(['error' => $message]);
    exit;
}

$data = json_decode($response, true);

if (!isset($data['active']) || !$data['active']) {
    $message = 'Jeton invalide ou expiré.';
    logAccess(null, null, $filename, false, $message, 'download');
    http_response_code(403);
    echo json_encode(['error' => $message]);
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
    $message = 'Scope insuffisant pour accéder au fichier.';
    logAccess($user_id, null, $filename, false, $message, 'download');
    http_response_code(403);
    echo json_encode(['error' => $message]);
    exit;
} else {
    $stmt = $pdo->prepare($query);
    $stmt->execute([$filename, $user_id]);
}

$file = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$file) {
    $message = 'Fichier non trouvé ou accès non autorisé.';
    logAccess($user_id, null, $filename, false, $message, 'download');
    http_response_code(404);
    echo json_encode(['error' => $message]);
    exit;
}

// Récupérer l'ID du fichier pour la journalisation
$file_id = $file['id'];

// Journaliser l'accès avant le téléchargement
$init_message = sprintf(
    'Téléchargement du fichier initié (ID: %d, Nom: %s, Taille: %s, Chemin: %s)',
    $file_id,
    $filename,
    number_format($file['size'] / 1024, 2) . ' KB',
    $file['path']
);
logAccess($user_id, $file_id, $filename, true, $init_message, 'download');

// Préparer le chemin du fichier
$file_path = $file['path'];

// Vérifier l'existence du fichier
if (!file_exists($file_path) || !is_file($file_path)) {
    $message = 'Fichier physique non trouvé.';
    logAccess($user_id, $file_id, $filename, false, $message, 'download');
    http_response_code(404);
    echo json_encode(['error' => $message]);
    exit;
}

// Journaliser le succès avant l'envoi
$success_message = sprintf(
    'Téléchargement du fichier réussi (ID: %d, Nom: %s, Taille: %s, Chemin: %s)',
    $file_id,
    $filename,
    number_format($file['size'] / 1024, 2) . ' KB',
    $file['path']
);
logAccess($user_id, $file_id, $filename, true, $success_message, 'download_complete');

// Envoyer le fichier
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
header('Content-Length: ' . filesize($file_path));

// Nettoyer le tampon avant l'envoi
ob_clean();
flush();
readfile($file_path);
exit;