<?php
session_start();
if (!isset($_SESSION['access_token'])) {
    die("Non authentifié.");
}

$resource_url = "http://localhost/oauth2-project/protected-resources/resource.php?access_token=" . $_SESSION['access_token'];
$response = file_get_contents($resource_url);
$data = json_decode($response, true);

echo "<h2>Fichiers de l'utilisateur</h2>";
if (isset($data['files'])) {
    echo "<ul>";
    foreach ($data['files'] as $file) {
        echo "<li>$file</li>";
    }
    echo "</ul>";
} else {
    echo "Erreur : " . $data['error'];
}
?>
