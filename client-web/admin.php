<?php
session_start();
if (!isset($_SESSION['access_token'])) {
    header("Location: index.php");
    exit;
}

// Vérifier les permissions admin
$validation_url = "http://localhost/oauth2-project/server-oauth/validate_token.php?access_token=" . urlencode($_SESSION['access_token']);
$validation_response = @file_get_contents($validation_url);
$token_data = json_decode($validation_response, true);

// Vérification des scopes disponibles
$scopes = explode(' ', $token_data['scope'] ?? '');
if (!in_array('admin', $scopes)) {
    header("Location: view.php");
    exit;
}

// Connexion à la base de données
require_once __DIR__ . '/../server-oauth/database.php';

// Récupérer tous les utilisateurs
$users_stmt = $pdo->query("SELECT id, username, email FROM users");
$users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer tous les fichiers disponibles
$files_path = __DIR__ . '/../protected-resources/ressources/';
$all_files = scandir($files_path);
$all_files = array_diff($all_files, ['.', '..']);

// Traitement des actions
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ajouter une permission
    if (isset($_POST['add_permission'])) {
        $user_id = intval($_POST['user_id']);
        $file_name = $_POST['file_name'];
        $access_type = $_POST['access_type'];
        
        // Vérifier si la permission existe déjà
        $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM files_permissions WHERE user_id = ? AND file_name = ?");
        $check_stmt->execute([$user_id, $file_name]);
        $exists = $check_stmt->fetchColumn();
        
        if ($exists) {
            // Mettre à jour la permission existante
            $update_stmt = $pdo->prepare("UPDATE files_permissions SET access_type = ? WHERE user_id = ? AND file_name = ?");
            $update_stmt->execute([$access_type, $user_id, $file_name]);
            $message = "Permission mise à jour avec succès.";
        } else {
            // Ajouter une nouvelle permission
            $insert_stmt = $pdo->prepare("INSERT INTO files_permissions (user_id, file_name, access_type) VALUES (?, ?, ?)");
            $insert_stmt->execute([$user_id, $file_name, $access_type]);
            $message = "Permission ajoutée avec succès.";
        }
    }
    
    // Supprimer une permission
    if (isset($_POST['delete_permission'])) {
        $permission_id = intval($_POST['permission_id']);
        $delete_stmt = $pdo->prepare("DELETE FROM files_permissions WHERE id = ?");
        $delete_stmt->execute([$permission_id]);
        $message = "Permission supprimée avec succès.";
    }
}

// Récupérer toutes les permissions existantes
$permissions_stmt = $pdo->query("
    SELECT fp.id, fp.file_name, fp.access_type, fp.user_id, u.username 
    FROM files_permissions fp
    JOIN users u ON fp.user_id = u.id
    ORDER BY fp.file_name, u.username
");
$permissions = $permissions_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration des permissions</title>
    <link rel="stylesheet" href="css/view.css">
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f2f2f2;
        }
        tr:hover {
            background-color: #f5f5f5;
        }
        .form-container {
            margin: 20px 0;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
        .form-group {
            margin-bottom: 10px;
        }
        .form-group label {
            display: inline-block;
            width: 100px;
        }
        .success-message {
            color: green;
            font-weight: bold;
        }
        .error-message {
            color: red;
            font-weight: bold;
        }
        .action-buttons {
            display: flex;
            gap: 5px;
        }
    </style>
</head>
<body>
    <h2>Administration des permissions de fichiers</h2>
    
    <a href="view.php" class="logout-btn" style="background-color: #28a745;">Retour à mes fichiers</a>
    
    <?php if ($message): ?>
        <p class="success-message"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <p class="error-message"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
    
    <div class="form-container">
        <h3>Ajouter/Modifier une permission</h3>
        <form method="POST">
            <div class="form-group">
                <label for="user_id">Utilisateur:</label>
                <select name="user_id" id="user_id" required>
                    <?php foreach ($users as $user): ?>
                        <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['username']) ?> (<?= htmlspecialchars($user['email']) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="file_name">Fichier:</label>
                <select name="file_name" id="file_name" required>
                    <?php foreach ($all_files as $file): ?>
                        <option value="<?= $file ?>"><?= htmlspecialchars($file) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="access_type">Type d'accès:</label>
                <select name="access_type" id="access_type" required>
                    <option value="read">Lecture</option>
                    <option value="write">Écriture</option>
                </select>
            </div>
            
            <button type="submit" name="add_permission" value="1">Ajouter/Modifier</button>
        </form>
    </div>
    
    <h3>Permissions existantes</h3>
    <table>
        <thead>
            <tr>
                <th>Fichier</th>
                <th>Utilisateur</th>
                <th>Type d'accès</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($permissions) > 0): ?>
                <?php foreach ($permissions as $permission): ?>
                    <tr>
                        <td><?= htmlspecialchars($permission['file_name']) ?></td>
                        <td><?= htmlspecialchars($permission['username']) ?></td>
                        <td><?= htmlspecialchars($permission['access_type']) ?></td>
                        <td>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="permission_id" value="<?= $permission['id'] ?>">
                                <button type="submit" name="delete_permission" value="1" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette permission?')">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4">Aucune permission définie.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    
    <a href="logout.php" class="logout-btn">Déconnexion</a>
</body>
</html>