<?php
// Inclure la configuration contenant la fonction validate_token
include 'config.php';  // Ajustez le chemin en fonction de votre structure de projet
include 'validate_token.php';  // Ajustez le chemin en fonction de votre structure de projet

// Vérifier si le jeton d'accès et le fichier sont passés dans la requête
if (!isset($_GET['access_token']) || !isset($_GET['file'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Jeton ou fichier manquant.']);
    exit;
}

$access_token = $_GET['access_token'];
$file = $_GET['file'];

// Vérifier la validité du jeton d'accès
if (!validate_token($access_token)) {
    http_response_code(403);
    echo json_encode(['error' => 'Jeton invalide ou expiré.']);
    exit;
}

$directory = 'ressources/';
$file_path = $directory . basename($file); // Sécuriser le chemin du fichier

// Vérifier si le fichier existe
if (!file_exists($file_path)) {
    http_response_code(404);
    echo json_encode(['error' => 'Fichier non trouvé.']);
    exit;
}

// Fournir le fichier pour téléchargement
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($file) . '"');
header('Content-Length: ' . filesize($file_path));

readfile($file_path);
exit;
?>
