<?php
require_once 'database.php';
session_start();

// Vérifier les paramètres de requête OAuth2
if (!isset($_GET['client_id']) || !isset($_GET['redirect_uri']) || !isset($_GET['response_type'])) {
    die("Paramètres OAuth2 manquants.");
}

$client_id = $_GET['client_id'];
$redirect_uri = $_GET['redirect_uri'];
$response_type = $_GET['response_type'];
$scope = isset($_GET['scope']) ? $_GET['scope'] : 'read'; // Par défaut, uniquement 'read'

// Vérifier si le client existe
$stmt = $pdo->prepare("SELECT * FROM clients WHERE client_id = ? AND redirect_uri = ?");
$stmt->execute([$client_id, $redirect_uri]);
$client = $stmt->fetch();

if (!$client) {
    die("Client non autorisé.");
}

// Valider les scopes
$available_scopes = ['read', 'write', 'admin'];
$requested_scopes = explode(' ', $scope);
$valid_scopes = [];

foreach ($requested_scopes as $s) {
    if (in_array($s, $available_scopes)) {
        $valid_scopes[] = $s;
    }
}

// S'il n'y a pas au moins un scope valide, on accorde uniquement 'read'
if (empty($valid_scopes)) {
    $valid_scopes[] = 'read';
}
$scope = implode(' ', $valid_scopes);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérifier les identifiants
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND password = ?");
    $stmt->execute([$username, $password]);
    $user = $stmt->fetch();

    if ($user) {
        $_SESSION['user_id'] = $user['id'];

        // Rediriger avec le code d'autorisation
        $code = bin2hex(random_bytes(16));
        $expires = date('Y-m-d H:i:s', time() + 300);

        $stmt = $pdo->prepare("INSERT INTO authorization_codes (code, client_id, user_id, redirect_uri, expires, scope) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$code, $client_id, $user['id'], $redirect_uri, $expires, $scope]);

        header("Location: $redirect_uri?code=$code");
        exit;
    } else {
        $error_message = "Identifiants incorrects.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion OAuth</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <h2>Connexion SecureAuth</h2>
        
        <div class="authorization-info">
            <p>L'application <strong><?= htmlspecialchars($client_id) ?></strong> demande l'accès avec les permissions suivantes :</p>
            <ul class="scopes-list">
                <?php foreach ($valid_scopes as $s): ?>
                    <li>
                        <?php 
                        switch ($s) {
                            case 'read':
                                echo '<strong>Lecture</strong> - Voir la liste des fichiers';
                                break;
                            case 'write':
                                echo '<strong>Écriture</strong> - Ajouter ou modifier des fichiers';
                                break;
                            case 'admin':
                                echo '<strong>Administration</strong> - Accès complet à tous les fichiers';
                                break;
                            default:
                                echo htmlspecialchars($s);
                        }
                        ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        
        <form method="POST">
            <input type="text" name="username" placeholder="Nom d'utilisateur" required>
            <input type="password" name="password" placeholder="Mot de passe" required>
            <button type="submit">Autoriser l'accès</button>
        </form>
        <?php if (isset($error_message)): ?>
            <p class="error"><?= htmlspecialchars($error_message) ?></p>
        <?php endif; ?>
    </div>
</body>
</html>