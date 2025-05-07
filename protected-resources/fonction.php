
     <?php 
     function logAccess($user_id = null, $file_id = null, $filename = null, $success = false, $message = '', $action) {
    global $pdo;
    
    // Récupérer l'adresse IP du client
    $ip_address = $_SERVER['REMOTE_ADDR'];
    if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    
    // Préparer l'insertion dans la table access_logs
    $query = "INSERT INTO access_logs 
              (timestamp, ip_address, user_id, file_id, filename, action, success, message) 
              VALUES (NOW(), :ip, :user_id, :file_id, :filename, :action, :success, :message)";
    
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            ':ip' => $ip_address,
            ':user_id' => $user_id,
            ':file_id' => $file_id,
            ':filename' => $filename,
            ':action' => $action,
            ':success' => $success ? 1 : 0,
            ':message' => $message
        ]);
    } catch (PDOException $e) {
        // En cas d'erreur avec la journalisation, on continue quand même le script
        error_log('Erreur de journalisation: ' . $e->getMessage());
    }
}
