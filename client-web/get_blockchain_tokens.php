<?php
session_start();
include_once 'variable.php';

// Vérifier si l'utilisateur est connecté et a les droits admin
if (!isset($_SESSION['access_token'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Non autorisé']);
    exit();
}

// Vérifier si le token est encore valide
if (!isset($_SESSION['token_created']) || !isset($_SESSION['expires_in'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Token expiré']);
    exit();
}

if (time() > $_SESSION['token_created'] + $_SESSION['expires_in']) {
    http_response_code(401);
    echo json_encode(['error' => 'Token expiré']);
    exit();
}

// Vérifier si l'utilisateur a le scope admin
$resource_url = $domainenameprressources . "resource.php?access_token=" . $_SESSION['access_token'];
$response = file_get_contents($resource_url);
$data = json_decode($response, true);

if (!isset($data['user']['scopes']) || !in_array('admin', $data['user']['scopes'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Accès refusé']);
    exit();
}

try {
    // Connexion à la base de données
    $pdo = new PDO("mysql:host=localhost;dbname=oauth2", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Récupérer tous les tokens avec leurs métadonnées
    $stmt = $pdo->query("SELECT * FROM blockchain_tokens ORDER BY timestamp DESC");
    $tokens = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Convertir les données JSON en objets PHP
    foreach ($tokens as &$token) {
        $token['token_data'] = json_decode($token['token_data'], true);
        $token['decoded_data'] = $token['token_data']['decoded_data'] ?? [];
    }
    
    // Envoyer les données au format JSON
    header('Content-Type: application/json');
    echo json_encode($tokens);
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur de base de données: ' . $e->getMessage()]);
}
?> 