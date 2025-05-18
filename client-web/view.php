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
  // Appel à refresh.php avec méthode GET
   
// Vérification de l'expiration du token
if (time() > $_SESSION['token_created'] + $_SESSION['expires_in']) {


    if (isset($_SESSION['refresh_token'])) {

        // Afficher un message d'attente
     
        // Appel à refresh.php
        $url = $domainenameserverauth . "refresh.php?refresh_token=" . urlencode($_SESSION['refresh_token']) .
               "&client_id=" . urlencode('quickview-client') .
               "&client_secret=" . urlencode('secret123');
        
        $response = file_get_contents($url);
        $data = json_decode($response, true);

        if (isset($data['access_token'])) {
            $_SESSION['access_token'] = $data['access_token'];
            $_SESSION['token_created'] = time();
            $_SESSION['expires_in'] = $data['expires_in'];
            // Rediriger pour éviter double affichage
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        } else {
            header("Location: logout.php");
            exit();
        }

    } else {
        header("Location: logout.php");
        exit();
    }
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
    <title>QuickView - Gestion des fichiers</title>
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>QuickView</h1>
            <nav class="nav-menu">
                <a href="view.php" class="nav-link active">Mes fichiers</a>
                <?php if ($is_admin): ?>
                <a href="admin_permissions.php" class="nav-link">Permissions</a>
                <a href="admin_log.php" class="nav-link">Logs</a>
                <?php endif; ?>
                <a href="logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
            </nav>
        </div>
    </div>

    <div class="container">
        <div class="card" style="margin-top: 2rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <h2><i class="fas fa-folder-open"></i> Mes fichiers</h2>
                <div class="user-info">
                    <p>Permissions : 
                        <?php foreach ($scopes as $scope): ?>
                            <span class="btn btn-secondary" style="font-size: 0.8rem; padding: 0.3rem 0.8rem;">
                                <?= htmlspecialchars(ucfirst($scope)) ?>
                            </span>
                        <?php endforeach; ?>
                    </p>
                </div>
            </div>

            <?php if ($can_write): ?>
            <div class="card" style="background: var(--light-gray); margin-bottom: 2rem;">
                <h3><i class="fas fa-upload"></i> Téléverser un fichier</h3>
                <form id="uploadForm" enctype="multipart/form-data" style="margin-top: 1rem;">
                    <div class="form-group" style="display: flex; gap: 1rem; align-items: center;">
                        <input type="file" name="file" required class="form-input" style="flex: 1;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-cloud-upload-alt"></i> Téléverser
                        </button>
                    </div>
                </form>
                <div id="uploadStatus"></div>
            </div>
            <?php endif; ?>

            <div class="file-list">
                <?php if (isset($data['files']) && !empty($data['files'])): ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Nom du fichier</th>
                                <th>Taille</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data['files'] as $file): ?>
                            <tr>
                                <td>
                                    <i class="fas fa-file"></i>
                                    <?= htmlspecialchars($file['name']) ?>
                                </td>
                                <td><?= htmlspecialchars(formatSize($file['size'])) ?></td>
                                <td style="white-space: nowrap;">
                                    <a href="<?= htmlspecialchars($file['url']) ?>" class="btn btn-primary" style="margin-right: 0.5rem;">
                                        <i class="fas fa-download"></i> Télécharger
                                    </a>
                                    <?php if ($is_admin): ?>
                                    <button class="btn btn-secondary" onclick="deleteFile(<?= (int)$file['id'] ?>)">
                                        <i class="fas fa-trash"></i> Supprimer
                                    </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="alert" style="text-align: center; padding: 2rem;">
                        <i class="fas fa-info-circle" style="font-size: 2rem; color: var(--primary-color);"></i>
                        <p style="margin-top: 1rem;">Aucun fichier disponible</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
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
