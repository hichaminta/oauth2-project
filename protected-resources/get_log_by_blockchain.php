<?php
header('Content-Type: application/json');

function getLogsFromBlockchain($stream = 'file_operations_stream', $limit = 50, $offset = 0) {
    $rpc_url = "http://multichainrpc:9gEwnCkgAXV5v3PPFgEMPyK7LyEyDbZovQq6qScvRnPA@localhost:5001";
    
    // Now get the stream items
    $request = [
        'method' => 'liststreamitems',
        'params' => [
            $stream,
            false,
            $limit,
            $offset
        ],
        'id' => uniqid(),
        'chain_name' => 'DriveChain'
    ];
    
    $ch = curl_init($rpc_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($request));
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $error = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($error) {
        return [
            'error' => 'Connection error',
            'details' => $error,
            'http_code' => $httpCode
        ];
    }
    
    if ($httpCode !== 200) {
        return [
            'error' => 'HTTP error',
            'http_code' => $httpCode,
            'response' => $response
        ];
    }
    
    $result = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        return [
            'error' => 'JSON decode error',
            'details' => json_last_error_msg(),
            'raw_response' => $response
        ];
    }
    
    // Check if the response is an array (direct MultiChain response)
    if (is_array($result) && !isset($result['result'])) {
        $logs = [];
        foreach ($result as $item) {
            if (!isset($item['data'])) {
                continue;
            }
            
            try {
                $decodedData = hex2bin($item['data']);
                if ($decodedData === false) {
                    continue;
                }
                
                $logData = json_decode($decodedData, true);
                if ($logData === null) {
                    continue;
                }
                
                $logs[] = [
                    'hash' => $item['txid'],
                    'timestamp' => $item['blocktime'],
                    'data' => $logData
                ];
            } catch (Exception $e) {
                continue;
            }
        }
        
        if (empty($logs)) {
            return [
                'error' => 'No valid logs found',
                'stream' => $stream,
                'total_items' => count($result),
                'response' => $result
            ];
        }
        
        return $logs;
    }
    
    // Check if the response has a result field (JSON-RPC response)
    if (isset($result['result']) && is_array($result['result'])) {
        $logs = [];
        foreach ($result['result'] as $item) {
            if (!isset($item['data'])) {
                continue;
            }
            
            try {
                $decodedData = hex2bin($item['data']);
                if ($decodedData === false) {
                    continue;
                }
                
                $logData = json_decode($decodedData, true);
                if ($logData === null) {
                    continue;
                }
                
                $logs[] = [
                    'hash' => $item['txid'],
                    'timestamp' => $item['blocktime'],
                    'data' => $logData
                ];
            } catch (Exception $e) {
                continue;
            }
        }
        
        if (empty($logs)) {
            return [
                'error' => 'No valid logs found',
                'stream' => $stream,
                'total_items' => count($result['result']),
                'response' => $result
            ];
        }
        
        return $logs;
    }
    
    return [
        'error' => 'Invalid response format',
        'response' => $result
    ];
}

// Handle GET request
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
    $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
    $stream = isset($_GET['stream']) ? $_GET['stream'] : 'file_operations_stream';
    
    $logs = getLogsFromBlockchain($stream, $limit, $offset);
    echo json_encode($logs, JSON_PRETTY_PRINT);
}
?> 