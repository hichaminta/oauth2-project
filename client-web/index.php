<?php
session_start();
$auth_server = "http://localhost/oauth2-project/server-oauth/authorization.php";
$client_id = "quickview-client";
$redirect_uri = "http://localhost/oauth2-project/client-web/callback.php";
$scope = "read";

$auth_url = "$auth_server?response_type=code&client_id=$client_id&redirect_uri=$redirect_uri&scope=$scope";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QuickView - Connexion</title>
    <link rel="stylesheet" href="css/index.css"> <!-- Inclusion du fichier CSS -->
</head>
<body>
    <div class="container">
        <h2>Bienvenue sur QuickView </h2>
        <a href="<?= $auth_url ?>">Se connecter avec SecureAuth</a>
    </div>
</body>
</html>