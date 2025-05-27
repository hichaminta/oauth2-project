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
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QuickView - Logs d'accès</title>
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .filters {
            background-color: var(--white);
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
            padding: 1.5rem;
            margin-bottom: 2rem;
            border-top: 3px solid var(--primary-color);
        }

        .filter-label {
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: var(--text-color);
        }

        .refresh-btn {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: var(--primary-color);
            color: var(--white);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            transition: transform 0.2s ease;
            border: none;
            cursor: pointer;
        }

        .refresh-btn:hover {
            transform: rotate(180deg);
            background-color: var(--secondary-color);
        }

        .loading {
            text-align: center;
            padding: 3rem;
        }

        .spinner-border {
            color: var(--primary-color);
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>QuickView</h1>
            <nav class="nav-menu">
                <a href="view.php" class="nav-link">Mes fichiers</a>
                <a href="admin_permissions.php" class="nav-link">Permissions</a>
                <a href="admin_log.php" class="nav-link active">Logs</a>
                <a href="blochaine_adm_token.php" class="nav-link">Blockchain</a>
                <a href="blockchain_logs.php" class="nav-link">Logs Blockchain</a>
                <a href="logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
            </nav>
        </div>
    </div>

    <div class="container">
        <div class="card" style="margin-top: 2rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <h2><i class="fas fa-history"></i> Logs de connexion et d'accès aux fichiers</h2>
                <div id="loading-indicator">
                    <i class="fas fa-spinner fa-spin"></i> Chargement...
                </div>
            </div>

            <div class="filters">
                <div class="form-group" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                    <div>
                        <label class="filter-label">Action</label>
                        <select class="form-input" id="actionFilter">
                            <option value="">Toutes les actions</option>
                            <option value="upload">Upload</option>
                            <option value="download">Download</option>
                            <option value="delete">Delete</option>
                            <option value="login">Login</option>
                        </select>
                    </div>
                    <div>
                        <label class="filter-label">Statut</label>
                        <select class="form-input" id="statusFilter">
                            <option value="">Tous les statuts</option>
                            <option value="success">Succès</option>
                            <option value="error">Échec</option>
                        </select>
                    </div>
                    <div>
                        <label class="filter-label">Date</label>
                        <input type="date" class="form-input" id="dateFilter">
                    </div>
                    <div>
                        <label class="filter-label">Recherche</label>
                        <input type="text" class="form-input" id="searchFilter" placeholder="Rechercher...">
                    </div>
                </div>
            </div>

            <div style="overflow-x: auto;">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Horodatage</th>
                            <th>IP</th>
                            <th>User ID</th>
                            <th>Fichier ID</th>
                            <th>Nom de fichier</th>
                            <th>Action</th>
                            <th>Token</th>
                            <th>Blockchain</th>
                            <th>Succès</th>
                            <th>Message</th>
                        </tr>
                    </thead>
                    <tbody id="log-rows">
                        <!-- Données seront insérées ici -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <button id="refreshBtn" class="refresh-btn" title="Actualiser">
        <i class="fas fa-sync-alt"></i>
    </button>

    <script>
        let allLogs = [];
        const logsContainer = document.getElementById('log-rows');
        const actionFilter = document.getElementById('actionFilter');
        const statusFilter = document.getElementById('statusFilter');
        const dateFilter = document.getElementById('dateFilter');
        const searchFilter = document.getElementById('searchFilter');
        const refreshBtn = document.getElementById('refreshBtn');
        const loadingIndicator = document.getElementById('loading-indicator');
        const apiUrl = "http://localhost/oauth2-project/protected-resources/admin_log_api.php?access_token=<?= $_SESSION['access_token'] ?>";

        function formatDate(timestamp) {
            return new Date(timestamp).toLocaleString('fr-FR', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        function createLogRow(log) {
            const blockchainLink = log.blockchain_hash ? `
                <a href="blockchain_logs.php?hash=${log.blockchain_hash}" class="btn btn-primary" style="font-size: 0.8rem; padding: 0.3rem 0.8rem;">
                    <i class="fas fa-link"></i> Voir
                </a>
            ` : '<span class="text-muted">-</span>';

            const tokenDisplay = log.user_token ? `
                <div class="token-display" style="max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="${log.user_token}">
                    <i class="fas fa-key"></i> ${log.user_token.substring(0, 10)}...
                    <a href="blochaine_adm_token.php?token=${log.user_token}" class="btn btn-primary" style="font-size: 0.8rem; padding: 0.3rem 0.8rem; margin-left: 0.5rem;">
                        <i class="fas fa-search"></i> Voir
                    </a>
                </div>
            ` : '<span class="text-muted">-</span>';

            return `
                <tr>
                    <td>${log.id}</td>
                    <td><i class="fas fa-clock"></i> ${formatDate(log.timestamp)}</td>
                    <td><i class="fas fa-network-wired"></i> ${log.ip_address}</td>
                    <td><i class="fas fa-user"></i> ${log.user_id}</td>
                    <td>${log.file_id}</td>
                    <td><i class="fas fa-file"></i> ${log.filename}</td>
                    <td><span class="btn btn-secondary" style="font-size: 0.8rem; padding: 0.3rem 0.8rem;">${log.action}</span></td>
                    <td>${tokenDisplay}</td>
                    <td>${blockchainLink}</td>
                    <td>
                        ${log.success 
                            ? '<i class="fas fa-check" style="color: green;"></i>' 
                            : '<i class="fas fa-times" style="color: red;"></i>'
                        }
                    </td>
                    <td>${log.message}</td>
                </tr>
            `;
        }

        function filterLogs() {
            const action = actionFilter.value.toLowerCase();
            const status = statusFilter.value;
            const date = dateFilter.value;
            const search = searchFilter.value.toLowerCase();

            const filteredLogs = allLogs.filter(log => {
                const logAction = (log.action || '').toLowerCase();
                const logSuccess = log.success;
                const logDate = new Date(log.timestamp).toISOString().split('T')[0];
                const logMessage = (log.message || '').toLowerCase();
                const logFilename = (log.filename || '').toLowerCase();
                const logUserId = (log.user_id || '').toString().toLowerCase();
                const logToken = (log.user_token || '').toLowerCase();

                const matchesAction = !action || logAction === action;
                const matchesStatus = !status || 
                    (status === 'success' && logSuccess) || 
                    (status === 'error' && !logSuccess);
                const matchesDate = !date || logDate === date;
                const matchesSearch = !search || 
                    logMessage.includes(search) || 
                    logFilename.includes(search) || 
                    logUserId.includes(search) ||
                    logToken.includes(search);

                return matchesAction && matchesStatus && matchesDate && matchesSearch;
            });

            displayLogs(filteredLogs);
        }

        function displayLogs(logs) {
            if (logs.length === 0) {
                logsContainer.innerHTML = `
                    <tr>
                        <td colspan="9" class="text-center">
                            <div class="alert alert-error">
                                <i class="fas fa-info-circle me-2"></i>
                                Aucun log ne correspond aux critères de recherche.
                            </div>
                        </td>
                    </tr>
                `;
                return;
            }

            logsContainer.innerHTML = logs.map(log => createLogRow(log)).join('');
        }

        async function loadLogs() {
            try {
                loadingIndicator.style.display = 'block';
                logsContainer.innerHTML = `
                    <tr>
                        <td colspan="9" class="text-center">
                            <div class="loading">
                                <div class="spinner-border" role="status">
                                    <span class="visually-hidden">Chargement...</span>
                                </div>
                                <p class="mt-2">Chargement des logs...</p>
                            </div>
                        </td>
                    </tr>
                `;

                const response = await fetch(apiUrl);
                const data = await response.json();

                if (data.error) {
                    throw new Error(data.error);
                }

                allLogs = data;
                filterLogs();
            } catch (error) {
                logsContainer.innerHTML = `
                    <tr>
                        <td colspan="9" class="text-center">
                            <div class="alert alert-error">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                Erreur lors du chargement des logs: ${error.message}
                            </div>
                        </td>
                    </tr>
                `;
            } finally {
                loadingIndicator.style.display = 'none';
            }
        }

        // Événements
        actionFilter.addEventListener('change', filterLogs);
        statusFilter.addEventListener('change', filterLogs);
        dateFilter.addEventListener('change', filterLogs);
        searchFilter.addEventListener('input', filterLogs);
        refreshBtn.addEventListener('click', () => {
            refreshBtn.classList.add('fa-spin');
            loadLogs().finally(() => {
                setTimeout(() => {
                    refreshBtn.classList.remove('fa-spin');
                }, 1000);
            });
        });

        // Chargement initial
        loadLogs();
    </script>
</body>
</html>
