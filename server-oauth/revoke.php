<?php
// revoke.php
require_once 'database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'] ?? '';
    $client_id = $_POST['client_id'] ?? '';
    $client_secret = $_POST['client_secret'] ?? '';

    if (empty($token) || empty($client_id) || empty($client_secret)) {
        http_response_code(400);
        echo json_encode(['error' => 'Token, client_id ou client_secret manquant']);
        exit;
    }

    // Vérifier que le client est valide
    $stmt = $pdo->prepare("SELECT * FROM clients WHERE client_id = ? AND client_secret = ?");
    $stmt->execute([$client_id, $client_secret]);
    $client = $stmt->fetch();

    if (!$client) {
        http_response_code(401);
        echo json_encode(['error' => 'Client non autorisé']);
        exit;
    }

    // Expirer le token
    $expiredTime = date('Y-m-d H:i:s', strtotime('-23 hour'));
    $stmt = $pdo->prepare("UPDATE access_tokens SET expires = ? WHERE access_token = ? AND client_id = ?");
    $stmt->execute([$expiredTime, $token, $client_id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => 'Token expiré']);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Token non trouvé ou déjà expiré']);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée']);
}
?>
