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
    <title>QuickView - Données Blockchain</title>
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .token-container {
            background-color: var(--white);
            border-radius: 8px;
            padding: 1.5rem;
            margin: 1.5rem 0;
            border: 1px solid var(--light-gray);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-top: 3px solid var(--primary-color);
        }
        .token-header {
            background-color: var(--light-gray);
            padding: 0.8rem;
            border-radius: 5px;
            font-weight: bold;
            margin-bottom: 1rem;
            border-left: 4px solid var(--primary-color);
            display: flex;
            align-items: center;
        }
        .json-raw {
            background-color: var(--light-gray);
            padding: 1rem;
            border-radius: 5px;
            font-family: monospace;
            font-size: 0.9em;
            overflow-x: auto;
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
                <a href="blochaine_adm_token.php" class="nav-link active">Blockchain</a>
                <a href="blockchain_logs.php" class="nav-link">Logs Blockchain</a>
                <a href="logout.php" class="nav-link"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
            </nav>
        </div>
    </div>

    <div class="container">
        <div class="card" style="margin-top: 2rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
                <h2><i class="fas fa-link"></i> Données Blockchain MultiChain</h2>
                <div id="loading-indicator">
                    <i class="fas fa-spinner fa-spin"></i> Chargement...
                </div>
            </div>

            <div class="filters">
                <div class="form-group" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
                    <div>
                        <label class="filter-label">Date</label>
                        <input type="date" class="form-input" id="dateFilter">
                    </div>
                    <div>
                        <label class="filter-label">Token</label>
                        <input type="text" class="form-input" id="searchFilter" placeholder="Rechercher un token...">
                    </div>
                </div>
            </div>

            <div id="tokensContainer">
                <div class="loading">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Chargement...</span>
                    </div>
                    <p class="mt-2">Chargement des tokens...</p>
                </div>
            </div>
        </div>
    </div>

    <button id="refreshBtn" class="refresh-btn" title="Actualiser">
        <i class="fas fa-sync-alt"></i>
    </button>

    <script>
        let allTokens = [];
        const tokensContainer = document.getElementById('tokensContainer');
        const dateFilter = document.getElementById('dateFilter');
        const searchFilter = document.getElementById('searchFilter');
        const refreshBtn = document.getElementById('refreshBtn');
        const loadingIndicator = document.getElementById('loading-indicator');
        const apiUrl = "http://localhost/oauth2-project/protected-resources/blokchaine_get_api.php";

        function formatDate(timestamp) {
            try {
                if (!timestamp || isNaN(timestamp)) {
                    return 'Date invalide';
                }
                const date = new Date(timestamp * 1000);
                if (isNaN(date.getTime())) {
                    return 'Date invalide';
                }
                return date.toLocaleString('fr-FR', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
            } catch (error) {
                console.error('Erreur de formatage de date:', error);
                return 'Date invalide';
            }
        }

        function filterTokens() {
            const date = dateFilter.value;
            const search = searchFilter.value.toLowerCase();

            const filteredTokens = allTokens.filter(token => {
                const decoded = token.decoded_data || {};
                const timestamp = decoded.timestamp || token.timestamp || 0;
                
                try {
                    const tokenDate = new Date(timestamp * 1000);
                    const formattedDate = tokenDate.toISOString().split('T')[0];
                    const matchesDate = !date || formattedDate === date;
                    
                    // Recherche dans tous les champs pertinents
                    const tokenToken = (decoded.user_token || decoded.access_token || '').toLowerCase();
                    const tokenAction = (decoded.action || '').toLowerCase();
                    const tokenUsername = (decoded.username || '').toLowerCase();
                    const tokenFilename = (decoded.filename || '').toLowerCase();
                    const tokenMessage = (decoded.message || '').toLowerCase();
                    
                    const matchesSearch = !search || 
                        tokenToken.includes(search) ||
                        tokenAction.includes(search) ||
                        tokenUsername.includes(search) ||
                        tokenFilename.includes(search) ||
                        tokenMessage.includes(search);

                    return matchesDate && matchesSearch;
                } catch (error) {
                    console.error('Erreur de filtrage:', error);
                    return false;
                }
            });

            displayTokens(filteredTokens);
        }

        function displayTokens(tokens) {
            if (tokens.length === 0) {
                tokensContainer.innerHTML = `
                    <div class="alert alert-error">
                        <i class="fas fa-info-circle me-2"></i>
                        Aucun token ne correspond aux critères de recherche.
                    </div>
                `;
                return;
            }

            tokensContainer.innerHTML = tokens.map(token => createTokenEntry(token)).join('');
        }

        async function loadTokens() {
            try {
                loadingIndicator.style.display = 'block';
                tokensContainer.innerHTML = `
                    <div class="loading">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Chargement...</span>
                        </div>
                        <p class="mt-2">Chargement des tokens...</p>
                    </div>
                `;

                const response = await fetch(apiUrl);
                const data = await response.json();

                if (data.error) {
                    throw new Error(data.error);
                }

                // Vérifier et nettoyer les données
                allTokens = Array.isArray(data) ? data : [data];
                
                // Afficher les données brutes pour le débogage
                console.log('Données reçues:', allTokens);
                
                // Appliquer les filtres initiaux
                filterTokens();
            } catch (error) {
                console.error('Erreur de chargement:', error);
                tokensContainer.innerHTML = `
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        Erreur lors du chargement des tokens: ${error.message}
                    </div>
                `;
            } finally {
                loadingIndicator.style.display = 'none';
            }
        }

        function createTokenEntry(token) {
            const decoded = token.decoded_data || {};
            return `
                <div class="token-container">
                    <div class="token-header">
                        <i class="fas fa-cubes"></i> Token
                    </div>
                    <table class="table">
                        <tbody>
                            ${Object.entries(decoded).map(([key, value]) => `
                                <tr>
                                    <td><i class="fas fa-key"></i> ${key}</td>
                                    <td>${value}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                    <div class="json-raw">
                        <div style="margin-bottom: 0.5rem;"><i class="fas fa-code"></i> Données JSON brutes :</div>
                        <pre>${JSON.stringify(token, null, 2)}</pre>
                    </div>
                </div>
            `;
        }

        // Événements
        dateFilter.addEventListener('change', filterTokens);
        searchFilter.addEventListener('input', filterTokens);
        refreshBtn.addEventListener('click', () => {
            refreshBtn.classList.add('fa-spin');
            loadTokens().finally(() => {
                setTimeout(() => {
                    refreshBtn.classList.remove('fa-spin');
                }, 1000);
            });
        });

        // Fonction utilitaire pour lire un paramètre d'URL
        function getUrlParam(name) {
            const url = new URL(window.location.href);
            return url.searchParams.get(name);
        }

        // Pré-remplir le champ de recherche si un token est passé dans l'URL
        const tokenFromUrl = getUrlParam('token');
        if (tokenFromUrl) {
            searchFilter.value = tokenFromUrl;
        }

        // Chargement initial
        document.addEventListener('DOMContentLoaded', () => {
            loadTokens();
        });
    </script>
</body>
</html>
