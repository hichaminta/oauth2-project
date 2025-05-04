<?php
include 'config.php';         // Paramètres de connexion à la base
include 'validate_token.php'; // Inclure la fonction validate_token()

// Vérifier si le jeton d'accès est passé dans la requête
if (!isset($_GET['access_token'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Jeton d\'accès manquant.']);
    exit;
}

$access_token = $_GET['access_token'];

// 🔐 Valider le jeton en appelant la fonction
if (!validate_token($access_token)) {
    http_response_code(403);
    echo json_encode(['error' => 'Jeton invalide ou expiré.']);
    exit;
}

// ✅ Jeton valide → lister les fichiers dans le dossier 'ressources/'
$directory = 'ressources/';

if (!is_dir($directory)) {
    http_response_code(500);
    echo json_encode(['error' => 'Le dossier "ressources/" n\'existe pas.']);
    exit;
}

$files = scandir($directory);
$files = array_diff($files, array('.', '..'));

$file_data = [];
foreach ($files as $file) {
    $file_data[] = [
        'name' => $file,
        'size' => filesize($directory . $file),
        'url'  => "http://localhost/oauth2-project/protected-resources/access_file.php?access_token=" . urlencode($access_token) . "&file=" . urlencode($file)
    ];
}

// Retourner les fichiers en JSON
echo json_encode(['files' => $file_data]);
?>
