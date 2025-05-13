<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Affichage Données Blockchain</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .token-container {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            border: 1px solid #dee2e6;
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
        }
        .token-header {
            background-color: #f8f9fa;
            padding: 12px;
            border-radius: 5px;
            font-weight: bold;
            margin-bottom: 10px;
            border-left: 4px solid #0d6efd;
        }
        table {
            width: 100%;
        }
        th, td {
            padding: 8px 12px;
            vertical-align: middle;
        }
        th {
            background-color: #f1f1f1;
            width: 200px;
            text-align: left;
        }
        .json-raw {
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            font-family: monospace;
            font-size: 0.9em;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h1 class="text-center mb-4">Données Blockchain MultiChain</h1>

        <?php
        $url = 'http://localhost/oauth2-project/protected-resources/blokchaine_get_api.php';
        $response = @file_get_contents($url);

        if ($response === false) {
            echo '<div class="alert alert-danger">Erreur lors de la récupération des données.</div>';
        } else {
            $data = json_decode($response, true);
            $items = is_array($data) ? $data : [$data];

            if (empty($items) || (isset($items[0]['status']) && $items[0]['status'] === 'not_found')) {
                echo '<div class="alert alert-warning text-center">Aucune donnée trouvée dans la blockchain.</div>';
            } else {
                foreach ($items as $index => $item) {
                    $decoded = $item['decoded_data'] ?? [];

                    echo '<div class="token-container">';
                    echo '<div class="token-header">Token #' . ($index + 1) . '</div>';
                    echo '<table class="table table-bordered table-striped">';
                    
                    if (!empty($decoded)) {
                        foreach ($decoded as $key => $value) {
                            echo '<tr><th>' . htmlspecialchars(ucfirst(str_replace('_', ' ', $key))) . '</th><td>' . htmlspecialchars($value) . '</td></tr>';
                        }
                    } else {
                        echo '<tr><td colspan="2">Aucune information décodée disponible.</td></tr>';
                    }

                    echo '</table>';

                    echo '<div class="json-raw">';
                    echo htmlspecialchars(json_encode($item, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                    echo '</div>';
                    echo '</div>';
                }
            }
        }
        ?>
    </div>
</body>
</html>
