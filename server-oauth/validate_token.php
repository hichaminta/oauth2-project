<?php
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_GET['access_token'])) {
    http_response_code(400);
    echo json_encode(['error' => 'access_token manquant']);
    exit;
}

$access_token = $_GET['access_token'];

try {
    $db = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $db->prepare("SELECT user_id, client_id, scope, expires FROM access_tokens WHERE access_token = :token AND expires > NOW()");
    $stmt->execute(['token' => $access_token]);

    $token_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($token_data) {
        echo json_encode([
            'active' => true,
            'user_id' => $token_data['user_id'],
            'client_id' => $token_data['client_id'],
            'scope' => $token_data['scope'],
            'expires' => $token_data['expires']
        ]);
    } else {
        echo json_encode(['active' => false]);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur base de données']);
}
