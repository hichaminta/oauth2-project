<?php
include 'config.php';

session_start();

if (!isset($_SESSION['access_token'])) {
    die('Jeton d\'accès manquant.');
}

$access_token = $_SESSION['access_token'];  // Récupérer le jeton d'accès

// Faire une requête pour accéder à la ressource protégée
$response = file_get_contents(RESOURCE_URL . '?access_token=' . $access_token);

// Afficher la ressource
echo "<h2>Ressource protégée :</h2>";
echo "<pre>" . $response . "</pre>";
?>
