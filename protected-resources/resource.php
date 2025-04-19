<?php
require 'config.php';

// Vérifie que le jeton est fourni
if (!isset($_GET['access_token'])) {
    http_response_code(401);
    echo "Erreur : Jeton d'accès manquant.";
    exit;
}

$access_token = $_GET['access_token'];

try {
    // Connexion à la base de données MySQL
    $db = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Vérifier si le jeton d'accès est valide et non expiré
    $stmt = $db->prepare("SELECT * FROM access_tokens WHERE access_token = :token AND expires > NOW()");
    $stmt->execute(['token' => $access_token]);

    if ($stmt->fetch()) {
        // Jeton valide → afficher la ressource protégée
        echo "<h2>Jeton valide. Accès à la ressource :</h2><pre>";
        echo file_get_contents(RESOURCE_FILE);
        echo "</pre>";
    } else {
        http_response_code(403);
        echo "Jeton invalide ou expiré.";
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo "Erreur base de données : " . $e->getMessage();
}
