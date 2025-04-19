<?php
// callback.php - Gestion du code d'autorisation et récupération du jeton d'accès

// Inclure la configuration
include 'config.php';

// Vérifier si le code d'autorisation est dans l'URL
if (!isset($_GET['code'])) {
    die('Code d\'autorisation manquant.');
}

$code = $_GET['code'];  // Récupérer le code d'autorisation

// Préparer la requête pour échanger le code contre un jeton d'accès
$response = file_get_contents(TOKEN_URL, false, stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
        'content' => http_build_query([
            'client_id' => CLIENT_ID,
            'client_secret' => CLIENT_SECRET,
            'code' => $code,
            'redirect_uri' => REDIRECT_URI,
            'grant_type' => 'authorization_code'  // Type de flux d'autorisation
        ])
    ]
]));

// Décoder la réponse JSON
$token_data = json_decode($response, true);

// Vérifier si le jeton d'accès est dans la réponse
if (isset($token_data['access_token'])) {
    $access_token = $token_data['access_token'];

    // Sauvegarder le jeton d'accès pour l'utiliser plus tard (session ou base de données)
    session_start();
    $_SESSION['access_token'] = $access_token;

    // Rediriger l'utilisateur vers la page de ressources protégées
    header('Location: resource.php');
    exit;
} else {
    echo "Erreur lors de l'obtention du jeton d'accès.";
    exit;
}
?>
