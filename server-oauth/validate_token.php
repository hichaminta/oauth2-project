<?php
// validate_token.php
include 'config.php';
include 'database.php';

$headers = apache_request_headers();
$auth = $headers['Authorization'] ?? '';

if (preg_match('/Bearer\s(\S+)/', $auth, $matches)) {
    $token = $matches[1];

    $stmt = $pdo->prepare("SELECT * FROM access_tokens WHERE token = ?");
    $stmt->execute([$token]);
    $data = $stmt->fetch();

    if ($data && strtotime($data['expires_at']) > time()) {
        echo "Token valide.";
    } else {
        http_response_code(401);
        echo "Token expiré ou invalide.";
    }
} else {
    http_response_code(400);
    echo "Token manquant.";
}
?>
