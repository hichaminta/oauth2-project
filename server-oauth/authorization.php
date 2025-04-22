<?php
require_once 'database.php';
session_start();

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
        $client_id = $_GET['client_id'];
        $redirect_uri = $_GET['redirect_uri'];
        $scope = $_GET['scope'];
        $code = bin2hex(random_bytes(16));
        $expires = date('Y-m-d H:i:s', time() + 300);

        $stmt = $pdo->prepare("INSERT INTO authorization_codes (code, client_id, user_id, redirect_uri, expires, scope) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$code, $client_id, $user['id'], $redirect_uri, $expires, $scope]);

        header("Location: $redirect_uri?code=$code");
        exit;
    } else {
        echo "Identifiants incorrects.";
    }
}
?>

<h2>Connexion OAuth</h2>
<form method="POST">
    <input type="text" name="username" placeholder="Nom d'utilisateur" required><br>
    <input type="password" name="password" placeholder="Mot de passe" required><br>
    <button type="submit">Se connecter</button>
</form>
