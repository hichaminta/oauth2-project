<?php
session_start();
$auth_server = "http://localhost/oauth2-project/server-oauth/authorization.php";
$client_id = "quickview-client";
$redirect_uri = "http://localhost/oauth2-project/client-web/callback.php";
$scope = "read";

$auth_url = "$auth_server?response_type=code&client_id=$client_id&redirect_uri=$redirect_uri&scope=$scope";
?>

<h2>Bienvenue sur QuickView (Client OAuth)</h2>
<a href="<?= $auth_url ?>">Se connecter avec SecureAuth</a>
