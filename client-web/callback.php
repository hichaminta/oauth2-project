<?php
session_start();
if (!isset($_GET['code'])) {
    die("Code d'autorisation manquant.");
}

$code = $_GET['code'];
$token_url = "http://localhost/oauth2-project/server-oauth/token.php";
$client_id = "quickview-client";
$client_secret = "secret123";
$redirect_uri = "http://localhost/oauth2-project/client-web/callback.php";

// Appel POST pour obtenir le token
$ch = curl_init($token_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'grant_type' => 'authorization_code',
    'code' => $code,
    'redirect_uri' => $redirect_uri,
    'client_id' => $client_id,
    'client_secret' => $client_secret
]));

$response = curl_exec($ch);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    die("Erreur CURL: " . $error);
}

$data = json_decode($response, true);
if (isset($data['access_token'])) {
    $_SESSION['access_token'] = $data['access_token'];
    $_SESSION['expires_in'] = $data['expires_in'];
    $_SESSION['token_created'] = time();
    header("Location: view.php");
    exit;
} else {
    echo "Erreur lors de la récupération du jeton :";
    var_dump($data);
}
?>