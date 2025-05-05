<?php
session_start();

// Si l'utilisateur est déjà authentifié, rediriger vers view.php
if (isset($_SESSION['access_token'])) {
    header("Location: view.php");
    exit;
}

// Paramètres pour la construction de l'URL d'autorisation
$client_id = "quickview-client";
$redirect_uri = "http://localhost/oauth2-project/client-web/callback.php";
$auth_url = "http://localhost/oauth2-project/server-oauth/authorization.php";

// Définir les scopes disponibles
$available_scopes = [
    'read' => 'Lecture des fichiers',
    'write' => 'Ajout/modification de fichiers',
    'admin' => 'Accès administrateur complet'
];

// Traiter les choix de scope
$selected_scopes = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($available_scopes as $scope_key => $scope_desc) {
        if (isset($_POST['scope_' . $scope_key])) {
            $selected_scopes[] = $scope_key;
        }
    }
    
    // Si aucun scope n'est sélectionné, utiliser 'read' par défaut
    if (empty($selected_scopes)) {
        $selected_scopes[] = 'read';
    }
    
    // Construire la chaîne de scope
    $scope = implode(' ', $selected_scopes);
    
    // Construire l'URL d'autorisation
    $auth_request_url = $auth_url . "?" . http_build_query([
        'client_id' => $client_id,
        'redirect_uri' => $redirect_uri,
        'response_type' => 'code',
        'scope' => $scope
    ]);
    
    // Rediriger vers l'URL d'autorisation
    header("Location: " . $auth_request_url);
    exit;
} else {
    // Par défaut, sélectionner uniquement 'read'
    $selected_scopes = ['read'];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accès aux fichiers</title>
    <link rel="stylesheet" href="css/index.css">
    <style>
        .scopes-container {
            margin: 20px 0;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 8px;
        }
        
        .scope-option {
            margin-bottom: 10px;
        }
        
        .scope-option label {
            display: flex;
            align-items: center;
            cursor: pointer;
        }
        
        .scope-option input[type="checkbox"] {
            margin-right: 10px;
        }
        
        .scope-name {
            font-weight: bold;
            margin-right: 5px;
        }
        
        .scope-description {
            font-size: 0.9em;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Bienvenue</h1>
        <p>Accédez à vos fichiers sécurisés</p>
        
        <form method="POST" action="">
            <div class="scopes-container">
                <h3>Choisissez les permissions</h3>
                
                <?php foreach ($available_scopes as $scope_key => $scope_desc): ?>
                    <div class="scope-option">
                        <label>
                            <input type="checkbox" name="scope_<?= $scope_key ?>" 
                                <?= in_array($scope_key, $selected_scopes) ? 'checked' : '' ?>>
                            <span class="scope-name"><?= ucfirst($scope_key) ?>:</span>
                            <span class="scope-description"><?= $scope_desc ?></span>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="login-button-container">
                <button type="submit" class="login-button">
                    Login avec SecureAuth
                </button>
            </div>
        </form>
    </div>
</body>
</html>