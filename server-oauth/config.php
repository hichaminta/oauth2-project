<?php
// config.php
include_once 'variables.php';

define('DB_HOST', 'localhost');
define('DB_NAME', 'oauth2_project');
define('DB_USER', 'root');
define('DB_PASS', ''); // modifie si nécessaire

define('CLIENT_ID', 'quickview-client');
define('CLIENT_SECRET', 'secret123');
define('REDIRECT_URI', $domainenameclient . 'callback.php');
?>
