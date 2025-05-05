<?php
session_start();

// Si l'utilisateur est déjà authentifié, rediriger vers view.php
if (isset($_SESSION['access_token'])) {
    header("Location: view.php");
    exit;
}

// Paramètres pour la construction de l'URL d'autorisation
$client_id = "quickview-client";
$redirect_uri = "http://localhost/oauth2-project/client-web/callback.php";
$auth_url = "http://localhost/oauth2-project/server-oauth/authorization.php";

// Nous demandons maintenant les scopes 'read' et 'write'
$scope = "read write";

// Construire l'URL d'autorisation
$auth_request_url = $auth_url . "?" . http_build_query([
    'client_id' => $client_id,
    'redirect_uri' => $redirect_uri,
    'response_type' => 'code',
    'scope' => $scope
]);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accès aux fichiers</title>
    <link rel="stylesheet" href="css/index.css">
</head>
<body>
    <div class="container">
        <h1>Bienvenue</h1>
        <p>Accédez à vos fichiers sécurisés</p>
        
        <div class="login-button-container">
            <a href="<?= htmlspecialchars($auth_request_url) ?>" class="login-button">
                Login avec SecureAuth
            </a>
        </div>
    </div>
</body>
</html>