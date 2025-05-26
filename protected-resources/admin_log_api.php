<?php
session_start();
header('Content-Type: application/json');

if (!isset($_GET['access_token'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Token d\'accès requis']);
    exit();
}

$access_token = $_GET['access_token'];
$validation_url = "http://localhost/oauth2-project/server-oauth/validate_token.php?access_token=" . urlencode($access_token);

// Appel de l'API de validation avec cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $validation_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

$token_info = json_decode($response, true);

// Vérifie si le token est valide
if (!isset($token_info['active']) || $token_info['active'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Token invalide ou expiré']);
    exit();
}

// Connexion à la base de données (même config que dans validate_token.php)
require_once '../server-oauth/config.php';

try {
    $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $conn->prepare("SELECT `id`, `timestamp`, `ip_address`, `user_id`, `file_id`, `filename`, `action`, `success`, `message`, `blockchain_hash`
                            FROM `access_logs`");
    $stmt->execute();

    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($logs);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur base de données']);
}
