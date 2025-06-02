<?php
session_start();
include_once 'variable.php';

$is_authenticated = isset($_SESSION['access_token']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QuickView - Nos Services</title>
    <link rel="stylesheet" href="css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .service-card {
            background: var(--white);
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            margin-bottom: 20px;
            border: 1px solid var(--border-color);
        }
        .service-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }
        .icon-feature {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
        .welcome-section {
            background: var(--primary-color);
            color: var(--white);
            padding: 4rem 0;
            margin-bottom: 3rem;
        }
        .btn-login {
            background: var(--white);
            color: var(--primary-color);
            border: 2px solid var(--white);
            padding: 0.5rem 2rem;
            font-size: 1.1rem;
            border-radius: 4px;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-block;
            margin-top: 1rem;
        }
        .btn-login:hover {
            background: transparent;
            color: var(--white);
        }
        .card-title {
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
        .card-text {
            color: var(--secondary-color);
        }
        .navbar {
            background-color: var(--white);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 1rem 0;
        }
        .navbar-brand {
            color: var(--primary-color);
            font-weight: bold;
            font-size: 1.5rem;
            text-decoration: none;
        }
        .nav-link {
            color: var(--secondary-color);
            text-decoration: none;
            padding: 0.5rem 1rem;
            transition: color 0.3s ease;
        }
        .nav-link:hover {
            color: var(--primary-color);
        }
        .nav-link.active {
            color: var(--primary-color);
            font-weight: bold;
        }
    </style>
</head>
<body>
    <!-- Barre de navigation -->
    <nav class="navbar">
        <div class="container">
            <a class="navbar-brand" href="services.php">
                <i class="fas fa-file-search"></i> QuickView
            </a>
            <div class="nav-links">
                <a class="nav-link active" href="services.php">Accueil</a>
                <?php if ($is_authenticated): ?>
                <a class="nav-link" href="view.php">Mon Compte</a>
                <a class="nav-link" href="logout.php">Déconnexion</a>
                <?php else: ?>
                <a class="nav-link" href="index.php?login=1">Connexion</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Section de bienvenue -->
    <section class="welcome-section">
        <div class="container" style="text-align: center;">
            <i class="fas fa-file-search" style="font-size: 4rem; margin-bottom: 1rem;"></i>
            <h1 style="font-size: 2.5rem; margin-bottom: 1rem;">Bienvenue sur QuickView</h1>
            <p style="font-size: 1.2rem; margin-bottom: 2rem;">Découvrez nos services sécurisés pour la gestion et la visualisation de vos documents</p>
            <?php if (!$is_authenticated): ?>
            <a href="index.php?login=1" class="btn-login">
                <i class="fas fa-sign-in-alt"></i> Se connecter pour accéder à tous les services
            </a>
            <?php endif; ?>
        </div>
    </section>

    <!-- Section des services -->
    <div class="container">
        <div class="row">
            <div class="col-md-4">
                <div class="service-card">
                    <div class="card-body" style="padding: 2rem; text-align: center;">
                        <i class="fas fa-shield-alt icon-feature"></i>
                        <h3 class="card-title">Authentification Sécurisée</h3>
                        <p class="card-text">Profitez d'une authentification robuste basée sur OAuth2 pour protéger vos données et votre identité.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="service-card">
                    <div class="card-body" style="padding: 2rem; text-align: center;">
                        <i class="fas fa-project-diagram icon-feature"></i>
                        <h3 class="card-title">Gestion de Blockchain</h3>
                        <p class="card-text">Accédez à nos outils de gestion de blockchain pour un suivi transparent et sécurisé de vos transactions.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="service-card">
                    <div class="card-body" style="padding: 2rem; text-align: center;">
                        <i class="fas fa-user-shield icon-feature"></i>
                        <h3 class="card-title">Administration Avancée</h3>
                        <p class="card-text">Interface d'administration puissante pour gérer vos permissions et suivre les activités.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row" style="margin-top: 2rem;">
            <div class="col-md-4">
                <div class="service-card">
                    <div class="card-body" style="padding: 2rem; text-align: center;">
                        <i class="fas fa-history icon-feature"></i>
                        <h3 class="card-title">Historique des Logs</h3>
                        <p class="card-text">Consultez l'historique complet des activités et des transactions blockchain.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="service-card">
                    <div class="card-body" style="padding: 2rem; text-align: center;">
                        <i class="fas fa-key icon-feature"></i>
                        <h3 class="card-title">Gestion des Tokens</h3>
                        <p class="card-text">Gérez facilement vos tokens d'accès et vos autorisations de manière sécurisée.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="service-card">
                    <div class="card-body" style="padding: 2rem; text-align: center;">
                        <i class="fas fa-users-cog icon-feature"></i>
                        <h3 class="card-title">Contrôle des Accès</h3>
                        <p class="card-text">Définissez et gérez les permissions utilisateurs avec précision.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer style="text-align: center; padding: 2rem 0; color: var(--secondary-color); background-color: var(--white); margin-top: 3rem;">
        <p>&copy; 2024 QuickView. Tous droits réservés.</p>
    </footer>
</body>
</html>