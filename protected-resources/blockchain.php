<?php

/**
 * Publishes data to the blockchain stream
 * @param string $stream The stream name to publish to
 * @param array $data The data to publish
 * @return string|false The transaction hash if successful, false otherwise
 */
function publishToBlockchain($stream, $data) {
    $rpc_url = "http://multichainrpc:9gEwnCkgAXV5v3PPFgEMPyK7LyEyDbZovQq6qScvRnPA@localhost:5001";
    
    // Prepare JSON-RPC request
    $request = [
        'method' => 'publish',
        'params' => [
            $stream,                   // Stream name
            'log-' . time(),           // Key (unique identifier)
            bin2hex(json_encode($data)) // Data in hex format
        ],
        'id' => uniqid(),
        'chain_name' => 'DriveChain'
    ];
    
    // Initialize cURL session
    $ch = curl_init($rpc_url);
    
    // Set cURL options
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($request));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    
    // Execute cURL request
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code === 200) {
        $result = json_decode($response, true);
        if (isset($result['result'])) {
            return $result['result']; // Return transaction hash
        }
    }
    
    error_log("Failed to publish to blockchain: " . $response);
    return false;
}

/**
 * Retrieves logs from the blockchain stream
 * @param string $stream The stream name to retrieve from
 * @param array $filters Optional filters for the search
 * @return array|false The logs found or false in case of error
 */
function getLogsFromBlockchain($stream, $filters = []) {
    $rpc_url = "http://multichainrpc:9gEwnCkgAXV5v3PPFgEMPyK7LyEyDbZovQq6qScvRnPA@localhost:5001";
    
    // Prepare JSON-RPC request
    $request = [
        'method' => 'liststreamitems',
        'params' => [
            $stream,
            true,  // verbose
            $filters['count'] ?? 100,  // number of items to return
            $filters['start'] ?? 0,    // start position
            $filters['local-ordering'] ?? false
        ],
        'id' => uniqid(),
        'chain_name' => 'DriveChain'
    ];
    
    // Initialize cURL session
    $ch = curl_init($rpc_url);
    
    // Set cURL options
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($request));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    
    // Execute cURL request
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code === 200) {
        $result = json_decode($response, true);
        if (isset($result['result'])) {
            $logs = [];
            foreach ($result['result'] as $item) {
                if (isset($item['data'])) {
                    $logs[] = json_decode(hex2bin($item['data']), true);
                }
            }
            return $logs;
        }
    }
    
    error_log("Failed to retrieve from blockchain: " . $response);
    return false;
} 