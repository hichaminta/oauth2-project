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

// Définir le mode (login ou register)
$mode = isset($_GET['mode']) ? $_GET['mode'] : 'login';

// Traitement du formulaire d'inscription
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $registration_error = null;
    
    // Vérifier si le nom d'utilisateur ou l'email existe déjà
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    $exists = $stmt->fetchColumn();
    
    if ($exists) {
        $registration_error = "Ce nom d'utilisateur ou cette adresse email est déjà utilisé(e).";
    } 
    elseif ($password !== $confirm_password) {
        $registration_error = "Les mots de passe ne correspondent pas.";
    }
    elseif (strlen($password) < 6) {
        $registration_error = "Le mot de passe doit contenir au moins 6 caractères.";
    } 
    else {
        // Hacher le mot de passe
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        try {
            $pdo->beginTransaction();
            
            // Insérer le nouvel utilisateur
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            $stmt->execute([$username, $email, $hashed_password]);
            $user_id = $pdo->lastInsertId();
            
            // Ajouter les rôles par défaut
            $stmt = $pdo->prepare("INSERT INTO user_roles (user_id, role, available_scopes) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, 'user', 'read']);
            
            $pdo->commit();
            
            // Préserver les paramètres OAuth2 originaux
            $params = array(
                'client_id' => $client_id,
                'redirect_uri' => $redirect_uri,
                'response_type' => $response_type,
                'scope' => $scope,
                'registration_success' => 1
            );
            
            // Rediriger vers la page de connexion avec les paramètres préservés
            header("Location: " . $_SERVER['PHP_SELF'] . "?" . http_build_query($params));
            exit;
        } catch (PDOException $e) {
            $pdo->rollBack();
            $registration_error = "Erreur lors de l'inscription: " . $e->getMessage();
        }
    }
}

