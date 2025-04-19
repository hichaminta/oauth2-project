<?php
include 'config.php';
// Rediriger l'utilisateur vers la page d'autorisation OAuth
header('Location: ' . AUTHORIZATION_URL . '?client_id=' . CLIENT_ID . '&redirect_uri=' . urlencode(REDIRECT_URI));
exit;
?>
