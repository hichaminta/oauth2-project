<?php
require_once 'database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $grant_type = $_POST['grant_type'];
    $refresh_token = $_POST['refresh_token'];
    $client_id = $_POST['client_id'];
    $client_secret = $_POST['client_secret'];

    if ($grant_type !== 'refresh_token') {
        http_response_code(400);
        echo json_encode(["error" => "Type de flux invalide"]);
        exit;
    }

    // Vérifier client
    $stmt = $pdo->prepare("SELECT * FROM clients WHERE client_id = ? AND client_secret = ?");
    $stmt->execute([$client_id, $client_secret]);
    if (!$stmt->fetch()) {
        http_response_code(401);
        echo json_encode(["error" => "Client non valide"]);
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM refresh_tokens WHERE refresh_token = ?");
    $stmt->execute([$refresh_token]);
    $data = $stmt->fetch();

    if (!$data || strtotime($data['expires']) < time()) {
        http_response_code(401);
        echo json_encode(["error" => "Refresh token invalide"]);
        exit;
    }

    // Nouveau access token
    $new_access_token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', time() + 3600);

    $stmt = $pdo->prepare("INSERT INTO access_tokens (access_token, client_id, user_id, expires, scope) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$new_access_token, $client_id, $data['user_id'], $expires, $data['scope']]);

    echo json_encode([
        "access_token" => $new_access_token,
        "token_type" => "Bearer",
        "expires_in" => 3600
    ]);
}
?>
