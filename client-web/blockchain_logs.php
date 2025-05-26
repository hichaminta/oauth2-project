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
    <title>QuickView - Logs Blockchain</title>
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .log-entry {
            background-color: var(--white);
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
            margin-bottom: 1rem;
            padding: 1.5rem;
            transition: transform 0.2s ease;
            border-top: 3px solid var(--primary-color);
        }

        .log-entry:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }

        .log-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--light-gray);
        }

        .log-info {
            flex: 1;
        }

        .log-hash {
            font-family: 'Courier New', monospace;
            font-size: 0.8rem;
            color: var(--secondary-color);
            word-break: break-all;
            max-width: 200px;
        }

        .log-action {
            font-weight: bold;
            color: var(--primary-color);
        }

        .log-status {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .status-success {
            background-color: #e0f2f1;
            color: #00695c;
        }

        .status-error {
            background-color: #ffebee;
            color: #c62828;
        }

        .log-timestamp {
            color: var(--secondary-color);
            font-size: 0.875rem;
        }

        .log-message {
            background-color: var(--light-gray);
            padding: 1rem;
            border-radius: 4px;
            margin-top: 1rem;
        }

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
                <a href="admin_log.php" class="nav-link">Logs</a>
                <a href="blochaine_adm_token.php" class="nav-link">Blockchain</a>
                <a href="blockchain_logs.php" class="nav-link active">Logs Blockchain</a>
                <a href="logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
            </nav>
        </div>
    </div>

    <div class="container">
        <div class="card" style="margin-top: 2rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <h2><i class="fas fa-history"></i> Logs Blockchain</h2>
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

            <div id="logsContainer">
                <div class="loading">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Chargement...</span>
                    </div>
                    <p class="mt-2">Chargement des logs...</p>
                </div>
            </div>
        </div>
    </div>

    <button id="refreshBtn" class="refresh-btn" title="Actualiser">
        <i class="fas fa-sync-alt"></i>
    </button>

    <script>
        let allLogs = [];
        const logsContainer = document.getElementById('logsContainer');
        const actionFilter = document.getElementById('actionFilter');
        const statusFilter = document.getElementById('statusFilter');
        const dateFilter = document.getElementById('dateFilter');
        const searchFilter = document.getElementById('searchFilter');
        const refreshBtn = document.getElementById('refreshBtn');
        const loadingIndicator = document.getElementById('loading-indicator');
        const apiUrl = "http://localhost/oauth2-project/protected-resources/get_log_by_blockchain.php?access_token=<?= $_SESSION['access_token'] ?>";

        // Récupérer le hash de l'URL si présent
        const urlParams = new URLSearchParams(window.location.search);
        const hashFilter = urlParams.get('hash');

        // Si un hash est présent dans l'URL, le mettre dans le champ de recherche
        if (hashFilter) {
            searchFilter.value = hashFilter;
            // Désactiver les autres filtres
            actionFilter.disabled = true;
            statusFilter.disabled = true;
            dateFilter.disabled = true;
            // Ajouter un message indiquant le filtrage par hash
            const filtersDiv = document.querySelector('.filters');
            const hashMessage = document.createElement('div');
            hashMessage.className = 'alert alert-info';
            hashMessage.innerHTML = `
                <i class="fas fa-info-circle"></i>
                Affichage des logs pour le hash: <strong>${hashFilter}</strong>
                <button onclick="clearHashFilter()" class="btn btn-secondary" style="margin-left: 1rem;">
                    <i class="fas fa-times"></i> Effacer le filtre
                </button>
            `;
            filtersDiv.insertBefore(hashMessage, filtersDiv.firstChild);
        }

        function clearHashFilter() {
            window.location.href = 'blockchain_logs.php';
        }

        function formatDate(timestamp) {
            return new Date(timestamp * 1000).toLocaleString('fr-FR', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        function createLogEntry(log) {
            const success = log.data.success ?? false;
            const statusClass = success ? 'status-success' : 'status-error';
            const statusText = success ? 'Succès' : 'Échec';
            const actionIcon = getActionIcon(log.data.action);

            return `
                <div class="log-entry">
                    <div class="log-header">
                        <div class="log-info">
                            <div class="d-flex align-items-center mb-2">
                                <span class="log-action me-3">
                                    <i class="${actionIcon} me-2"></i>
                                    ${log.data.action || 'N/A'}
                                </span>
                                <span class="log-status ${statusClass}">
                                    ${statusText}
                                </span>
                            </div>
                            <div class="log-details">
                                <div><strong>Utilisateur:</strong> ${log.data.username || 'N/A'}</div>
                                <div><strong>Fichier:</strong> ${log.data.filename || 'N/A'}</div>
                                <div class="log-timestamp mt-2">
                                    <i class="far fa-clock me-1"></i>
                                    ${formatDate(log.timestamp)}
                                </div>
                            </div>
                        </div>
                        <div class="log-hash">
                            <small>Hash:</small><br>
                            ${log.hash}
                        </div>
                    </div>
                    <div class="log-message">
                        ${log.data.message || 'N/A'}
                    </div>
                </div>
            `;
        }

        function getActionIcon(action) {
            switch(action?.toLowerCase()) {
                case 'upload': return 'fas fa-upload';
                case 'download': return 'fas fa-download';
                case 'delete': return 'fas fa-trash-alt';
                case 'login': return 'fas fa-sign-in-alt';
                default: return 'fas fa-info-circle';
            }
        }

        function filterLogs() {
            const action = actionFilter.value.toLowerCase();
            const status = statusFilter.value;
            const date = dateFilter.value;
            const search = searchFilter.value.toLowerCase();

            const filteredLogs = allLogs.filter(log => {
                const logAction = (log.data.action || '').toLowerCase();
                const logSuccess = log.data.success ?? false;
                const logDate = new Date(log.timestamp * 1000).toISOString().split('T')[0];
                const logMessage = (log.data.message || '').toLowerCase();
                const logUsername = (log.data.username || '').toLowerCase();
                const logFilename = (log.data.filename || '').toLowerCase();
                const logHash = (log.hash || '').toLowerCase();

                const matchesAction = !action || logAction === action;
                const matchesStatus = !status || 
                    (status === 'success' && logSuccess) || 
                    (status === 'error' && !logSuccess);
                const matchesDate = !date || logDate === date;
                const matchesSearch = !search || 
                    logMessage.includes(search) || 
                    logUsername.includes(search) || 
                    logFilename.includes(search) ||
                    logHash.includes(search);

                return matchesAction && matchesStatus && matchesDate && matchesSearch;
            });

            displayLogs(filteredLogs);
        }

        function displayLogs(logs) {
            if (logs.length === 0) {
                logsContainer.innerHTML = `
                    <div class="alert alert-error">
                        <i class="fas fa-info-circle me-2"></i>
                        Aucun log ne correspond aux critères de recherche.
                    </div>
                `;
                return;
            }

            logsContainer.innerHTML = logs.map(log => createLogEntry(log)).join('');
        }

        async function loadLogs() {
            try {
                loadingIndicator.style.display = 'block';
                logsContainer.innerHTML = `
                    <div class="loading">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Chargement...</span>
                        </div>
                        <p class="mt-2">Chargement des logs...</p>
                    </div>
                `;

                const response = await fetch('/oauth2-project/protected-resources/get_log_by_blockchain.php');
                const data = await response.json();

                if (data.error) {
                    throw new Error(data.error);
                }

                allLogs = data;
                filterLogs();
            } catch (error) {
                logsContainer.innerHTML = `
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        Erreur lors du chargement des logs: ${error.message}
                    </div>
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