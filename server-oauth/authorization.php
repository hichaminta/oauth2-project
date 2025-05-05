<?php
require_once 'database.php';
session_start();

// Liste des scopes disponibles et leurs descriptions
$available_scopes = [
    'read' => 'Voir la liste des fichiers autorisés',
    'write' => 'Ajouter de nouveaux fichiers',
    'admin' => 'Gérer les permissions des fichiers'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérifier les identifiants
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $requested_scopes = isset($_POST['scopes']) ? $_POST['scopes'] : ['read'];
    
    // Vérification des identifiants
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND password = ?");
    $stmt->execute([$username, $password]);
    $user = $stmt->fetch();

    if ($user) {
        $_SESSION['user_id'] = $user['id'];

        // Récupération des paramètres de l'URL
        $client_id = $_GET['client_id'] ?? '';
        $redirect_uri = $_GET['redirect_uri'] ?? '';
        $response_type = $_GET['response_type'] ?? '';
        
        // Valider les paramètres requis
        if (empty($client_id) || empty($redirect_uri) || $response_type !== 'code') {
            die("Paramètres de requête invalides.");
        }

        // Vérifier si le client_id existe
        $stmt = $pdo->prepare("SELECT * FROM clients WHERE client_id = ?");
        $stmt->execute([$client_id]);
        $client = $stmt->fetch();
        
        if (!$client) {
            die("Client non autorisé.");
        }

        // Convertir les scopes sélectionnés en chaîne
        $scope = implode(' ', $requested_scopes);
        
        // Générer le code d'autorisation
        $code = bin2hex(random_bytes(16));
        $expires = date('Y-m-d H:i:s', time() + 300); // 5 minutes d'expiration

        // Enregistrer le code d'autorisation
        $stmt = $pdo->prepare("INSERT INTO authorization_codes (code, client_id, user_id, redirect_uri, expires, scope) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$code, $client_id, $user['id'], $redirect_uri, $expires, $scope]);

        // Rediriger vers l'URI de redirection avec le code
        header("Location: $redirect_uri?code=$code");
        exit;
    } else {
        $error_message = "Identifiants incorrects.";
    }
} else {
    // Valider les paramètres requis dans la requête GET
    $client_id = $_GET['client_id'] ?? '';
    $redirect_uri = $_GET['redirect_uri'] ?? '';
    $response_type = $_GET['response_type'] ?? '';
    $scope = $_GET['scope'] ?? 'read';
    
    if (empty($client_id) || empty($redirect_uri) || $response_type !== 'code') {
        die("Paramètres de requête invalides.");
    }

    // Vérifier si le client_id existe
    $stmt = $pdo->prepare("SELECT * FROM clients WHERE client_id = ?");
    $stmt->execute([$client_id]);
    $client = $stmt->fetch();
    
    if (!$client) {
        die("Client non autorisé.");
    }
    
    // Convertir la chaîne de scope en tableau pour l'affichage
    $requested_scopes = explode(' ', $scope);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion SecureAuth</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .scope-container {
            margin: 20px 0;
            text-align: left;
        }
        .scope-option {
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Connexion SecureAuth</h2>
        
        <form method="POST">
            <input type="text" name="username" placeholder="Nom d'utilisateur" required>
            <input type="password" name="password" placeholder="Mot de passe" required>
            
            <div class="scope-container">
                <h3>Permissions demandées:</h3>
                <?php foreach ($available_scopes as $scope_name => $scope_desc) : ?>
                    <div class="scope-option">
                        <input type="checkbox" name="scopes[]" id="scope-<?= $scope_name ?>" 
                               value="<?= $scope_name ?>" 
                               <?= in_array($scope_name, $requested_scopes) ? 'checked' : '' ?>>
                        <label for="scope-<?= $scope_name ?>"><?= $scope_desc ?></label>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <button type="submit">Se connecter et autoriser</button>
        </form>
        
        <?php if (isset($error_message)): ?>
            <p class="error"><?= htmlspecialchars($error_message) ?></p>
        <?php endif; ?>
    </div>
</body>
</html>