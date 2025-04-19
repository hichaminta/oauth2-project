<?php
include 'config.php';

// Récupérer le code d'autorisation depuis l'URL
$code = $_GET['code'];  // Code d'autorisation

// Configuration client
$client_id = CLIENT_ID;
$client_secret = CLIENT_SECRET;
$redirect_uri = REDIRECT_URI;

// Préparer la requête pour échanger le code d'autorisation contre un jeton
$response = file_get_contents(TOKEN_URL, false, stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
        'content' => http_build_query([
            'CLIENT_ID' => $client_id,
            'client_secret' => $client_secret,
            'code' => $code,
            'redirect_uri' => $redirect_uri
        ])
    ]
]));

// Décoder la réponse JSON pour obtenir le jeton d'accès
$token_data = json_decode($response, true);

// Récupérer le jeton d'accès
$access_token = $token_data['access_token'];

// Rediriger vers la page des ressources protégées avec le jeton d'accès
header('Location: ' . RESOURCE_URL . '?access_token=' . $access_token);
exit;
?>
