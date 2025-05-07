<?php
require_once 'database.php';
require_once 'fonction.php';
session_start();

// Récupérer les paramètres d'autorisation
$client_id = $_GET['client_id'] ?? '';
$redirect_uri = $_GET['redirect_uri'] ?? '';
$response_type = $_GET['response_type'] ?? '';
$scope = $_GET['scope'] ?? '';

// Valider les paramètres
if (empty($client_id) || empty($redirect_uri) || empty($response_type) || $response_type !== 'code') {
    die("Paramètres d'autorisation invalides");
}

// Vérifier si le client existe
$stmt = $pdo->prepare("SELECT * FROM clients WHERE client_id = ? AND redirect_uri = ?");
$stmt->execute([$client_id, $redirect_uri]);
$client = $stmt->fetch();

if (!$client) {
    die("Client non autorisé");
}

// Traitement du formulaire de connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND password = ?");
    $stmt->execute([$username, $password]);
    $user = $stmt->fetch();

    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        logAccess($user['id'], null, null, true, 'Connexion réussie', 'login');

        $stmt = $pdo->prepare("SELECT role, available_scopes FROM user_roles WHERE user_id = ?");
        $stmt->execute([$user['id']]);
        $role_data = $stmt->fetch();

        if ($role_data) {
            $_SESSION['role'] = $role_data['role'];
            $_SESSION['available_scopes'] = $role_data['available_scopes'];
        } else {
            $_SESSION['role'] = 'user';
            $_SESSION['available_scopes'] = 'read';
        }

        header("Location: " . $_SERVER['PHP_SELF'] . "?" . http_build_query($_GET));
        exit;
    } else {
        $error_message = "Identifiants incorrects.";
    }
}

// Traitement de l'autorisation des scopes
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['authorize'])) {
    $authorized_scopes = [];
    $requested_scopes = explode(' ', $scope);
    $available_scopes = explode(' ', $_SESSION['available_scopes']);

    if (isset($_POST['all_scopes']) && is_array($_POST['all_scopes'])) {
        foreach ($_POST['all_scopes'] as $s) {
            $checkbox_name = 'scope_' . $s;
            if (isset($_POST[$checkbox_name]) && in_array($s, $available_scopes)) {
                $authorized_scopes[] = $s;
            }
        }
    }

    if (empty($authorized_scopes)) {
        $authorized_scopes[] = 'read';
    }

    $code = bin2hex(random_bytes(16));
    $expires = date('Y-m-d H:i:s', time() + 300);
    $final_scope = implode(' ', $authorized_scopes);

    try {
        $stmt = $pdo->prepare("INSERT INTO authorization_codes (code, client_id, user_id, redirect_uri, expires, scope) 
                              VALUES (?, ?, ?, ?, ?, ?)");
        $result = $stmt->execute([$code, $client_id, $_SESSION['user_id'], $redirect_uri, $expires, $final_scope]);

        if (!$result) {
            die("Erreur lors de l'insertion du code d'autorisation");
        }

        header("Location: $redirect_uri?code=$code");
        exit;
    } catch (PDOException $e) {
        die("Erreur lors de l'insertion du code d'autorisation: " . $e->getMessage());
    }
}

// Si l'utilisateur est déjà connecté, afficher la page de sélection des scopes
if (isset($_SESSION['user_id'])) {
    $available_scopes = explode(' ', $_SESSION['available_scopes']);
    $valid_scopes = $available_scopes;

    $scope_descriptions = [
        'read' => 'Voir la liste des fichiers',
        'write' => 'Ajouter ou modifier des fichiers',
        'admin' => 'Accès complet (administration)'
    ];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Autoriser l'accès</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <h2>Autoriser l'accès à <?= htmlspecialchars($client_id) ?></h2>
        <p>Connecté en tant que <strong><?= htmlspecialchars($_SESSION['username']) ?></strong> (<?= htmlspecialchars($_SESSION['role']) ?>)</p>
        
        <form method="POST" action="<?= $_SERVER['PHP_SELF'] ?>?<?= http_build_query($_GET) ?>">
            <p>Cette application demande les autorisations suivantes :</p>
            
            <?php foreach ($valid_scopes as $s): ?>
            <div class="scope-container">
                <label>
                    <input type="checkbox" name="scope_<?= htmlspecialchars($s) ?>" value="1" checked>
                    <strong><?= htmlspecialchars(ucfirst($s)) ?></strong>
                </label>
                <span class="scope-description"><?= htmlspecialchars($scope_descriptions[$s] ?? 'Accès sans description') ?></span>
                <input type="hidden" name="all_scopes[]" value="<?= htmlspecialchars($s) ?>">
            </div>
            <?php endforeach; ?>
            
            <div class="button-group">
                <button type="submit" name="authorize" class="btn-primary">Autoriser</button>
                <a href="index.php" class="btn-secondary">Annuler</a>
            </div>
        </form>
    </div>
</body>
</html>
<?php
} else {
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion requise</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <h2>Connexion requise</h2>
        <p>Veuillez vous connecter pour autoriser l'accès à <?= htmlspecialchars($client_id) ?></p>
        
        <?php if (isset($error_message)): ?>
        <div class="error-message">
            <?= htmlspecialchars($error_message) ?>
        </div>
        <?php endif; ?>
        
        <form method="POST" action="<?= $_SERVER['PHP_SELF'] ?>?<?= http_build_query($_GET) ?>">
            <div class="form-group">
                <label for="username">Nom d'utilisateur:</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Mot de passe:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="button-group">
                <button type="submit" name="login" class="btn-primary">Se connecter</button>
                <a href="index.php" class="btn-secondary">Annuler</a>
            </div>
        </form>
    </div>
</body>
</html>
<?php
}
?>
