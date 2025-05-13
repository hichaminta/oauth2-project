<?php
header('Content-Type: application/json');

// Allow only GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

// Get optional parameters
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
$stream = isset($_GET['stream']) ? $_GET['stream'] : 'chain_oauth_token';

// Get tokens from blockchain
$tokens = getTokensFromBlockchain($stream, $limit, $offset);

// Return response
echo json_encode($tokens);

function getTokensFromBlockchain($stream, $limit = 50, $offset = 0) {
    $rpc_url = "http://multichainrpc:HtbLPm5f1X3HB9XkdkngARZzbbJN7FwDtGtAHJ6Tn3bQ@localhost:5000";
    
    // Prepare JSON-RPC request to list stream items
    $request = [
        'method' => 'liststreamitems',
        'params' => [
            $stream,       // Stream name
            false,         // Verbose
            $limit,        // Count
            $offset        // Start
        ],
        'id' => uniqid(),
        'chain_name' => 'AuthChain'
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
    
    $result = json_decode($response, true);
    
    // Check if result contains items
    if (isset($result['result']) && is_array($result['result'])) {
        // Process each item to decode the data
        foreach ($result['result'] as &$item) {
            if (isset($item['data'])) {
                // Convert hex to string and then decode JSON
                $decodedData = hex2bin($item['data']);
                $item['decoded_data'] = json_decode($decodedData, true);
            }
        }
        return $result['result'];
    }
    
    return ['error' => 'No items found or error in response', 'raw_response' => $result];
}
?>