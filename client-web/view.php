<?php
session_start();
if (!isset($_SESSION['access_token'])) {
    die("Non authentifié.");
}
if (!isset($_SESSION['token_created']) || !isset($_SESSION['expires_in'])) {
    die("Erreur : Information sur le token manquante.");
}
if (time() > $_SESSION['token_created'] + $_SESSION['expires_in']) {

    header("Location: logout.php");
    exit();
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fichiers de l'utilisateur</title>
    <link rel="stylesheet" href="css/view.css"> <!-- Inclusion du fichier CSS -->
</head>
<body>
<?php
$resource_url = "http://localhost/oauth2-project/protected-resources/resource.php?access_token=" . $_SESSION['access_token'];
$response = file_get_contents($resource_url);
$data = json_decode($response, true);

echo "<h2>Fichiers de l'utilisateur</h2>";
if (isset($data['error'])) {
    echo "<p>Erreur : " . htmlspecialchars($data['error']) . "</p>";
} elseif (isset($data['files']) && is_array($data['files'])) {
    echo "<ul>";
    foreach ($data['files'] as $file) {
        // Afficher les fichiers avec un lien pour le téléchargement
        echo "<li>" . htmlspecialchars($file['name']) . " (Taille: " . htmlspecialchars($file['size']) . ") ";
        echo "<a href='" . htmlspecialchars($file['url']) . "'>Télécharger</a></li>";
    }
    echo "</ul>";
} else {
    echo "<p>Aucun fichier disponible ou erreur de données.</p>";
}
?>
        <a href="logout.php" class="logout-btn">Déconnexion</a>

</body>
</html>