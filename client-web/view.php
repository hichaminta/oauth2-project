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

// Récupérer les informations sur les fichiers
$resource_url = "http://localhost/oauth2-project/protected-resources/resource.php?access_token=" . urlencode($_SESSION['access_token']);
$response = file_get_contents($resource_url);
$data = json_decode($response, true);

// Vérifier si l'utilisateur a le droit d'écriture
$can_write = isset($data['user']['can_write']) && $data['user']['can_write'];
$scopes = isset($data['user']['scopes']) ? $data['user']['scopes'] : [];
$is_admin = in_array('admin', $scopes);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fichiers de l'utilisateur</title>
    <link rel="stylesheet" href="css/view.css">
    <style>
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .file-list {
            margin-top: 20px;
        }
        .file-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        .file-info {
            flex-grow: 1;
        }
        .file-actions {
            display: flex;
            gap: 10px;
        }
        .upload-form {
            margin: 20px 0;
            padding: 15px;
            background-color: #f5f5f5;
            border-radius: 5px;
        }
        .upload-form h3 {
            margin-top: 0;
        }
        .btn {
            padding: 8px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
        }
        .btn-primary {
            background-color: #4CAF50;
            color: white;
        }
        .btn-download {
            background-color: #2196F3;
            color: white;
        }
        .btn-danger {
            background-color: #f44336;
            color: white;
        }
        .logout-btn {
            position: absolute;
            top: 20px;
            right: 20px;
            padding: 8px 16px;
            background-color: #f44336;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 12px;
            margin-left: 5px;
        }
        .badge-read {
            background-color: #2196F3;
            color: white;
        }
        .badge-write {
            background-color: #4CAF50;
            color: white;
        }
        .badge-admin {
            background-color: #9C27B0;
            color: white;
        }
        .no-files {
            text-align: center;
            padding: 30px;
            color: #666;
        }
        .error-message {
            color: #f44336;
            padding: 10px;
            margin: 10px 0;
            background-color: #ffebee;
            border-radius: 4px;
        }
        .success-message {
            color: #4CAF50;
            padding: 10px;
            margin: 10px 0;
            background-color: #e8f5e9;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Gestion des fichiers</h1>
        
        <div class="user-info">
            <p>
                Vos autorisations : 
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
            
            <?php if (isset($data['error'])): ?>
                <p class="error"><?= htmlspecialchars($data['error']) ?></p>
            <?php elseif (isset($data['files']) && !empty($data['files'])): ?>
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
    // Fonction pour formater la taille en KB, MB, etc.
    function formatFileSize(bytes) {
        if (bytes < 1024) return bytes + " octets";
        else if (bytes < 1048576) return (bytes / 1024).toFixed(2) + " KB";
        else return (bytes / 1048576).toFixed(2) + " MB";
    }
    
    // Gestion du téléversement de fichier
    document.getElementById('uploadForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const statusDiv = document.getElementById('uploadStatus');
        
        statusDiv.innerHTML = '<p class="info">Téléversement en cours...</p>';
        
        // Utiliser le token de session actuel au lieu d'un token en dur
        fetch('http://localhost/oauth2-project/protected-resources/upload_file.php?access_token=<?= urlencode($_SESSION['access_token']) ?>', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`Erreur HTTP: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                statusDiv.innerHTML = '<p class="success-message">Fichier téléversé avec succès!</p>';
                // Recharger la page pour afficher le nouveau fichier
                setTimeout(() => location.reload(), 1000);
            } else {
                statusDiv.innerHTML = '<p class="error-message">Erreur: ' + (data.error || 'Une erreur inconnue est survenue') + '</p>';
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            statusDiv.innerHTML = '<p class="error-message">Erreur lors du téléversement: ' + error.message + '</p>';
        });
    });
    
    // Fonction pour supprimer un fichier (administrateur uniquement)
    function deleteFile(fileId) {
        if (!confirm('Êtes-vous sûr de vouloir supprimer ce fichier?')) return;
        
        fetch('http://localhost/oauth2-project/protected-resources/delete.php?access_token=<?= urlencode($_SESSION['access_token']) ?>&file_id=' + fileId, {
            method: 'DELETE'
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`Erreur HTTP: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                alert('Fichier supprimé avec succès!');
                location.reload();
            } else {
                alert('Erreur: ' + (data.error || 'Une erreur inconnue est survenue'));
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Erreur lors de la suppression: ' + error.message);
        });
    }
    </script>
<?php
// Fonction pour formater la taille des fichiers
function formatSize($size) {
    $units = ['octets', 'KB', 'MB', 'GB', 'TB'];
    $power = $size > 0 ? floor(log($size, 1024)) : 0;
    return number_format($size / pow(1024, $power), 2, '.', ',') . ' ' . $units[$power];
}
?>
</body>
</html>