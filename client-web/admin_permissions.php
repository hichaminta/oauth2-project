<?php
session_start();
include_once 'variable.php';
if (!isset($_SESSION['access_token'])) {
    header("Location: index.php");
    exit();
}

// Vérifier si le token est encore valide
if (!isset($_SESSION['token_created']) || !isset($_SESSION['expires_in'])) {
    header("Location: logout.php");
    exit();
}
if (time() > $_SESSION['token_created'] + $_SESSION['expires_in']) {
    header("Location: view.php");
    exit();
}

// Vérifier si l'utilisateur a le scope admin
$resource_url = $domainenameprressources . "resource.php?access_token=" . $_SESSION['access_token'];
$response = file_get_contents($resource_url);
$data = json_decode($response, true);

if (!isset($data['user']['scopes']) || !in_array('admin', $data['user']['scopes'])) {
    header("Location: view.php");
    exit();
}

// Connexion à la base de données via API sécurisée
$api_url = $domainenameprressources . "admin_api.php?access_token=" . $_SESSION['access_token'];

// Traitement des actions (ajout/suppression de permissions)
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];

        switch ($action) {
            case 'update_file_permission':
                if (isset($_POST['can_read'])) {
                                    $can_read = $_POST['can_read'];

                }
                else{
                $can_read = 0;
                }
                if (isset($_POST['can_write'])) {
                $can_write = $_POST['can_write'];

                }else{
                    $can_write = 0;
 
                }
                if (isset($_POST['file_id'], $_POST['user_id'],)) {

                    $params = http_build_query([
                        'action' => 'update_file_permission',
                        'file_id' => $_POST['file_id'],
                        'user_id' => $_POST['user_id'],
                        'can_read' => $can_read,
                        'can_write' => $can_write,
                    ]);

                    $result = file_get_contents($api_url . '&' . $params);
                    $response = json_decode($result, true);

                    if (isset($response['success']) && $response['success']) {
                        $message = 'Permissions mises à jour avec succès';
                    } else {
                        $error = $response['error'] ?? 'Erreur lors de la mise à jour des permissions';
                    }
                }
                break;

            case 'update_user_role':
                if (isset($_POST['user_id'], $_POST['role'], $_POST['available_scopes'])) {
                    $params = http_build_query([
                        'action' => 'update_user_role',
                        'user_id' => $_POST['user_id'],
                        'role' => $_POST['role'],
                        'available_scopes' => $_POST['available_scopes']
                    ]);

                    $result = file_get_contents($api_url . '&' . $params);
                    $response = json_decode($result, true);

                    if (isset($response['success']) && $response['success']) {
                        $message = 'Rôle utilisateur mis à jour avec succès';
                    } else {
                        $error = $response['error'] ?? 'Erreur lors de la mise à jour du rôle';
                    }
                }
                break;

            case 'add_permission':
                if (isset($_POST['file_id'], $_POST['user_id'])) {
                    $params = http_build_query([
                        'action' => 'add_permission',
                        'file_id' => $_POST['file_id'],
                        'user_id' => $_POST['user_id'],
                        'can_read' => isset($_POST['can_read']) ? 1 : 0,
                        'can_write' => isset($_POST['can_write']) ? 1 : 0
                    ]);
                    $response = json_decode(file_get_contents($api_url . '&' . $params), true);

                    if (isset($response['success']) && $response['success']) {
                        $message = 'Permission ajoutée avec succès.';
                    } else {
                        $error = $response['error'] ?? 'Erreur lors de l\'ajout de la permission.';
                    }
                }
                break;

            case 'delete_permission':
                if (isset($_POST['file_id'], $_POST['user_id'])) {
                    $params = http_build_query([
                        'action' => 'delete_permission',
                        'file_id' => $_POST['file_id'],
                        'user_id' => $_POST['user_id']
                    ]);
                    $response = json_decode(file_get_contents($api_url . '&' . $params), true);

                    if (isset($response['success']) && $response['success']) {
                        $message = 'Permission supprimée avec succès.';
                    } else {
                        $error = $response['error'] ?? 'Erreur lors de la suppression de la permission.';
                    }
                }
                break;
        }
    }
}

