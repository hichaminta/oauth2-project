<?php 
require_once 'blockchain.php';

function logAccess($user_id = null, $file_id = null, $filename = null, $success = false, $message = '', $action) {
    global $pdo;
    
    // Récupérer l'adresse IP du client
    $ip_address = $_SERVER['REMOTE_ADDR'];
    if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    
    // Récupérer les informations supplémentaires pour le message détaillé
    $detailed_message = $message;
    if ($user_id) {
        $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $username = $user ? $user['username'] : 'Utilisateur inconnu';
    } else {
        $username = 'Non authentifié';
    }
    
    // Construire le message détaillé selon l'action
    switch ($action) {
        case 'delete_permission':
            if ($file_id) {
                $stmt = $pdo->prepare("SELECT filename FROM files WHERE id = ?");
                $stmt->execute([$file_id]);
                $file = $stmt->fetch(PDO::FETCH_ASSOC);
                $detailed_message = sprintf(
                    "L'utilisateur %s a supprimé les permissions du fichier '%s' (ID: %d)",
                    $username,
                    $file ? $file['filename'] : 'Inconnu',
                    $file_id
                );
            }
            break;
            
        case 'update_file_permission':
            if ($file_id) {
                $stmt = $pdo->prepare("SELECT filename FROM files WHERE id = ?");
                $stmt->execute([$file_id]);
                $file = $stmt->fetch(PDO::FETCH_ASSOC);
                $detailed_message = sprintf(
                    "L'utilisateur %s a modifié les permissions du fichier '%s' (ID: %d)",
                    $username,
                    $file ? $file['filename'] : 'Inconnu',
                    $file_id
                );
            }
            break;
            
        case 'add_permission':
            if ($file_id) {
                $stmt = $pdo->prepare("SELECT filename FROM files WHERE id = ?");
                $stmt->execute([$file_id]);
                $file = $stmt->fetch(PDO::FETCH_ASSOC);
                $detailed_message = sprintf(
                    "L'utilisateur %s a ajouté des permissions pour le fichier '%s' (ID: %d)",
                    $username,
                    $file ? $file['filename'] : 'Inconnu',
                    $file_id
                );
            }
            break;
            
        case 'upload':
            if ($file_id) {
                $stmt = $pdo->prepare("SELECT size, path FROM files WHERE id = ?");
                $stmt->execute([$file_id]);
                $file = $stmt->fetch(PDO::FETCH_ASSOC);
                $file_size = $file ? number_format($file['size'] / 1024, 2) . ' KB' : 'Inconnu';
                $detailed_message = sprintf(
                    "L'utilisateur %s a tenté de téléverser le fichier '%s' (Taille: %s, ID: %d)",
                    $username,
                    $filename ?? 'Inconnu',
                    $file_size,
                    $file_id
                );
            } else {
                $detailed_message = sprintf(
                    "L'utilisateur %s a tenté de téléverser le fichier '%s'",
                    $username,
                    $filename ?? 'Inconnu'
                );
            }
            break;
            
        case 'upload_complete':
            if ($file_id) {
                $stmt = $pdo->prepare("SELECT size, path FROM files WHERE id = ?");
                $stmt->execute([$file_id]);
                $file = $stmt->fetch(PDO::FETCH_ASSOC);
                $file_size = $file ? number_format($file['size'] / 1024, 2) . ' KB' : 'Inconnu';
                $detailed_message = sprintf(
                    "L'utilisateur %s a téléversé avec succès le fichier '%s' (Taille: %s, ID: %d, Chemin: %s)",
                    $username,
                    $filename ?? 'Inconnu',
                    $file_size,
                    $file_id,
                    $file ? $file['path'] : 'Inconnu'
                );
            } else {
                $detailed_message = sprintf(
                    "L'utilisateur %s a téléversé avec succès le fichier '%s'",
                    $username,
                    $filename ?? 'Inconnu'
                );
            }
            break;
            
        case 'download':
            if ($file_id) {
                $stmt = $pdo->prepare("SELECT size, path FROM files WHERE id = ?");
                $stmt->execute([$file_id]);
                $file = $stmt->fetch(PDO::FETCH_ASSOC);
                $file_size = $file ? number_format($file['size'] / 1024, 2) . ' KB' : 'Inconnu';
                $detailed_message = sprintf(
                    "L'utilisateur %s a téléchargé le fichier '%s' (Taille: %s, ID: %d, Chemin: %s)",
                    $username,
                    $filename ?? 'Inconnu',
                    $file_size,
                    $file_id,
                    $file ? $file['path'] : 'Inconnu'
                );
            } else {
                $detailed_message = sprintf(
                    "L'utilisateur %s a téléchargé le fichier '%s'",
                    $username,
                    $filename ?? 'Inconnu'
                );
            }
            break;
            
        case 'delete':
            if ($file_id) {
                $stmt = $pdo->prepare("SELECT size, path FROM files WHERE id = ?");
                $stmt->execute([$file_id]);
                $file = $stmt->fetch(PDO::FETCH_ASSOC);
                $file_size = $file ? number_format($file['size'] / 1024, 2) . ' KB' : 'Inconnu';
                $detailed_message = sprintf(
                    "L'utilisateur %s a supprimé le fichier '%s' (Taille: %s, ID: %d, Chemin: %s)",
                    $username,
                    $filename ?? 'Inconnu',
                    $file_size,
                    $file_id,
                    $file ? $file['path'] : 'Inconnu'
                );
            } else {
                $detailed_message = sprintf(
                    "L'utilisateur %s a supprimé le fichier '%s'",
                    $username,
                    $filename ?? 'Inconnu'
                );
            }
            break;
            
        case 'login':
            $detailed_message = sprintf(
                "L'utilisateur %s s'est connecté au système depuis l'adresse IP %s",
                $username,
                $ip_address
            );
            break;
            
        default:
            $detailed_message = sprintf(
                "Action '%s' effectuée par l'utilisateur %s: %s",
                $action,
                $username,
                $message
            );
    }
    
    // Prepare log data for blockchain
    $log_data = [
        'timestamp' => date('Y-m-d H:i:s'),
        'ip_address' => $ip_address,
        'user_id' => $user_id,
        'username' => $username,
        'file_id' => $file_id,
        'filename' => $filename,
        'action' => $action,
        'success' => $success,
        'message' => $detailed_message
    ];
    
    // D'abord, stocker dans la blockchain
    $blockchain_hash = publishToBlockchain('file_operations_stream', $log_data);
    
    // Si la blockchain a échoué, on ne continue pas avec la base de données
    if ($blockchain_hash === false) {
        error_log('Échec de l\'enregistrement dans la blockchain');
        return false;
    }
    
    // Ensuite, stocker dans la base de données
    $query = "INSERT INTO access_logs 
              (timestamp, ip_address, user_id, file_id, filename, action, success, message, blockchain_hash) 
              VALUES (NOW(), :ip, :user_id, :file_id, :filename, :action, :success, :message, :blockchain_hash)";
    
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            ':ip' => $ip_address,
            ':user_id' => $user_id,
            ':file_id' => $file_id,
            ':filename' => $filename,
            ':action' => $action,
            ':success' => $success ? 1 : 0,
            ':message' => $detailed_message,
            ':blockchain_hash' => $blockchain_hash
        ]);
        return true;
    } catch (PDOException $e) {
        error_log('Erreur de journalisation dans la base de données: ' . $e->getMessage());
        return false;
    }
}

/**
 * Génère un token CSRF et le stocke dans la session
 * @return string Le token CSRF généré
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Vérifie si le token CSRF fourni correspond à celui dans la session
 * @param string $token Le token à vérifier
 * @return bool True si le token est valide, false sinon
 */
function validateCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || !$token) {
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Nettoie et échappe une entrée pour prévenir les attaques XSS
 * @param string $data Les données à nettoyer
 * @return string Les données nettoyées
 */
function sanitizeOutput($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

/**
 * Récupère les données d'un stream blockchain
 * @param string $stream_name Nom du stream
 * @return array|null Données du stream ou null si erreur
 */
function getStreamData($stream_name) {
    try {
        $url = "http://localhost:3000/api/streams/" . urlencode($stream_name);
        $response = @file_get_contents($url);
        
        if ($response === FALSE) {
            error_log("Erreur lors de la récupération des données du stream: " . $stream_name);
            return null;
        }
        
        $data = json_decode($response, true);
        if (!$data || !isset($data['data'])) {
            error_log("Format de réponse invalide pour le stream: " . $stream_name);
            return null;
        }
        
        return $data['data'];
    } catch (Exception $e) {
        error_log("Exception lors de la récupération des données du stream: " . $e->getMessage());
        return null;
    }
}