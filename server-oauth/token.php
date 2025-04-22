<?php
require_once 'database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer les paramètres envoyés
    $code = $_POST['code'];
    $client_id = $_POST['client_id'];
    $client_secret = $_POST['client_secret'];
    $redirect_uri = $_POST['redirect_uri'];
    $grant_type = $_POST['grant_type'];

    // Vérifier les paramètres
    if ($grant_type !== 'authorization_code') {
        echo json_encode(['error' => 'Invalid grant_type']);
        exit;
    }

    // Vérification du client_id et client_secret
    $stmt = $pdo->prepare("SELECT * FROM clients WHERE client_id = ? AND client_secret = ?");
    $stmt->execute([$client_id, $client_secret]);
    $client = $stmt->fetch();

    if (!$client) {
        echo json_encode(['error' => 'Client non valide']);
        exit;
    }

    // Vérifier le code d'autorisation dans la base de données
    $stmt = $pdo->prepare("SELECT * FROM authorization_codes WHERE code = ? AND client_id = ? AND redirect_uri = ?");
    $stmt->execute([$code, $client_id, $redirect_uri]);
    $auth_code = $stmt->fetch();

    if (!$auth_code || strtotime($auth_code['expires']) < time()) {
        echo json_encode(['error' => 'Code d\'autorisation invalide ou expiré']);
        exit;
    }

    // Créer un jeton d'accès
    $access_token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', time() + 3600); // Le jeton expire dans 1 heure

    // Sauvegarder le jeton d'accès dans la base de données
    $stmt = $pdo->prepare("INSERT INTO access_tokens (access_token, client_id, user_id, expires, scope) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$access_token, $client_id, $auth_code['user_id'], $expires, $auth_code['scope']]);

    // Retourner le jeton d'accès au client
    echo json_encode(['access_token' => $access_token, 'expires_in' => 3600]);
} else {
    echo json_encode(['error' => 'Method Not Allowed']);
}
?>
