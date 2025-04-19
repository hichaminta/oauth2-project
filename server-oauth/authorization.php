<?php
// authorization.php
include 'config.php';
include 'database.php';

session_start();

if (!isset($_GET['client_id']) || !isset($_GET['redirect_uri'])) {
    die('Paramètres manquants');
}

$client_id = $_GET['client_id'];
$redirect_uri = $_GET['redirect_uri'];

// Vérification du client
$stmt = $pdo->prepare("SELECT * FROM clients WHERE client_id = ? AND redirect_uri = ?");
$stmt->execute([$client_id, $redirect_uri]);
$client = $stmt->fetch();

if (!$client) {
    die("Client non valide.");
}

// Si l'utilisateur est déjà connecté
if (isset($_SESSION['user_id'])) {
    // Générer un code d'autorisation
    $code = bin2hex(random_bytes(16));  // Code d'autorisation unique
    $stmt = $pdo->prepare("INSERT INTO authorization_codes (code, user_id, client_id, redirect_uri, expires_at) 
                           VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
        $code,
        $_SESSION['user_id'],
        $client_id,
        $redirect_uri,
        date('Y-m-d H:i:s', strtotime('+10 minutes'))  // Expiration dans 10 minutes
    ]);

    // Redirection vers l'URI de redirection avec le code
    header("Location: $redirect_uri?code=$code");
    exit;
}

// Formulaire de connexion si l'utilisateur n'est pas connecté
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$_POST['username']]);
    $user = $stmt->fetch();

    // Vérification sans hachage du mot de passe
    if ($user && $_POST['password'] === $user['password']) {
        $_SESSION['user_id'] = $user['id'];
        header("Location: authorization.php?client_id=$client_id&redirect_uri=$redirect_uri&response_type=code");
        exit;
    } else {
        echo "Identifiants incorrects.";
    }
}
?>

<h2>Connexion</h2>
<form method="post">
    <input name="username" placeholder="Nom d’utilisateur" required><br>
    <input name="password" type="password" placeholder="Mot de passe" required><br>
    <button type="submit">Se connecter</button>
</form>
