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
    <title>Logs d'accès</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 6px 10px;
            border: 1px solid #ccc;
        }
        th {
            background-color: #eee;
        }
    </style>
</head>
<body>
    <h2>Logs de connexion et d'accès aux fichiers</h2>
    <table>
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

    <script>
        const apiUrl = "http://localhost/oauth2-project/protected-resources/admin_log_api.php?access_token=<?= $_SESSION['access_token'] ?>";

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
                        <td>${log.timestamp}</td>
                        <td>${log.ip_address}</td>
                        <td>${log.user_id}</td>
                        <td>${log.file_id}</td>
                        <td>${log.filename}</td>
                        <td>${log.action}</td>
                        <td>${log.success ? 'Oui' : 'Non'}</td>
                        <td>${log.message}</td>
                    </tr>`;
                    tbody.insertAdjacentHTML('beforeend', row);
                });
            })
            .catch(error => {
                alert("Erreur lors du chargement des logs : " + error.message);
            });
    </script>
</body>
</html>