// Récupérer la liste des utilisateurs
$users_response = file_get_contents($api_url . '&action=get_users');
$users_data = json_decode($users_response, true);
$users = $users_data['users'] ?? [];

// Récupérer la liste des fichiers
$files_response = file_get_contents($api_url . '&action=get_files');
$files_data = json_decode($files_response, true);
$files = $files_data['files'] ?? [];

// Récupérer les permissions actuelles
$permissions_response = file_get_contents($api_url . '&action=get_permissions');
$permissions_data = json_decode($permissions_response, true);
$permissions = $permissions_data['permissions'] ?? [];
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QuickView - Gestion des Permissions</title>
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body>
    <div class="header">
        <div class="header-content">
            <h1>QuickView</h1>
            <nav class="nav-menu">
                <a href="view.php" class="nav-link">Mes fichiers</a>
                <a href="admin_permissions.php" class="nav-link active">Permissions</a>
                <a href="admin_log.php" class="nav-link">Logs</a>
                <a href="blochaine_adm_token.php" class="nav-link">Blockchain</a>
                <a href="blockchain_logs.php" class="nav-link">Logs Blockchain</a>
                <a href="logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
            </nav>
        </div>
    </div>

    <div class="container">
        <?php if (!empty($message)): ?>
            <div class="alert alert-success" style="margin-top: 2rem;">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error" style="margin-top: 2rem;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <div class="card" style="margin-top: 2rem;">
            <h2>Gestion des rôles utilisateurs</h2>
            
            <table class="table">
                <thead>
                    <tr>
                        <th>Utilisateur</th>
                        <th>Email</th>
                        <th>Rôle</th>
                        <th>Scopes disponibles</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= htmlspecialchars($user['username']) ?></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td><?= htmlspecialchars($user['role']) ?></td>
                            <td><?= htmlspecialchars($user['available_scopes']) ?></td>
                            <td>
                                <button class="btn btn-primary" onclick="showEditRoleModal(<?= $user['id'] ?>, '<?= htmlspecialchars($user['username']) ?>', '<?= htmlspecialchars($user['role']) ?>', '<?= htmlspecialchars($user['available_scopes']) ?>')">
                                    <i class="fas fa-edit"></i> Modifier
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="card" style="margin-top: 2rem;">
            <h2>Permissions par fichier</h2>
            
            <div style="margin-bottom: 1rem; text-align: right;">
                <button class="btn btn-primary" onclick="showAddPermissionModal()">
                    <i class="fas fa-plus"></i> Ajouter une permission
                </button>
            </div>
            
            <table class="table">
                <thead>
                    <tr>
                        <th>Fichier</th>
                        <th>Utilisateur</th>
                        <th>Peut lire</th>
                        <th>Peut modifier</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($permissions as $perm): ?>
                        <tr>
                            <td><?= htmlspecialchars($perm['filename']) ?></td>
                            <td><?= htmlspecialchars($perm['username']) ?></td>
                            <td><?= $perm['can_read'] ? '<i class="fas fa-check" style="color: green;"></i>' : '<i class="fas fa-times" style="color: red;"></i>' ?></td>
                            <td><?= $perm['can_write'] ? '<i class="fas fa-check" style="color: green;"></i>' : '<i class="fas fa-times" style="color: red;"></i>' ?></td>
                            <td>
                                <button class="btn btn-primary" onclick="showEditPermissionModal(<?= $perm['file_id'] ?>, <?= $perm['user_id'] ?>, '<?= htmlspecialchars($perm['filename']) ?>', '<?= htmlspecialchars($perm['username']) ?>', <?= $perm['can_read'] ?>, <?= $perm['can_write'] ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-secondary" onclick="showDeletePermissionModal(<?= $perm['file_id'] ?>, <?= $perm['user_id'] ?>, '<?= htmlspecialchars($perm['filename']) ?>', '<?= htmlspecialchars($perm['username']) ?>')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modale pour l'édition des rôles -->
    <div id="roleModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 100;">
        <div class="card" style="width: 500px; margin: 100px auto; padding: 2rem;">
            <h3 style="margin-bottom: 1.5rem;">Modifier le rôle utilisateur</h3>
            <form id="roleForm" method="POST">
                <input type="hidden" name="action" value="update_user_role">
                <input type="hidden" name="user_id" id="roleUserId">

                <div class="form-group">
                    <label class="form-label">Utilisateur:</label>
                    <span id="roleUsername" style="font-weight: 500;"></span>
                </div>

                <div class="form-group">
                    <label class="form-label" for="roleSelect">Rôle:</label>
                    <select name="role" id="roleSelect" class="form-input">
                        <option value="user">Utilisateur</option>
                        <option value="admin">Administrateur</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Scopes disponibles:</label>
                    <div style="display: flex; gap: 1rem; margin-top: 0.5rem;">
                        <label style="display: flex; align-items: center;">
                            <input type="checkbox" name="scope_read" value="read" checked>
                            <span style="margin-left: 0.5rem;">Lecture</span>
                        </label>
                        <label style="display: flex; align-items: center;">
                            <input type="checkbox" name="scope_write" value="write">
                            <span style="margin-left: 0.5rem;">Écriture</span>
                        </label>
                        <label style="display: flex; align-items: center;">
                            <input type="checkbox" name="scope_admin" value="admin">
                            <span style="margin-left: 0.5rem;">Admin</span>
                        </label>
                    </div>
                </div>

                <div style="text-align: right; margin-top: 1.5rem;">
                    <button type="button" class="btn btn-secondary" onclick="hideModals()">Annuler</button>
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modale pour l'édition des permissions -->
    <div id="permissionModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 100;">
        <div class="card" style="width: 500px; margin: 100px auto; padding: 2rem;">
            <h3 style="margin-bottom: 1.5rem;">Modifier les permissions</h3>
            <form id="permissionForm" method="POST">
                <input type="hidden" name="action" value="update_file_permission">
                <input type="hidden" name="file_id" id="permFileId">
                <input type="hidden" name="user_id" id="permUserId">

                <div class="form-group">
                    <label class="form-label">Fichier:</label>
                    <span id="permFilename" style="font-weight: 500;"></span>
                </div>

                <div class="form-group">
                    <label class="form-label">Utilisateur:</label>
                    <span id="permUsername" style="font-weight: 500;"></span>
                </div>

                <div class="form-group">
                    <label class="form-label">
                        <input type="checkbox" name="can_read" value="1" id="permCanRead">
                        <span style="margin-left: 0.5rem;">Peut lire</span>
                    </label>
                </div>

                <div class="form-group">
                    <label class="form-label">
                        <input type="checkbox" name="can_write" value="1" id="permCanWrite">
                        <span style="margin-left: 0.5rem;">Peut modifier</span>
                    </label>
                </div>

                <div style="text-align: right; margin-top: 1.5rem;">
                    <button type="button" class="btn btn-secondary" onclick="hideModals()">Annuler</button>
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modale pour ajouter une permission -->
    <div id="addPermissionModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 100;">
        <div class="card" style="width: 500px; margin: 100px auto; padding: 2rem;">
            <h3 style="margin-bottom: 1.5rem;">Ajouter une permission</h3>
            <form id="addPermissionForm" method="POST">
                <input type="hidden" name="action" value="add_permission">

                <div class="form-group">
                    <label class="form-label" for="add_perm_file">Fichier:</label>
                    <select name="file_id" id="add_perm_file" class="form-input" required>
                        <option value="">-- Sélectionner un fichier --</option>
                        <?php foreach ($files as $file): ?>
                            <option value="<?= $file['id'] ?>"><?= htmlspecialchars($file['filename']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="add_perm_user">Utilisateur:</label>
                    <select name="user_id" id="add_perm_user" class="form-input" required>
                        <option value="">-- Sélectionner un utilisateur --</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['username']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">
                        <input type="checkbox" name="can_read" value="1" checked>
                        <span style="margin-left: 0.5rem;">Peut lire</span>
                    </label>
                </div>

                <div class="form-group">
                    <label class="form-label">
                        <input type="checkbox" name="can_write" value="1">
                        <span style="margin-left: 0.5rem;">Peut modifier</span>
                    </label>
                </div>

                <div style="text-align: right; margin-top: 1.5rem;">
                    <button type="button" class="btn btn-secondary" onclick="hideModals()">Annuler</button>
                    <button type="submit" class="btn btn-primary">Ajouter</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modale pour la suppression de permission -->
    <div id="deletePermissionModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 100;">
        <div class="card" style="width: 500px; margin: 100px auto; padding: 2rem;">
            <h3 style="margin-bottom: 1.5rem;">Supprimer la permission</h3>
            <form id="deletePermissionForm" method="POST">
                <input type="hidden" name="action" value="delete_permission">
                <input type="hidden" name="file_id" id="deleteFileId">
                <input type="hidden" name="user_id" id="deleteUserId">

                <div class="form-group">
                    <p>Êtes-vous sûr de vouloir supprimer définitivement cette permission ?</p>
                    <p style="margin-top: 1rem;"><strong>Fichier:</strong> <span id="deleteFilename"></span></p>
                    <p><strong>Utilisateur:</strong> <span id="deleteUsername"></span></p>
                </div>

                <div style="text-align: right; margin-top: 1.5rem;">
                    <button type="button" class="btn btn-secondary" onclick="hideModals()">Annuler</button>
                    <button type="submit" class="btn btn-primary">Supprimer</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Fonctions pour les modales
        function hideModals() {
            document.getElementById('roleModal').style.display = 'none';
            document.getElementById('permissionModal').style.display = 'none';
            document.getElementById('addPermissionModal').style.display = 'none';
            document.getElementById('deletePermissionModal').style.display = 'none';
        }

        function showEditRoleModal(userId, username, role, scopes) {
            document.getElementById('roleUserId').value = userId;
            document.getElementById('roleUsername').textContent = username;
            document.getElementById('roleSelect').value = role;

            // Réinitialiser les cases à cocher
            document.querySelector('input[name="scope_read"]').checked = false;
            document.querySelector('input[name="scope_write"]').checked = false;
            document.querySelector('input[name="scope_admin"]').checked = false;

            // Cocher les cases selon les scopes disponibles
            const scopeArray = scopes.split(' ');
            for (const scope of scopeArray) {
                const checkbox = document.querySelector(`input[name="scope_${scope}"]`);
                if (checkbox) checkbox.checked = true;
            }

            document.getElementById('roleModal').style.display = 'block';
        }

        function showEditPermissionModal(fileId, userId, filename, username, canRead, canWrite) {
            document.getElementById('permFileId').value = fileId;
            document.getElementById('permUserId').value = userId;
            document.getElementById('permFilename').textContent = filename;
            document.getElementById('permUsername').textContent = username;
            document.getElementById('permCanRead').checked = canRead === 1;
            document.getElementById('permCanWrite').checked = canWrite === 1;

            document.getElementById('permissionModal').style.display = 'block';
        }

        function showAddPermissionModal() {
            document.getElementById('addPermissionModal').style.display = 'block';
        }

        function showDeletePermissionModal(fileId, userId, filename, username) {
            document.getElementById('deleteFileId').value = fileId;
            document.getElementById('deleteUserId').value = userId;
            document.getElementById('deleteFilename').textContent = filename;
            document.getElementById('deleteUsername').textContent = username;

            document.getElementById('deletePermissionModal').style.display = 'block';
        }

        // Préparer les formulaires pour l'envoi
        document.getElementById('roleForm').addEventListener('submit', function(e) {
            e.preventDefault();

            // Récupérer les scopes sélectionnés
            const scopes = [];
            if (document.querySelector('input[name="scope_read"]').checked) scopes.push('read');
            if (document.querySelector('input[name="scope_write"]').checked) scopes.push('write');
            if (document.querySelector('input[name="scope_admin"]').checked) scopes.push('admin');

            // Ajouter les scopes au formulaire
            const scopeInput = document.createElement('input');
            scopeInput.type = 'hidden';
            scopeInput.name = 'available_scopes';
            scopeInput.value = scopes.join(' ');
            this.appendChild(scopeInput);

            this.submit();
        });
    </script>
</body>

</html>