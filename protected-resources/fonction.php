
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
function publishToBlockchain($stream, $data) {
    $rpc_url = "http://multichainrpc:9gEwnCkgAXV5v3PPFgEMPyK7LyEyDbZovQq6qScvRnPA@localhost:5001";
    
    // Prepare JSON-RPC request
    $request = [
        'method' => 'publish',
        'params' => [
            $stream,                   // Stream name
            'token-' . time(),         // Key (unique identifier)
            bin2hex(json_encode($data)) // Data in hex format
        ],
        'id' => uniqid(),
        'chain_name' => 'DriveChain'
    ];
    
    // Send request to MultiChain
    $ch = curl_init($rpc_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($request));
    
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        error_log("Blockchain error: " . $error);
        return ['error' => $error];
    }
    
    return json_decode($response, true);
}