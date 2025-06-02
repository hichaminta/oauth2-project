<?php
require_once 'database.php';

header('Content-Type: application/json');

// Vérification si la méthode est GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée']);
    exit;
}

// Récupération des paramètres via GET
$refresh_token = $_GET['refresh_token'] ?? '';
$client_id = $_GET['client_id'] ?? '';
$client_secret = $_GET['client_secret'] ?? '';

if (empty($refresh_token) || empty($client_id) || empty($client_secret)) {
    http_response_code(400);
    echo json_encode(['error' => 'Paramètres manquants']);
    exit;
}

// Valider le client
$stmt = $pdo->prepare("SELECT * FROM clients WHERE client_id = ? AND client_secret = ?");
$stmt->execute([$client_id, $client_secret]);
$client = $stmt->fetch();

if (!$client) {
    http_response_code(401);
    echo json_encode(['error' => 'Client invalide']);
    exit;
}

//  Vérifier le refresh_token
$stmt = $pdo->prepare("SELECT * FROM refresh_tokens WHERE refresh_token = ? AND client_id = ? AND expires > NOW()");
$stmt->execute([$refresh_token, $client_id]);
$token_data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$token_data) {
    http_response_code(401);
    echo json_encode(['error' => 'Refresh token invalide ou expiré']);
    exit;
}

//  Générer un nouveau access_token
$new_access_token = bin2hex(random_bytes(32));
$expires_in = 1800; // 30 minutes exactement
$expires_at = date('Y-m-d H:i:s', time() + $expires_in);

//  Enregistrer le nouveau token
$client_id = $token_data['client_id'];
$scope = $token_data['scope'] ?? '';

$stmt = $pdo->prepare("INSERT INTO access_tokens (access_token, client_id, user_id, expires, scope) VALUES (?, ?, ?, ?, ?)");
$stmt->execute([$new_access_token, $client_id, $token_data['user_id'], $expires_at, $scope]);

// Publier le nouveau token sur la blockchain
$token_data_blockchain = [
    'access_token' => $new_access_token,
    'client_id' => $client_id,
    'user_id' => $token_data['user_id'],
    'expires' => $expires_at,
    'scope' => $scope,
    'timestamp' => time()
];
publishToBlockchain('chain_oauth_token_nv', $token_data_blockchain);

//  Réponse
echo json_encode([
    'access_token' => $new_access_token,
    'token_type' => 'Bearer',
    'expires_in' => $expires_in,
    'scope' => $token_data['scope']
]);

// Ajouter la fonction publishToBlockchain
function publishToBlockchain($stream, $data) {
    $rpc_url = "http://multichainrpc:HtbLPm5f1X3HB9XkdkngARZzbbJN7FwDtGtAHJ6Tn3bQ@localhost:5000";
    $request = [
        'method' => 'publish',
        'params' => [
            $stream,
            'token-' . time(),
            bin2hex(json_encode($data))
        ],
        'id' => uniqid(),
        'chain_name' => 'AuthChain'
    ];
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
