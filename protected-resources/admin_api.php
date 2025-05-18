<?php
// admin_api.php - API sécurisée pour les opérations administratives
session_start();
include_once 'fonction.php';

if (!isset($_GET['access_token'])) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['error' => 'Token d\'accès requis']);
    exit();
}

$access_token = $_GET['access_token'];
require_once '../server-oauth/config.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn = $pdo;

    $stmt = $conn->prepare("SELECT t.user_id, t.expires, t.scope, u.username, u.email, r.role 
                            FROM access_tokens t
                            JOIN users u ON t.user_id = u.id
                            JOIN user_roles r ON u.id = r.user_id
                            WHERE t.access_token = ? AND t.expires > NOW()");
    $stmt->execute([$access_token]);
    $token_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$token_data) {
        header('HTTP/1.1 401 Unauthorized');
        echo json_encode(['error' => 'Token invalide ou expiré']);
        logAccess(null, null, null, false, 'Token invalide ou expiré', 'auth_check');
        exit();
    }

    $user_id = $token_data['user_id'] ?? null;
    $scopes = explode(' ', $token_data['scope']);
    if (!in_array('admin', $scopes)) {
        header('HTTP/1.1 403 Forbidden');
        echo json_encode(['error' => 'Privilèges administrateur requis']);
        logAccess($user_id, null, null, false, 'Privilèges admin requis', 'scope_check');
        exit();
    }

    $action = $_GET['action'] ?? '';

    switch ($action) {
        case 'get_users':
            $stmt = $conn->query("SELECT u.id, u.username, u.email, r.role, r.available_scopes 
                                  FROM users u
                                  JOIN user_roles r ON u.id = r.user_id");
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'users' => $users]);
            break;

        case 'get_files':
            $stmt = $conn->query("SELECT id, filename, path as file_path, size FROM files");
            $files = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'files' => $files]);
            break;

        case 'get_permissions':
            $stmt = $conn->query("SELECT p.*, f.filename, u.username 
                                  FROM file_permissions p
                                  JOIN files f ON p.file_id = f.id
                                  JOIN users u ON p.user_id = u.id
                                  ORDER BY f.filename, u.username");
            $permissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'permissions' => $permissions]);
            break;

        case 'update_file_permission':
            if (isset($_GET['file_id'], $_GET['user_id'])) {
                $file_id = (int)$_GET['file_id'];
                $target_user_id = (int)$_GET['user_id'];
                $can_read = isset($_GET['can_read']) ? (int)$_GET['can_read'] : 0;
                $can_write = isset($_GET['can_write']) ? (int)$_GET['can_write'] : 0;

                $stmt = $conn->prepare("SELECT id FROM file_permissions WHERE file_id = ? AND user_id = ?");
                $stmt->execute([$file_id, $target_user_id]);
                $exists = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($exists) {
                    $stmt = $conn->prepare("UPDATE file_permissions SET can_read = ?, can_write = ? WHERE file_id = ? AND user_id = ?");
                    $result = $stmt->execute([$can_read, $can_write, $file_id, $target_user_id]);
                } else {
                    $stmt = $conn->prepare("INSERT INTO file_permissions (file_id, user_id, can_read, can_write) VALUES (?, ?, ?, ?)");
                    $result = $stmt->execute([$file_id, $target_user_id, $can_read, $can_write]);
                }

                logAccess($user_id, $file_id, null, $result, $result ? 'Permission mise à jour' : 'Échec de la mise à jour', 'update_file_permission');
                echo json_encode(['success' => $result]);
            } else {
                logAccess($user_id, null, null, false, 'Paramètres manquants', 'update_file_permission');
                echo json_encode(['success' => false, 'error' => 'Paramètres manquants']);
            }
            break;

        case 'update_user_role':
            if (isset($_GET['user_id'], $_GET['role'], $_GET['available_scopes'])) {
                $target_user_id = (int)$_GET['user_id'];
                $role = $_GET['role'];
                $available_scopes = $_GET['available_scopes'];

                if (!in_array($role, ['user', 'admin'])) {
                    logAccess($user_id, null, null, false, 'Rôle invalide', 'update_user_role');
                    echo json_encode(['success' => false, 'error' => 'Rôle invalide']);
                    break;
                }

                $stmt = $conn->prepare("UPDATE user_roles SET role = ?, available_scopes = ? WHERE user_id = ?");
                $result = $stmt->execute([$role, $available_scopes, $target_user_id]);
                logAccess($user_id, null, null, $result, $result ? 'Rôle mis à jour' : 'Échec de la mise à jour', 'update_user_role');
                echo json_encode(['success' => $result]);
            } else {
                logAccess($user_id, null, null, false, 'Paramètres manquants', 'update_user_role');
                echo json_encode(['success' => false, 'error' => 'Paramètres manquants']);
            }
            break;

        case 'add_permission':
            if (isset($_GET['file_id'], $_GET['user_id'])) {
                $file_id = (int)$_GET['file_id'];
                $target_user_id = (int)$_GET['user_id'];
                $can_read = isset($_GET['can_read']) ? (int)$_GET['can_read'] : 0;
                $can_write = isset($_GET['can_write']) ? (int)$_GET['can_write'] : 0;

                $stmt = $conn->prepare("SELECT id FROM file_permissions WHERE file_id = ? AND user_id = ?");
                $stmt->execute([$file_id, $target_user_id]);
                $exists = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($exists) {
                    logAccess($user_id, $file_id, null, false, 'Permission déjà existante', 'add_permission');
                    echo json_encode(['success' => false, 'error' => 'Cette permission existe déjà']);
                } else {
                    $stmt = $conn->prepare("INSERT INTO file_permissions (file_id, user_id, can_read, can_write) VALUES (?, ?, ?, ?)");
                    $result = $stmt->execute([$file_id, $target_user_id, $can_read, $can_write]);
                    logAccess($user_id, $file_id, null, $result, $result ? 'Permission ajoutée' : 'Échec de l\'ajout', 'add_permission');
                    echo json_encode(['success' => $result]);
                }
            } else {
                logAccess($user_id, null, null, false, 'Paramètres manquants', 'add_permission');
                echo json_encode(['success' => false, 'error' => 'Paramètres manquants']);
            }
            break;

        case 'delete_permission':
            if (isset($_GET['file_id'], $_GET['user_id'])) {
                $file_id = (int)$_GET['file_id'];
                $target_user_id = (int)$_GET['user_id'];

                $stmt = $conn->prepare("DELETE FROM file_permissions WHERE file_id = ? AND user_id = ?");
                $result = $stmt->execute([$file_id, $target_user_id]);
                logAccess($user_id, $file_id, null, $result, $result ? 'Permission supprimée' : 'Échec de suppression', 'delete_permission');
                echo json_encode(['success' => $result]);
            } else {
                logAccess($user_id, null, null, false, 'Paramètres manquants', 'delete_permission');
                echo json_encode(['success' => false, 'error' => 'Paramètres manquants']);
            }
            break;

        case 'get_user_info':
            if (isset($_GET['user_id'])) {
                $target_user_id = (int)$_GET['user_id'];

                $stmt = $conn->prepare("SELECT u.id, u.username, u.email, r.role, r.available_scopes 
                                       FROM users u
                                       JOIN user_roles r ON u.id = r.user_id
                                       WHERE u.id = ?");
                $stmt->execute([$target_user_id]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                echo json_encode($user ? ['success' => true, 'user' => $user] : ['success' => false, 'error' => 'Utilisateur non trouvé']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Paramètre user_id manquant']);
            }
            break;

        case 'get_file_info':
            if (isset($_GET['file_id'])) {
                $file_id = (int)$_GET['file_id'];

                $stmt = $conn->prepare("SELECT id, filename, path as file_path, size, created_at FROM files WHERE id = ?");
                $stmt->execute([$file_id]);
                $file = $stmt->fetch(PDO::FETCH_ASSOC);
                logAccess($user_id, $file_id, $file['filename'] ?? null, $file ? true : false, $file ? 'Info fichier récupérée' : 'Fichier non trouvé', 'get_file_info');
            } else {
                logAccess($user_id, null, null, false, 'Paramètre file_id manquant', 'get_file_info');
            }
            break;

        default:
            logAccess($user_id, null, null, false, 'Action non reconnue', 'unknown_action');
            echo json_encode(['success' => false, 'error' => 'Action non reconnue']);
            break;
    }

} catch (PDOException $e) {
    logAccess($user_id ?? null, null, null, false, 'Erreur PDO', 'exception');
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['error' => 'Erreur de base de données']);
    exit();
} catch (Exception $e) {
    logAccess($user_id ?? null, null, null, false, 'Erreur générale', 'exception');
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['error' => 'Erreur interne du serveur']);
    exit();
}
