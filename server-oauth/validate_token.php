<?php
require_once 'database.php';

if (!isset($_GET['access_token'])) {
    http_response_code(400);
    echo json_encode(["error" => "Token manquant"]);
    exit;
}

$token = $_GET['access_token'];

$stmt = $pdo->prepare("SELECT * FROM access_tokens WHERE access_token = ?");
$stmt->execute([$token]);
$data = $stmt->fetch();

if (!$data || strtotime($data['expires']) < time()) {
    http_response_code(401);
    echo json_encode(["error" => "Token invalide ou expiré"]);
    exit;
}

echo json_encode(["active" => true, "user_id" => $data['user_id'], "scope" => $data['scope']]);
?>
