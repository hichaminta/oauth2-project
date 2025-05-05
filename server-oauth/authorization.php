<?php
require_once 'database.php';
session_start();

// Activer l'affichage des erreurs pour le débogage
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Fonction d'aide pour la journalisation
function logDebug($message) {
    error_log("[OAUTH DEBUG] " . $message);
}

// Récupérer les paramètres d'autorisation
$client_id = $_GET['client_id'] ?? '';
$redirect_uri = $_GET['redirect_uri'] ?? '';
$response_type = $_GET['response_type'] ?? '';
$scope = $_GET['scope'] ?? '';

logDebug("Paramètres reçus - client_id: $client_id, redirect_uri: $redirect_uri, response_type: $response_type, scope: $scope");

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
        
        // Récupérer les scopes disponibles pour cet utilisateur
        $stmt = $pdo->prepare("SELECT role, available_scopes FROM user_roles WHERE user_id = ?");
        $stmt->execute([$user['id']]);
        $role_data = $stmt->fetch();
        
        if ($role_data) {
            $_SESSION['role'] = $role_data['role'];
            $_SESSION['available_scopes'] = $role_data['available_scopes'];
            logDebug("Scopes disponibles pour l'utilisateur: " . $_SESSION['available_scopes']);
        } else {
            // Par défaut, si aucun rôle défini
            $_SESSION['role'] = 'user';
            $_SESSION['available_scopes'] = 'read';
            logDebug("Aucun rôle trouvé, utilisation du scope par défaut: read");
        }
        
        // Rediriger vers la page de sélection des scopes
        header("Location: " . $_SERVER['PHP_SELF'] . "?" . http_build_query($_GET));
        exit;
    } else {
        $error_message = "Identifiants incorrects.";
    }
}

// Traitement de l'autorisation des scopes
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['authorize'])) {
    // Débogue - Afficher toutes les données POST
    logDebug("Données POST reçues: " . print_r($_POST, true));
    
    $authorized_scopes = [];
    $requested_scopes = explode(' ', $scope);
    $available_scopes = explode(' ', $_SESSION['available_scopes']);
    
    logDebug("Scopes disponibles: " . print_r($available_scopes, true));
    logDebug("Scopes demandés: " . print_r($requested_scopes, true));
    
    // CORRECTION: Utiliser une seule méthode cohérente pour collecter les scopes autorisés
    if (isset($_POST['all_scopes']) && is_array($_POST['all_scopes'])) {
        foreach ($_POST['all_scopes'] as $s) {
            $checkbox_name = 'scope_' . $s;
            logDebug("Vérifie la case à cocher: $checkbox_name - Existe: " . (isset($_POST[$checkbox_name]) ? 'Oui' : 'Non'));
            
            // CORRECTION: S'assurer que le scope est disponible pour l'utilisateur avant de l'ajouter
            if (isset($_POST[$checkbox_name]) && in_array($s, $available_scopes)) {
                $authorized_scopes[] = $s;
                logDebug("Scope autorisé ajouté: $s");
            }
        }
    }
    
    // Si aucun scope n'est sélectionné, utilisez le scope minimal par défaut
    if (empty($authorized_scopes)) {
        $authorized_scopes[] = 'read'; // Scope minimum par défaut
        logDebug("Aucun scope autorisé, utilisation du scope par défaut: read");
    }
    
    // Générer le code d'autorisation
    $code = bin2hex(random_bytes(16));
    $expires = date('Y-m-d H:i:s', time() + 300); // Code valide 5 minutes
    $final_scope = implode(' ', $authorized_scopes);
    
    logDebug("Scopes finaux autorisés: $final_scope");
    
    try {
        // Insérer le code d'autorisation avec les scopes
        $stmt = $pdo->prepare("INSERT INTO authorization_codes (code, client_id, user_id, redirect_uri, expires, scope) 
                              VALUES (?, ?, ?, ?, ?, ?)");
        
        $result = $stmt->execute([$code, $client_id, $_SESSION['user_id'], $redirect_uri, $expires, $final_scope]);
        
        if (!$result) {
            logDebug("Erreur lors de l'insertion: " . print_r($stmt->errorInfo(), true));
            die("Erreur lors de l'insertion du code d'autorisation");
        }
        
        // Vérifier que l'insertion a fonctionné
        $check_insert = $pdo->prepare("SELECT * FROM authorization_codes WHERE code = ?");
        $check_insert->execute([$code]);
        $auth_code = $check_insert->fetch();
        
        logDebug("Code d'autorisation inséré: " . print_r($auth_code, true));
        logDebug("Scope stocké en base: " . ($auth_code['scope'] ?? 'non défini'));
        
        // Rediriger vers le client avec le code d'autorisation
        header("Location: $redirect_uri?code=$code");
        exit;
    } catch (PDOException $e) {
        logDebug("Exception PDO: " . $e->getMessage());
        die("Erreur lors de l'insertion du code d'autorisation: " . $e->getMessage());
    }
}

// Si l'utilisateur est déjà connecté, afficher la page d'autorisation de scopes
if (isset($_SESSION['user_id'])) {
    $available_scopes = explode(' ', $_SESSION['available_scopes']);
    logDebug("Scopes disponibles pour l'utilisateur: " . implode(', ', $available_scopes));
    
    // CORRECTION: Montrer TOUS les scopes disponibles pour l'utilisateur, qu'il soit admin ou non
    // Par défaut, on montre tous les scopes disponibles pour l'utilisateur
    $valid_scopes = $available_scopes;
    
    // Si un scope est spécifié dans la requête ET que l'utilisateur n'est pas admin,
    // on peut filtrer selon les scopes demandés (mais toujours parmi ceux disponibles)
    if (!empty($scope) && $_SESSION['role'] != 'admin') {
        $requested_scopes = explode(' ', $scope);
        logDebug("Scopes demandés: " . implode(', ', $requested_scopes));
        
        // On ne filtre pas, on montre TOUS les scopes disponibles pour l'utilisateur
        // $valid_scopes = array_intersect($requested_scopes, $available_scopes);
        // logDebug("Scopes demandés filtrés: " . implode(', ', $valid_scopes));
    }
    
    logDebug("Scopes finaux à afficher: " . implode(', ', $valid_scopes));
    
    // Définir les descriptions des scopes
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
        
        <?php if (isset($_GET['debug']) && $_GET['debug'] == '1'): ?>
        <!-- Informations de débogage, à supprimer en production -->
        <div class="debug-info">
            <h3>Informations de débogage</h3>
            <p>Scopes disponibles: <?= htmlspecialchars($_SESSION['available_scopes']) ?></p>
            <p>Scopes validés pour l'autorisation: <?= htmlspecialchars(implode(', ', $valid_scopes)) ?></p>
            <p>Client ID: <?= htmlspecialchars($client_id) ?></p>
            <p>Redirect URI: <?= htmlspecialchars($redirect_uri) ?></p>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
<?php
} else {
    // Si l'utilisateur n'est pas connecté, afficher le formulaire de connexion
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