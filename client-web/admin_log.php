<?php
session_start();
if (!isset($_SESSION['access_token'])) {
    header("Location: index.php");
    exit();
}

// Vérifier si le token est encore valide
if (!isset($_SESSION['token_created']) || !isset($_SESSION['expires_in'])) {
    header("Location: logout.php");
    exit();
}
if (time() > $_SESSION['token_created'] + $_SESSION['expires_in']) {
    header("Location: view.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QuickView - Logs d'accès</title>
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>QuickView</h1>
            <nav class="nav-menu">
                <a href="view.php" class="nav-link">Mes fichiers</a>
                <a href="admin_permissions.php" class="nav-link">Permissions</a>
                <a href="admin_log.php" class="nav-link active">Logs</a>
                <a href="logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
            </nav>
        </div>
    </div>

    <div class="container">
        <div class="card" style="margin-top: 2rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <h2><i class="fas fa-history"></i> Logs de connexion et d'accès aux fichiers</h2>
                <div id="loading-indicator" style="display: none;">
                    <i class="fas fa-spinner fa-spin"></i> Chargement...
                </div>
            </div>

            <div style="overflow-x: auto;">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Horodatage</th>
                            <th>IP</th>
                            <th>User ID</th>
                            <th>Fichier ID</th>
                            <th>Nom de fichier</th>
                            <th>Action</th>
                            <th>Succès</th>
                            <th>Message</th>
                        </tr>
                    </thead>
                    <tbody id="log-rows">
                        <!-- Données seront insérées ici -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        const loadingIndicator = document.getElementById('loading-indicator');
        const apiUrl = "http://localhost/oauth2-project/protected-resources/admin_log_api.php?access_token=<?= $_SESSION['access_token'] ?>";

        loadingIndicator.style.display = 'block';

        fetch(apiUrl)
            .then(response => {
                if (!response.ok) {
                    throw new Error("Erreur API");
                }
                return response.json();
            })
            .then(data => {
                const tbody = document.getElementById('log-rows');
                data.forEach(log => {
                    const row = `<tr>
                        <td>${log.id}</td>
                        <td><i class="fas fa-clock"></i> ${log.timestamp}</td>
                        <td><i class="fas fa-network-wired"></i> ${log.ip_address}</td>
                        <td><i class="fas fa-user"></i> ${log.user_id}</td>
                        <td>${log.file_id}</td>
                        <td><i class="fas fa-file"></i> ${log.filename}</td>
                        <td><span class="btn btn-secondary" style="font-size: 0.8rem; padding: 0.3rem 0.8rem;">${log.action}</span></td>
                        <td>
                            ${log.success 
                                ? '<i class="fas fa-check" style="color: green;"></i>' 
                                : '<i class="fas fa-times" style="color: red;"></i>'
                            }
                        </td>
                        <td>${log.message}</td>
                    </tr>`;
                    tbody.insertAdjacentHTML('beforeend', row);
                });
                loadingIndicator.style.display = 'none';
            })
            .catch(error => {
                loadingIndicator.style.display = 'none';
                const errorMessage = `
                    <div class="alert alert-error" style="margin: 1rem;">
                        <i class="fas fa-exclamation-circle"></i> 
                        Erreur lors du chargement des logs : ${error.message}
                    </div>
                `;
                document.querySelector('.card').insertAdjacentHTML('beforeend', errorMessage);
            });
    </script>
</body>
</html>
