<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QuickView - Données Blockchain</title>
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .token-container {
            background-color: var(--white);
            border-radius: 8px;
            padding: 1.5rem;
            margin: 1.5rem 0;
            border: 1px solid var(--light-gray);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-top: 3px solid var(--primary-color);
        }
        .token-header {
            background-color: var(--light-gray);
            padding: 0.8rem;
            border-radius: 5px;
            font-weight: bold;
            margin-bottom: 1rem;
            border-left: 4px solid var(--primary-color);
            display: flex;
            align-items: center;
        }
        .json-raw {
            background-color: var(--light-gray);
            padding: 1rem;
            border-radius: 5px;
            font-family: monospace;
            font-size: 0.9em;
            overflow-x: auto;
            margin-top: 1rem;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>QuickView</h1>
            <nav class="nav-menu">
                <a href="view.php" class="nav-link">Mes fichiers</a>
                <a href="admin_permissions.php" class="nav-link">Permissions</a>
                <a href="admin_log.php" class="nav-link">Logs</a>
                <a href="blochaine_adm_token.php" class="nav-link active">Blockchain</a>
                <a href="logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
            </nav>
        </div>
    </div>

    <div class="container">
        <div class="card" style="margin-top: 2rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <h2><i class="fas fa-link"></i> Données Blockchain MultiChain</h2>
                <div id="loading-indicator">
                    <i class="fas fa-spinner fa-spin"></i> Chargement...
                </div>
            </div>

            <?php
            $url = 'http://localhost/oauth2-project/protected-resources/blokchaine_get_api.php';
            $response = @file_get_contents($url);

            if ($response === false) {
                echo '<div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i> Erreur lors de la récupération des données.
                      </div>';
            } else {
                $data = json_decode($response, true);
                $items = is_array($data) ? $data : [$data];

                if (empty($items) || (isset($items[0]['status']) && $items[0]['status'] === 'not_found')) {
                    echo '<div class="alert" style="text-align: center; padding: 2rem;">
                            <i class="fas fa-info-circle" style="font-size: 2rem; color: var(--primary-color);"></i>
                            <p style="margin-top: 1rem;">Aucune donnée trouvée dans la blockchain.</p>
                          </div>';
                } else {
                    foreach ($items as $index => $item) {
                        $decoded = $item['decoded_data'] ?? [];

                        echo '<div class="token-container">';
                        echo '<div class="token-header"><i class="fas fa-cubes"></i> Token #' . ($index + 1) . '</div>';
                        
                        if (!empty($decoded)) {
                            echo '<table class="table">';
                            echo '<thead>
                                    <tr>
                                        <th>Propriété</th>
                                        <th>Valeur</th>
                                    </tr>
                                  </thead>';
                            echo '<tbody>';
                            foreach ($decoded as $key => $value) {
                                echo '<tr>
                                        <td><i class="fas fa-key"></i> ' . htmlspecialchars(ucfirst(str_replace('_', ' ', $key))) . '</td>
                                        <td>' . htmlspecialchars($value) . '</td>
                                      </tr>';
                            }
                            echo '</tbody>';
                            echo '</table>';
                        } else {
                            echo '<div class="alert" style="text-align: center; padding: 1rem;">
                                    <i class="fas fa-info-circle"></i> Aucune information décodée disponible.
                                  </div>';
                        }

                        echo '<div class="json-raw">';
                        echo '<div style="margin-bottom: 0.5rem;"><i class="fas fa-code"></i> Données JSON brutes :</div>';
                        echo htmlspecialchars(json_encode($item, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                        echo '</div>';
                        echo '</div>';
                    }
                }
            }
            ?>
        </div>
    </div>

    <script>
        // Cacher l'indicateur de chargement une fois que les données sont chargées
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('loading-indicator').style.display = 'none';
        });
    </script>
</body>
</html>
