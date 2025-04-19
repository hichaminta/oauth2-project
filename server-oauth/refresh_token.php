<?php
// refresh_token.php
include 'config.php';
include 'database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Méthode non autorisée.");
}

$client_id = $_POST['client_id'] ?? '';
$client_secret = $_POST['client_secret'] ?? '';
$refresh_token = $_POST['refresh_token'] ?? '';

// Vérification du client
$stmt = $pdo->prepare("SELECT * FROM clients WHERE client_id = ? AND client_secret = ?");
$stmt->execute([$client_id, $client_secret]);
$client = $stmt->fetch();

if (!$client) {
    die("Client invalide.");
}

// Vérification du refresh token
$stmt = $pdo->prepare("SELECT * FROM refresh_tokens WHERE token = ? AND client_id = ?");
$stmt->execute([$refresh_token, $client_id]);
$refresh = $stmt->fetch();

if (!$refresh || strtotime($refresh['expires_at']) < time()) {
    die("Refresh token invalide ou expiré.");
}

// Générer un nouveau jeton d’accès
$new_access_token = bin2hex(random_bytes(32));
$expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

$stmt = $pdo->prepare("UPDATE access_tokens SET token = ?, expires_at = ? WHERE user_id = ?");
$stmt->execute([$new_access_token, $expires, $refresh['user_id']]);

echo json_encode([
    'access_token' => $new_access_token,
    'token_type' => 'bearer',
    'expires_in' => 3600
]);
?>
