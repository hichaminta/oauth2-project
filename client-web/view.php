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

// Récupération des informations sur le token
$validation_url = "http://localhost/oauth2-project/server-oauth/validate_token.php?access_token=" . urlencode($_SESSION['access_token']);
$validation_response = @file_get_contents($validation_url);
$token_data = json_decode($validation_response, true);

// Vérification des scopes disponibles
$scopes = explode(' ', $token_data['scope'] ?? 'read');
$can_upload = in_array('write', $scopes) || in_array('admin', $scopes);
$is_admin = in_array('admin', $scopes);

// Gestion du téléchargement de fichier
$upload_message = '';
$upload_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $can_upload && isset($_FILES['file'])) {
    $ch = curl_init("http://localhost/oauth2-project/protected-resources/upload_file.php?access_token=" . urlencode($_SESSION['access_token']));
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, [
        'file' => new CURLFile($_FILES['file']['tmp_name'], $_FILES['file']['type'], $_FILES['file']['name'])
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        $upload_error = "Erreur lors du téléchargement: " . $error;
    } else {
        $result = json_decode($response, true);
        if (isset($result['success'])) {
            $upload_message = "Fichier téléchargé avec succès!";
        } else {
            $upload_error = $result['error'] ?? "Erreur inconnue lors du téléchargement.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fichiers de l'utilisateur</title>
    <link rel="stylesheet" href="css/view.css">
    <style>
        .upload-section {
            margin: 20px 0;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .success-message {
            color: green;
            font-weight: bold;
        }
        .error-message {
            color: red;
            font-weight: bold;
        }
        .permissions-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 12px;
            margin-left: 10px;
        }
        .admin-badge {
            background-color: #dc3545;
            color: white;
        }
        .write-badge {
            background-color: #ffc107;
            color: black;
        }
        .read-badge {
            background-color: #28a745;
            color: white;
        }
    </style>
</head>
<body>
    <h2>Fichiers de l'utilisateur</h2>
    
    <div>
        <span>Permissions: </span>
        <?php foreach ($scopes as $scope): ?>
            <span class="permissions-badge <?= $scope ?>-badge"><?= htmlspecialchars($scope) ?></span>
        <?php endforeach; ?>
    </div>
    
    <?php if ($can_upload): ?>
    <div class="upload-section">
        <h3>Télécharger un fichier</h3>
        <form method="POST" enctype="multipart/form-data">
            <input type="file" name="file" required>
            <button type="submit">Télécharger</button>
        </form>
        <?php if ($upload_message): ?>
            <p class="success-message"><?= htmlspecialchars($upload_message) ?></p>
        <?php endif; ?>
        <?php if ($upload_error): ?>
            <p class="error-message"><?= htmlspecialchars($upload_error) ?></p>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <h3>Liste des fichiers</h3>
    <?php
    $resource_url = "http://localhost/oauth2-project/protected-resources/resource.php?access_token=" . $_SESSION['access_token'];
    $response = file_get_contents($resource_url);
    $data = json_decode($response, true);

    if (isset($data['error'])) {
        echo "<p class='error-message'>Erreur : " . htmlspecialchars($data['error']) . "</p>";
    } elseif (isset($data['files']) && is_array($data['files'])) {
        if (count($data['files']) > 0) {
            echo "<ul>";
            foreach ($data['files'] as $file) {
                echo "<li>" . htmlspecialchars($file['name']) . " (Taille: " . htmlspecialchars($file['size']) . " octets) ";
                echo "<a href='" . htmlspecialchars($file['url']) . "'>Télécharger</a></li>";
            }
            echo "</ul>";
        } else {
            echo "<p>Aucun fichier accessible avec vos permissions.</p>";
        }
    } else {
        echo "<p>Aucun fichier disponible ou erreur de données.</p>";
    }
    ?>
    
    <?php if ($is_admin): ?>
        <a href="admin.php" class="logout-btn" style="background-color: #007bff; margin-right: 10px;">Panneau d'administration</a>
    <?php endif; ?>
    
    <a href="logout.php" class="logout-btn">Déconnexion</a>
</body>
</html>