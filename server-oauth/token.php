<?php
require_once 'database.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

$code = $_POST['code'] ?? '';
$client_id = $_POST['client_id'] ?? '';
$client_secret = $_POST['client_secret'] ?? '';
$redirect_uri = $_POST['redirect_uri'] ?? '';
$grant_type = $_POST['grant_type'] ?? '';

if (empty($code) || empty($client_id) || empty($client_secret) || empty($redirect_uri) || empty($grant_type)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required parameter']);
    exit;
}

if ($grant_type !== 'authorization_code') {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid grant_type']);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM clients WHERE client_id = ? AND client_secret = ?");
$stmt->execute([$client_id, $client_secret]);
$client = $stmt->fetch();

if (!$client) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid client']);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM authorization_codes WHERE code = ? AND client_id = ? AND redirect_uri = ? AND expires > NOW()");
$stmt->execute([$code, $client_id, $redirect_uri]);
$auth_code = $stmt->fetch();

if (!$auth_code) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid or expired authorization code']);
    exit;
}

// Delete the authorization code after use
$stmt = $pdo->prepare("DELETE FROM authorization_codes WHERE code = ?");
$stmt->execute([$code]); // Using the original code, not the hardcoded "11"

// Create tokens with short expiration
$access_token = bin2hex(random_bytes(32));
$expires_in = 60; // 1 minute
$access_token_expires = date('Y-m-d H:i:s', time() + $expires_in);

$refresh_token = bin2hex(random_bytes(40));
$refresh_token_expires = date('Y-m-d H:i:s', time() + (5 * 60)); // 5 minutes

// Store the access token
$stmt = $pdo->prepare("INSERT INTO access_tokens (access_token, client_id, user_id, expires, scope) VALUES (?, ?, ?, ?, ?)");
$stmt->execute([$access_token, $client_id, $auth_code['user_id'], $access_token_expires, $auth_code['scope']]);

// Store the refresh token
$stmt = $pdo->prepare("INSERT INTO refresh_tokens (refresh_token, client_id, user_id, expires, scope) VALUES (?, ?, ?, ?, ?)");
$stmt->execute([$refresh_token, $client_id, $auth_code['user_id'], $refresh_token_expires, $auth_code['scope']]);

// Store token in blockchain
$token_data = [
    'access_token' => $access_token,
    'client_id' => $client_id,
    'user_id' => $auth_code['user_id'],
    'expires' => $access_token_expires,
    'scope' => $auth_code['scope'],
    'timestamp' => time()
];

// Register token on blockchain
$result = publishToBlockchain('chain_oauth_token_nv', $token_data);

// Return to client
echo json_encode([
    'access_token' => $access_token,
    'token_type' => 'Bearer',
    'expires_in' => $expires_in,
    'refresh_token' => $refresh_token,
    'scope' => $auth_code['scope']
]);


function publishToBlockchain($stream, $data) {
    $rpc_url = "http://multichainrpc:HtbLPm5f1X3HB9XkdkngARZzbbJN7FwDtGtAHJ6Tn3bQ@localhost:5000";
    
    // Prepare JSON-RPC request
    $request = [
        'method' => 'publish',
        'params' => [
            $stream,                   // Stream name
            'token-' . time(),         // Key (unique identifier)
            bin2hex(json_encode($data)) // Data in hex format
        ],
        'id' => uniqid(),
        'chain_name' => 'AuthChain'
    ];
    
    // Send request to MultiChain
    $ch = curl_init($rpc_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($request));
    
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        error_log("Blockchain error");
        return ['error' => 'Erreur de connexion blockchain'];
    }
    
    return json_decode($response, true);
}
?>