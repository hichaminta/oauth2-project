<?php
// logout.php
session_start();
include_once 'variable.php';

// 1. Récupérer les infos en session
$accessToken   = $_SESSION['access_token']   ?? null;
$clientId      = $_SESSION['client_id']      ?? null;
$clientSecret  = $_SESSION['client_secret']  ?? null;
if ($accessToken && $clientId && $clientSecret) {
    $revokeUrl = $domainenameserverauth.'revoke.php';

    $postData = http_build_query([
        'token'         => $accessToken,
        'client_id'     => $clientId,
        'client_secret' => $clientSecret,
    ]);

    $ch = curl_init($revokeUrl);
    curl_setopt($ch, CURLOPT_POST,        true);
    curl_setopt($ch, CURLOPT_POSTFIELDS,  $postData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded',
        // (optionnel) restreindre l’origine si besoin
        //'Origin: https://votre-application-client.com'
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
var_dump($response);

    // (optionnel) vous pouvez logger $response et $httpCode pour audit
}


session_destroy();

// 4. Redirection
header("Location: index.php");
exit;
