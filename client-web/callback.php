<?php
session_start();
include_once 'variable.php';

if (!isset($_GET['code'])) {
    die("Code d'autorisation manquant.");
}

$code = $_GET['code'];
$token_url = $domainenameserverauth . "token.php";
$client_id = "quickview-client";
$client_secret = "secret123";
$redirect_uri = $domainenameclient . "callback.php";

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
    // Stockage de l'access token et du refresh token en session
    $_SESSION['access_token'] = $data['access_token'];
    $_SESSION['refresh_token'] = $data['refresh_token'] ?? null; // Assurez-vous que le refresh_token est présent dans la réponse
    $_SESSION['expires_in'] = $data['expires_in'];
    $_SESSION['token_created'] = time();
    $_SESSION['client_id'] = "quickview-client";
    $_SESSION['client_secret'] = "secret123";

    header("Location: view.php");
    exit;
} else {
    echo "Erreur lors de la récupération du jeton :";
    var_dump($data);
}
?>
