<?php
session_start();
if (!isset($_SESSION['access_token'])) {
    die("Non authentifié.");
}
echo $_SESSION['access_token'];

$resource_url = "http://localhost/oauth2-project/protected-resources/resource.php?access_token=" . $_SESSION['access_token'];
$response = file_get_contents($resource_url);
$data = json_decode($response, true);

// Vérifiez si la réponse contient des erreurs avant d'essayer d'accéder aux fichiers
echo "<h2>Fichiers de l'utilisateur</h2>";
if (isset($data['error'])) {
    echo "Erreur : " . $data['error'];
} elseif (isset($data['files']) && is_array($data['files'])) {
    echo "<ul>";
    foreach ($data['files'] as $file) {
        // Afficher les fichiers avec un lien pour le téléchargement
        echo "<li>" . htmlspecialchars($file['name']) . " (Taille: " . htmlspecialchars($file['size']) . ") ";
        echo "<a href='" . htmlspecialchars($file['url']) . "'>Télécharger</a></li>";
    }
    echo "</ul>";
} else {
    echo "Aucun fichier disponible ou erreur de données.";
}
?>
