<?php
session_start();
if (!isset($_SESSION['access_token'])) {
    header("Location: index.php");
    exit;
}
if (!isset($_SESSION['token_created']) || !isset($_SESSION['expires_in'])) {
    die("Erreur : Information sur le token manquante.");
}
if (time() > $_SESSION['token_created'] + $_SESSION['expires_in']) {
    header("Location: logout.php");
    exit();
}

// Valider le token pour récupérer les infos (notamment le scope)
$validate_url = "http://localhost/oauth2-project/server-oauth/validate_token.php?access_token=" . $_SESSION['access_token'];
$response = file_get_contents($validate_url);
$token_data = json_decode($response, true);

$scope = isset($token_data['scope']) ? $token_data['scope'] : 'read';
$scopes = explode(' ', $scope);
$has_read = in_array('read', $scopes) || in_array('admin', $scopes);
$has_write = in_array('write', $scopes) || in_array('admin', $scopes);
$has_admin = in_array('admin', $scopes);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fichiers de l'utilisateur</title>
    <link rel="stylesheet" href="css/view.css">
    <style>
        .permissions-badge {
            display: inline-block;
            padding: 3px 8px;
            margin: 2px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
        }
        .read { background-color: #e7f4ff; color: #0066cc; }
        .write { background-color: #e6f9e6; color: #006600; }
        .admin { background-color: #f9e6e6; color: #cc0000; }
        
        .file-actions button {
            padding: 5px 10px;
            margin-right: 5px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
        }
        .download-btn { background-color: #4CAF50; color: white; }
        .edit-btn { background-color: #2196F3; color: white; }
        .delete-btn { background-color: #f44336; color: white; }
        
        .permission-denied {
            opacity: 0.5;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Fichiers de l'utilisateur</h2>
        
        <div class="permissions-info">
            <h3>Vos permissions</h3>
            <div>
                <?php if ($has_read): ?>
                    <span class="permissions-badge read">Lecture</span>
                <?php endif; ?>
                <?php if ($has_write): ?>
                    <span class="permissions-badge write">Écriture</span>
                <?php endif; ?>
                <?php if ($has_admin): ?>
                    <span class="permissions-badge admin">Administration</span>
                <?php endif; ?>
            </div>
        </div>
        
        <?php
        $resource_url = "http://localhost/oauth2-project/protected-resources/resource.php?access_token=" . $_SESSION['access_token'];
        $response = file_get_contents($resource_url);
        $data = json_decode($response, true);

        if (isset($data['error'])) {
            echo "<div class='error-message'>";
            echo "<p>Erreur : " . htmlspecialchars($data['error']) . "</p>";
            echo "</div>";
        } elseif (isset($data['files']) && is_array($data['files'])) {
            echo "<table class='files-table'>";
            echo "<thead>";
            echo "<tr>";
            echo "<th>Nom</th>";
            echo "<th>Taille</th>";
            echo "<th>Permissions</th>";
            echo "<th>Actions</th>";
            echo "</tr>";
            echo "</thead>";
            echo "<tbody>";
            
            foreach ($data['files'] as $file) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($file['name']) . "</td>";
                echo "<td>" . htmlspecialchars(number_format($file['size'] / 1024, 2)) . " KB</td>";
                
                // Affichage des permissions spécifiques
                echo "<td>";
                if ($file['permissions']['read']) echo "<span class='permissions-badge read'>Lecture</span>";
                if ($file['permissions']['write']) echo "<span class='permissions-badge write'>Écriture</span>";
                if ($file['permissions']['delete']) echo "<span class='permissions-badge admin'>Suppression</span>";
                echo "</td>";
                
                // Actions
                echo "<td class='file-actions'>";
                
                // Télécharger - nécessite permission de lecture
                if ($file['permissions']['read']) {
                    echo "<a href='" . htmlspecialchars($file['url']) . "'>";
                    echo "<button class='download-btn'>Télécharger</button>";
                    echo "</a>";
                } else {
                    echo "<button class='download-btn permission-denied' disabled>Télécharger</button>";
                }
                
                // Éditer - nécessite permission d'écriture
                if ($has_write && $file['permissions']['write']) {
                    echo "<button class='edit-btn'>Éditer</button>";
                } else {
                    echo "<button class='edit-btn permission-denied' disabled>Éditer</button>";
                }
                
                // Supprimer - nécessite permission de suppression
                if ($has_admin && $file['permissions']['delete']) {
                    echo "<button class='delete-btn'>Supprimer</button>";
                } else {
                    echo "<button class='delete-btn permission-denied' disabled>Supprimer</button>";
                }
                
                echo "</td>";
                echo "</tr>";
            }
            
            echo "</tbody>";
            echo "</table>";
            
            // Bouton d'ajout de fichier - nécessite permission d'écriture
            if ($has_write) {
                echo "<div class='upload-section'>";
                echo "<h3>Ajouter un fichier</h3>";
                echo "<form action='upload.php' method='post' enctype='multipart/form-data'>";
                echo "<input type='file' name='file' required>";
                echo "<button type='submit'>Téléverser</button>";
                echo "</form>";
                echo "</div>";
            }
        } else {
            echo "<p>Aucun fichier disponible ou erreur de données.</p>";
        }
        ?>
        
        <a href="logout.php" class="logout-btn">Déconnexion</a>
    </div>
</body>
</html>