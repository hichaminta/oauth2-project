<?php


// config.php
define('CLIENT_ID', 'quickview-client');
define('CLIENT_SECRET', 'secret123');
define('REDIRECT_URI', 'http://localhost/oauth2-project/client-web/callback.php');  // URL vers laquelle l'utilisateur est redirigé après l'autorisation
define('AUTHORIZATION_URL', 'http://localhost/oauth2-project/server-oauth/authorization.php');
define('TOKEN_URL', 'http://localhost/oauth2-project/server-oauth/token.php');
define('RESOURCE_URL', 'http://localhost/oauth2-project/protected-resources/resource.php');