// Traitement du formulaire de connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT id, username, password FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
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
    <title>QuickView - Autorisation</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        :root {
            --primary-color: #5c6bc0;
            --secondary-color: #455a64;
            --accent-color: #ff9800;
            --background-color: #f3f4f8;
            --text-color: #37474f;
            --light-gray: #e9eef2;
            --white: #ffffff;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: var(--background-color);
            color: var(--text-color);
            line-height: 1.6;
        }

        .container {
            max-width: 600px;
            margin: 50px auto;
            padding: 2rem;
            background-color: var(--white);
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        h2 {
            color: var(--secondary-color);
            margin-bottom: 1.5rem;
            text-align: center;
            font-size: 1.8rem;
        }

        p {
            margin-bottom: 1rem;
        }

        .scope-container {
            margin-bottom: 1rem;
            padding: 1rem;
            background-color: var(--light-gray);
            border-radius: 5px;
        }

        .scope-description {
            display: block;
            margin-top: 0.5rem;
            color: #666;
            font-size: 0.9rem;
        }

        .button-group {
            display: flex;
            justify-content: space-between;
            margin-top: 2rem;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: var(--white);
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background-color: #4a57a6;
        }

        .btn-secondary {
            background-color: var(--secondary-color);
            color: var(--white);
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            text-align: center;
        }

        .btn-secondary:hover {
            background-color: #3b4c55;
        }

        .logo {
            text-align: center;
            margin-bottom: 2rem;
            font-size: 2rem;
            color: var(--primary-color);
            font-weight: bold;
        }

        .logo i {
            font-size: 3rem;
            margin-bottom: 0.5rem;
            display: block;
        }

        .user-info {
            text-align: center;
            margin-bottom: 1.5rem;
            padding: 0.5rem;
            background-color: var(--light-gray);
            border-radius: 5px;
        }
    </style>
    <!-- Ajout de Font Awesome pour les icônes -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <div class="logo">
            <i class="fas fa-file-search"></i>
            QuickView
        </div>
        
        <div class="user-info">
            <i class="fas fa-user-circle"></i> 
            Connecté en tant que <strong><?= htmlspecialchars($_SESSION['username']) ?></strong>
            <span class="badge"><?= htmlspecialchars($_SESSION['role']) ?></span>
        </div>
        
        <h2>Autoriser l'accès à <?= htmlspecialchars($client_id) ?></h2>
        
        <form method="POST" action="<?= $_SERVER['PHP_SELF'] ?>?<?= http_build_query($_GET) ?>">
            <p>Cette application demande les autorisations suivantes :</p>
            
            <?php foreach ($valid_scopes as $s): ?>
            <div class="scope-container">
                <label>
                    <input type="checkbox" name="scope_<?= htmlspecialchars($s) ?>" value="1" checked>
                    <strong><?= htmlspecialchars(ucfirst($s)) ?></strong>
                </label>
                <span class="scope-description">
                    <i class="fas fa-info-circle"></i> 
                    <?= htmlspecialchars($scope_descriptions[$s] ?? 'Accès sans description') ?>
                </span>
                <input type="hidden" name="all_scopes[]" value="<?= htmlspecialchars($s) ?>">
            </div>
            <?php endforeach; ?>
            
            <div class="button-group">
                <a href="index.php" class="btn-secondary">
                    <i class="fas fa-times"></i> Annuler
                </a>
                <button type="submit" name="authorize" class="btn-primary">
                    <i class="fas fa-check"></i> Autoriser
                </button>
            </div>
        </form>
    </div>
</body>
</html>
<?php
} else {
    // Afficher le formulaire d'inscription ou de connexion selon le mode
    if ($mode === 'register') {
        // Formulaire d'inscription
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QuickView - Créer un compte</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        :root {
            --primary-color: #5c6bc0;
            --secondary-color: #455a64;
            --accent-color: #ff9800;
            --background-color: #f3f4f8;
            --text-color: #37474f;
            --light-gray: #e9eef2;
            --white: #ffffff;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: var(--background-color);
            color: var(--text-color);
            line-height: 1.6;
        }

        .container {
            max-width: 450px;
            margin: 50px auto;
            padding: 2rem;
            background-color: var(--white);
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        h2 {
            color: var(--secondary-color);
            margin-bottom: 1.5rem;
            text-align: center;
            font-size: 1.8rem;
        }

        p {
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        input[type="text"],
        input[type="password"],
        input[type="email"] {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid var(--light-gray);
            border-radius: 5px;
            font-size: 1rem;
        }

        input[type="text"]:focus,
        input[type="password"]:focus,
        input[type="email"]:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        .button-group {
            display: flex;
            justify-content: space-between;
            margin-top: 2rem;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: var(--white);
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background-color: #4a57a6;
        }

        .btn-secondary {
            background-color: var(--secondary-color);
            color: var(--white);
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            text-align: center;
        }

        .btn-secondary:hover {
            background-color: #3b4c55;
        }

        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 0.8rem;
            margin-bottom: 1.5rem;
            border: 1px solid #f5c6cb;
            border-radius: 5px;
        }

        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 0.8rem;
            margin-bottom: 1.5rem;
            border: 1px solid #c3e6cb;
            border-radius: 5px;
        }

        .logo {
            text-align: center;
            margin-bottom: 2rem;
            font-size: 2rem;
            color: var(--primary-color);
            font-weight: bold;
        }

        .logo i {
            font-size: 3rem;
            margin-bottom: 0.5rem;
            display: block;
        }

        .toggle-form {
            text-align: center;
            margin-top: 1.5rem;
        }

        .toggle-form a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }

        .toggle-form a:hover {
            text-decoration: underline;
        }
    </style>
    <!-- Ajout de Font Awesome pour les icônes -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <div class="logo">
            <i class="fas fa-file-search"></i>
            QuickView
        </div>
        
        <h2>Créer un compte</h2>
        <p>Pour accéder à <strong><?= htmlspecialchars($client_id) ?></strong></p>
        
        <?php if (isset($registration_error)): ?>
        <div class="error-message">
            <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($registration_error) ?>
        </div>
        <?php endif; ?>
        
        <form method="POST" action="<?= $_SERVER['PHP_SELF'] ?>?<?= http_build_query($_GET) ?>">
            <input type="hidden" name="mode" value="register">
            <div class="form-group">
                <label for="username"><i class="fas fa-user"></i> Nom d'utilisateur:</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="email"><i class="fas fa-envelope"></i> Adresse email:</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password"><i class="fas fa-lock"></i> Mot de passe:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="confirm_password"><i class="fas fa-lock"></i> Confirmer le mot de passe:</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            <div class="button-group">
                <a href="<?= $_SERVER['PHP_SELF'] ?>?<?= http_build_query(['client_id' => $client_id, 'redirect_uri' => $redirect_uri, 'response_type' => $response_type, 'scope' => $scope]) ?>" class="btn-secondary">
                    <i class="fas fa-arrow-left"></i> Retour
                </a>
                <button type="submit" name="register" class="btn-primary">
                    <i class="fas fa-user-plus"></i> S'inscrire
                </button>
            </div>
        </form>
        
        <div class="toggle-form">
            <p>Vous avez déjà un compte? <a href="<?= $_SERVER['PHP_SELF'] ?>?<?= http_build_query($_GET) ?>">Connectez-vous</a></p>
        </div>
    </div>
</body>
</html>
<?php
    } else {
        // Formulaire de connexion
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QuickView - Connexion</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        :root {
            --primary-color: #5c6bc0;
            --secondary-color: #455a64;
            --accent-color: #ff9800;
            --background-color: #f3f4f8;
            --text-color: #37474f;
            --light-gray: #e9eef2;
            --white: #ffffff;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: var(--background-color);
            color: var(--text-color);
            line-height: 1.6;
        }

        .container {
            max-width: 450px;
            margin: 50px auto;
            padding: 2rem;
            background-color: var(--white);
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        h2 {
            color: var(--secondary-color);
            margin-bottom: 1.5rem;
            text-align: center;
            font-size: 1.8rem;
        }

        p {
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid var(--light-gray);
            border-radius: 5px;
            font-size: 1rem;
        }

        input[type="text"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        .button-group {
            display: flex;
            justify-content: space-between;
            margin-top: 2rem;
        }

        .btn-primary {
            background-color: var(--primary-color);
            color: var(--white);
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background-color: #4a57a6;
        }

        .btn-secondary {
            background-color: var(--secondary-color);
            color: var(--white);
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            text-align: center;
        }

        .btn-secondary:hover {
            background-color: #3b4c55;
        }

        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 0.8rem;
            margin-bottom: 1.5rem;
            border: 1px solid #f5c6cb;
            border-radius: 5px;
        }

        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 0.8rem;
            margin-bottom: 1.5rem;
            border: 1px solid #c3e6cb;
            border-radius: 5px;
        }

        .logo {
            text-align: center;
            margin-bottom: 2rem;
            font-size: 2rem;
            color: var(--primary-color);
            font-weight: bold;
        }

        .logo i {
            font-size: 3rem;
            margin-bottom: 0.5rem;
            display: block;
        }

        .toggle-form {
            text-align: center;
            margin-top: 1.5rem;
        }

        .toggle-form a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }

        .toggle-form a:hover {
            text-decoration: underline;
        }
    </style>
    <!-- Ajout de Font Awesome pour les icônes -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <div class="logo">
            <i class="fas fa-file-search"></i>
            QuickView
        </div>
        
        <h2>Connexion requise</h2>
        <p>Pour autoriser l'accès à <strong><?= htmlspecialchars($client_id) ?></strong></p>
        
        <?php if (isset($error_message)): ?>
        <div class="error-message">
            <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error_message) ?>
        </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['registration_success'])): ?>
        <div class="success-message">
            <i class="fas fa-check-circle"></i> Votre compte a été créé avec succès! Veuillez vous connecter.
        </div>
        <?php endif; ?>
        
        <form method="POST" action="<?= $_SERVER['PHP_SELF'] ?>?<?= http_build_query($_GET) ?>">
            <div class="form-group">
                <label for="username"><i class="fas fa-user"></i> Nom d'utilisateur:</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password"><i class="fas fa-lock"></i> Mot de passe:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="button-group">
                <a href="index.php" class="btn-secondary">
                    <i class="fas fa-times"></i> Annuler
                </a>
                <button type="submit" name="login" class="btn-primary">
                    <i class="fas fa-sign-in-alt"></i> Se connecter
                </button>
            </div>
        </form>
        
        <div class="toggle-form">
            <p>Vous n'avez pas de compte? <a href="<?= $_SERVER['PHP_SELF'] ?>?<?= http_build_query(['client_id' => $client_id, 'redirect_uri' => $redirect_uri, 'response_type' => $response_type, 'scope' => $scope, 'mode' => 'register']) ?>">Créer un compte</a></p>
        </div>
    </div>
</body>
</html>
<?php
    }
}
?>
