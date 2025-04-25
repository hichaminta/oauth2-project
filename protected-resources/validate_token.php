<?php

function validate_token($access_token) {
    try {
        // Connexion à la base de données MySQL pour vérifier le jeton
        $db = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Vérifier si le jeton est valide et non expiré
        $stmt = $db->prepare("SELECT * FROM access_tokens WHERE access_token = :token AND expires > NOW()");
        $stmt->execute(['token' => $access_token]);

        // Retourner true si le jeton est valide, sinon false
        if ($stmt->fetch()) {
            return true; // Jeton valide
        } else {
            return false; // Jeton invalide ou expiré
        }
    } catch (PDOException $e) {
        // En cas d'erreur avec la base de données, retourner un code d'erreur
        http_response_code(500);
        echo json_encode(['error' => 'Erreur base de données : ' . $e->getMessage()]);
        exit;
    }
}
?>