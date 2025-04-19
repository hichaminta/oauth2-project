<?php
// token.php
include 'config.php';
include 'database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Méthode non autorisée.");
}

$client_id = $_POST['client_id'] ?? '';
$client_secret = $_POST['client_secret'] ?? '';
$code = $_POST['code'] ?? '';

// Vérifier le client
$stmt = $pdo->prepare("SELECT * FROM clients WHERE client_id = ? AND client_secret = ?");
$stmt->execute([$client_id, $client_secret]);
$client = $stmt->fetch();

if (!$client) {
    die("Client invalide.");
}

// Vérifier le code d’autorisation
$stmt = $pdo->prepare("SELECT * FROM authorization_codes WHERE code = ? AND client_id = ?");
$stmt->execute([$code, $client_id]);
$auth_code = $stmt->fetch();

if (!$auth_code || strtotime($auth_code['expires_at']) < time()) {
    die("Code d’autorisation invalide ou expiré.");
}

// Générer un jeton d'accès
$access_token = bin2hex(random_bytes(32));
$expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

// Mise à jour de la requête pour utiliser 'access_token' au lieu de 'token'
$stmt = $pdo->prepare("INSERT INTO access_tokens (access_token, user_id, client_id, expires) 
                       VALUES (?, ?, ?, ?)");
$stmt->execute([$access_token, $auth_code['user_id'], $client_id, $expires]);

// Générer un jeton de rafraîchissement
$refresh_token = bin2hex(random_bytes(32));
$stmt = $pdo->prepare("INSERT INTO refresh_tokens (refresh_token, user_id, client_id, expires) 
                       VALUES (?, ?, ?, ?)");
$stmt->execute([$refresh_token, $auth_code['user_id'], $client_id, date('Y-m-d H:i:s', strtotime('+30 days'))]);

echo json_encode([
    'access_token' => $access_token,
    'token_type' => 'bearer',
    'expires_in' => 3600,
    'refresh_token' => $refresh_token
]);
?>
