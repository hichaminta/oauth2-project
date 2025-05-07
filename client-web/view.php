<?php
session_start();
include_once 'variable.php';

// Masquer les erreurs en production
ini_set('display_errors', 0);
error_reporting(0);

// Vérification de l'authentification
if (!isset($_SESSION['access_token']) || !isset($_SESSION['token_created']) || !isset($_SESSION['expires_in'])) {
    header("Location: logout.php");
    exit();
}

// Vérification de l'expiration du token
if (time() > $_SESSION['token_created'] + $_SESSION['expires_in']) {
    header("Location: logout.php");
    exit();
}

// Récupérer les informations sur les fichiers
$resource_url = $domainenameprressources . "resource.php?access_token=" . urlencode($_SESSION['access_token']);
$response = file_get_contents($resource_url);
$data = json_decode($response, true);

// Vérification des permissions
$can_write = isset($data['user']['can_write']) && $data['user']['can_write'];
$scopes = isset($data['user']['scopes']) ? $data['user']['scopes'] : [];
$is_admin = in_array('admin', $scopes);

// Fonction pour formater la taille des fichiers
function formatSize($size) {
    $units = ['octets', 'KB', 'MB', 'GB', 'TB'];
    $power = $size > 0 ? floor(log($size, 1024)) : 0;
    return number_format($size / pow(1024, $power), 2, '.', ',') . ' ' . $units[$power];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fichiers de l'utilisateur</title>
    <link rel="stylesheet" href="css/view.css">
</head>
<body>
    <div class="container">
        <h1>Gestion des fichiers</h1>

        <div class="user-info">
            <p>Vos autorisations : 
                <?php foreach ($scopes as $scope): ?>
                    <span class="badge badge-<?= htmlspecialchars($scope) ?>"><?= htmlspecialchars(ucfirst($scope)) ?></span>
                <?php endforeach; ?>
            </p>
        </div>

        <?php if ($can_write): ?>
        <div class="upload-form">
            <h3>Téléverser un nouveau fichier</h3>
            <form id="uploadForm" enctype="multipart/form-data">
                <input type="file" name="file" required>
                <button type="submit" class="btn btn-primary">Téléverser</button>
            </form>
            <div id="uploadStatus"></div>
        </div>
        <?php endif; ?>

        <div class="file-list">
            <h2>Fichiers disponibles</h2>

            <?php if (isset($data['files']) && !empty($data['files'])): ?>
                <?php foreach ($data['files'] as $file): ?>
                <div class="file-item">
                    <div class="file-info">
                        <strong><?= htmlspecialchars($file['name']) ?></strong>
                        <span>(<?= htmlspecialchars(formatSize($file['size'])) ?>)</span>
                    </div>
                    <div class="file-actions">
                        <a href="<?= htmlspecialchars($file['url']) ?>" class="btn btn-download">Télécharger</a>
                        <?php if ($is_admin): ?>
                        <button class="btn btn-danger" onclick="deleteFile(<?= (int)$file['id'] ?>)">Supprimer</button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="no-files">Aucun fichier disponible</p>
            <?php endif; ?>
        </div>

        <?php if ($is_admin): ?>
        <div class="admin-section">
            <h2>Administration des permissions</h2>
            <p>En tant qu'administrateur, vous pouvez gérer les permissions des utilisateurs.</p>
            <a href="admin_permissions.php" class="btn btn-primary">Gérer les permissions</a>
        </div>
        <?php endif; ?>

        <a href="logout.php" class="logout-btn">Déconnexion</a>
    </div>

    <script>
    // Gestion du téléversement de fichier
    document.getElementById('uploadForm')?.addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        const statusDiv = document.getElementById('uploadStatus');
        statusDiv.innerHTML = '<p class="info">Téléversement en cours...</p>';

        fetch('http://localhost/oauth2-project/protected-resources/upload_file.php?access_token=<?= urlencode($_SESSION['access_token']) ?>', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                statusDiv.innerHTML = '<p class="success-message">Fichier téléversé avec succès!</p>';
                setTimeout(() => location.reload(), 1000);
            } else {
                statusDiv.innerHTML = '<p class="error-message">Erreur lors du téléversement.</p>';
            }
        })
        .catch(() => {
            statusDiv.innerHTML = '<p class="error-message">Erreur réseau lors du téléversement.</p>';
        });
    });

    // Suppression de fichier (admin uniquement)
    function deleteFile(fileId) {
        if (!confirm('Êtes-vous sûr de vouloir supprimer ce fichier?')) return;

        fetch('http://localhost/oauth2-project/protected-resources/delete.php?access_token=<?= urlencode($_SESSION['access_token']) ?>&file_id=' + fileId, {
            method: 'DELETE'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Erreur lors de la suppression.');
            }
        })
        .catch(() => {
            alert('Erreur réseau lors de la suppression.');
        });
    }
    </script>
</body>
</html>
