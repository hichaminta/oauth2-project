<?php
// config.php contient la configuration pour la vérification du jeton
include 'config.php';  // Charger la configuration de la base de données

// Vérifier si le jeton d'accès est passé dans la requête
if (!isset($_GET['access_token'])) {
    http_response_code(401);
    echo "Erreur : Jeton d'accès manquant.";
    exit;
}

$access_token = $_GET['access_token'];

try {
    // Connexion à la base de données MySQL pour vérifier le jeton
    $db = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Vérifier si le jeton est valide et non expiré
    $stmt = $db->prepare("SELECT * FROM access_tokens WHERE access_token = :token AND expires > NOW()");
    $stmt->execute(['token' => $access_token]);

    if ($stmt->fetch()) {
        // Jeton valide → lister les fichiers dans le dossier 'ressources/'
        $directory = 'ressources/'; // Dossier contenant les fichiers

        // Vérifier si le dossier existe
        if (!is_dir($directory)) {
            http_response_code(500);
            echo "Erreur : Le dossier 'ressources/' n'existe pas.";
            exit;
        }

        $files = scandir($directory);

        // Enlever les "." et ".." qui sont présents dans le retour de scandir
        $files = array_diff($files, array('.', '..'));

        // Créer un tableau pour afficher les fichiers avec un lien vers `access_file.php`
        $file_data = [];
        foreach ($files as $file) {
            // Générer un lien vers `access_file.php` pour chaque fichier
            $file_data[] = [
                'name' => $file,
                'size' => filesize($directory . $file),
                'url'  => "http://localhost/oauth2-project/protected-resources/access_file.php?access_token=" . urlencode($access_token) . "&file=" . urlencode($file)
            ];
        }

        // Retourner les fichiers sous forme de JSON
        echo json_encode(['files' => $file_data]);
    } else {
        http_response_code(403);
        echo json_encode(['error' => 'Jeton invalide ou expiré.']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur base de données : ' . $e->getMessage()]);
}
?>