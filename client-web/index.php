<?php
session_start();
include_once 'variable.php';

// Si l'utilisateur est déjà authentifié, rediriger vers view.php
if (isset($_SESSION['access_token'])) {
    header("Location: view.php");
    exit;
}

// Si l'utilisateur vient directement sur index.php, le rediriger vers services.php
if (!isset($_GET['login'])) {
    header("Location: services.php");
    exit;
}

// Paramètres pour la construction de l'URL d'autorisation
$client_id = "quickview-client";
$redirect_uri = $domainenameclient."callback.php";
$auth_url = $domainenameserverauth."authorization.php";
$scope = "read";

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
    <title>QuickView - Connexion</title>
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <div class="card" style="max-width: 500px; margin: 80px auto;">
            <div style="text-align: center; margin-bottom: 2rem;">
                <i class="fas fa-file-search" style="font-size: 4rem; color: var(--primary-color);"></i>
                <h1 style="margin-top: 1rem; color: var(--primary-color);">QuickView</h1>
                <p style="color: var(--secondary-color); margin-top: 1rem; font-size: 1.2rem;">
                    Visualisez et gérez vos fichiers de manière rapide et sécurisée
                </p>
            </div>
            
            <div class="card" style="background: var(--light-gray); padding: 2rem; text-align: center; margin-top: 2rem;">
                <h2 style="margin-bottom: 1.5rem;">Connexion</h2>
                <p style="margin-bottom: 2rem;">
                    Connectez-vous pour accéder à vos documents sécurisés
                </p>
                <a href="<?= htmlspecialchars($auth_request_url) ?>" class="btn btn-primary" style="display: inline-block; min-width: 200px; font-size: 1.1rem;">
                    <i class="fas fa-sign-in-alt"></i> Se connecter avec SecureAuth
                </a>
            </div>
        </div>
    </div>

    <footer style="text-align: center; padding: 2rem 0; color: var(--secondary-color); background-color: var(--white); margin-top: 3rem; position: fixed; bottom: 0; width: 100%;">
        <p>&copy; 2024 QuickView. Tous droits réservés.</p>
    </footer>
</body>
</html>