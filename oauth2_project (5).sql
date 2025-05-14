-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : jeu. 15 mai 2025 à 00:51
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `oauth2_project`
--

-- --------------------------------------------------------

--
-- Structure de la table `access_logs`
--

CREATE TABLE `access_logs` (
  `id` int(11) NOT NULL,
  `timestamp` datetime NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `file_id` int(11) DEFAULT NULL,
  `filename` varchar(255) DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `success` tinyint(1) NOT NULL DEFAULT 0,
  `message` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `access_logs`
--

INSERT INTO `access_logs` (`id`, `timestamp`, `ip_address`, `user_id`, `file_id`, `filename`, `action`, `success`, `message`) VALUES
(208, '2025-05-14 23:42:41', '::1', 1, 17, NULL, 'update_file_permission', 1, 'Permission mise à jour'),
(209, '2025-05-14 23:42:56', '::1', 1, 11, NULL, 'delete_permission', 1, 'Permission supprimée'),
(210, '2025-05-14 23:42:58', '::1', 1, 11, NULL, 'delete_permission', 1, 'Permission supprimée'),
(211, '2025-05-14 23:43:05', '::1', 1, 9, NULL, 'add_permission', 1, 'Permission ajoutée'),
(212, '2025-05-14 23:43:11', '::1', 1, 9, NULL, 'update_file_permission', 1, 'Permission mise à jour');

-- --------------------------------------------------------

--
-- Structure de la table `access_tokens`
--

CREATE TABLE `access_tokens` (
  `id` int(11) NOT NULL,
  `access_token` varchar(255) NOT NULL,
  `client_id` varchar(255) NOT NULL,
  `user_id` int(11) NOT NULL,
  `expires` datetime NOT NULL,
  `scope` varchar(255) NOT NULL DEFAULT 'read'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `access_tokens`
--

INSERT INTO `access_tokens` (`id`, `access_token`, `client_id`, `user_id`, `expires`, `scope`) VALUES
(250, 'b0d2e1a3959a66060befbc74392f67ad6d37e8ac42ddeec49240c45cb8b3a8ba', 'quickview-client', 1, '2025-05-12 16:02:14', 'read write admin'),
(251, 'fabb6b8cb2b5896bb214bd68274f231c768b079b470d41da04deed1b62ea9b33', 'quickview-client', 1, '2025-05-12 16:02:19', 'read write admin'),
(252, 'cc801a8e86058177b40afaff95d5ef1d157c93ba6449f97a7f8fd0df6eba2727', 'quickview-client', 1, '2025-05-13 15:03:55', 'read write admin'),
(253, '20f3a3aa7ec919a4e27df491fcd6c6f9eae5173e84e0bd3a1c41bd129f83ac5a', 'quickview-client', 1, '2025-05-13 15:51:23', 'read write admin'),
(254, '9e267edf9eb4e0659085d61b799e616ebbdef90bd6ec183ee32b6f0b5f674b37', 'quickview-client', 1, '2025-05-12 18:54:35', 'read write admin'),
(255, 'c30692dc2ce32a48bfbbe60c595c90a3c3b50877111bbb2954d259f1affebf12', 'quickview-client', 1, '2025-05-13 01:16:04', 'read write admin'),
(256, 'fc11c0e99e8f850a75496a0d22876498fe8398a6427015759bdf312922938701', 'quickview-client', 1, '2025-05-14 00:17:10', 'read write admin'),
(257, '505d109431f9634caac2f3d9f2becfa53c0d35ed6d7b76ff1e43f83586554a1e', 'quickview-client', 1, '2025-05-14 22:46:50', 'read write admin'),
(258, 'ccb4119acc504cbb9a487e1d2afd0e9ed871f8a98a0a0d5300599dc140a07c84', 'quickview-client', 1, '2025-05-14 00:57:52', 'read write admin'),
(259, 'fe2c6a5048288e07542c25205e72bea90c64072d1dc8f0dd7709362081bdac37', 'quickview-client', 1, '2025-05-14 01:25:24', 'read write admin'),
(260, '1ed9ee5fbbf8a6df76dc87fd5e490fa1a67c895d66774dbd3c0f13e0a047528c', 'quickview-client', 1, '2025-05-14 01:40:42', 'read write admin'),
(261, '587a71075647055ecfe81e65bf550b5dd200da4dba63e5a429af739b3f022278', 'quickview-client', 1, '2025-05-15 00:41:46', 'read write admin'),
(262, 'e9aa74e19d3ae2db6f63e0ae5c92032a476a85c489cf6675a798d578ec8353bf', 'quickview-client', 1, '2025-05-15 01:41:59', 'read write admin');

-- --------------------------------------------------------

--
-- Structure de la table `authorization_codes`
--

CREATE TABLE `authorization_codes` (
  `code` varchar(255) NOT NULL,
  `client_id` varchar(80) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `redirect_uri` varchar(200) DEFAULT NULL,
  `expires` datetime DEFAULT NULL,
  `scope` varchar(255) NOT NULL DEFAULT 'read',
  `expires_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `clients`
--

CREATE TABLE `clients` (
  `client_id` varchar(80) NOT NULL,
  `client_secret` varchar(80) NOT NULL,
  `redirect_uri` varchar(200) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `clients`
--

INSERT INTO `clients` (`client_id`, `client_secret`, `redirect_uri`) VALUES
('quickview-client', 'secret123', 'http://localhost/oauth2-project/client-web/callback.php');

-- --------------------------------------------------------

--
-- Structure de la table `files`
--

CREATE TABLE `files` (
  `id` int(11) NOT NULL,
  `filename` varchar(255) NOT NULL,
  `path` varchar(255) NOT NULL,
  `size` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `files`
--

INSERT INTO `files` (`id`, `filename`, `path`, `size`, `created_at`) VALUES
(8, '1739818799806.pdf', 'C:\\xampp\\htdocs\\oauth2-project\\protected-resources/ressources/1739818799806.pdf', 4567391, '2025-05-06 00:46:21'),
(9, 'Important tools.docx', 'C:\\xampp\\htdocs\\oauth2-project\\protected-resources/ressources/Important tools.docx', 11537, '2025-05-06 01:23:30'),
(11, '1728213431292.gif', 'C:\\xampp\\htdocs\\oauth2-project\\protected-resources/ressources/1728213431292.gif', 715846, '2025-05-07 01:18:24'),
(12, 'New Microsoft Excel Worksheet.xlsx', 'C:\\xampp\\htdocs\\oauth2-project\\protected-resources/ressources/New Microsoft Excel Worksheet.xlsx', 9013, '2025-05-09 16:43:23'),
(13, 'New Text Document.txt', 'C:\\xampp\\htdocs\\oauth2-project\\protected-resources/ressources/New Text Document.txt', 37, '2025-05-12 11:37:40'),
(14, 'blochaine.docx', 'C:\\xampp\\htdocs\\oauth2-project\\protected-resources/ressources/blochaine.docx', 244685, '2025-05-13 16:45:25'),
(15, 'tp1SM.pdf', 'C:\\xampp\\htdocs\\oauth2-project\\protected-resources/ressources/tp1SM.pdf', 1811416, '2025-05-14 21:45:56'),
(16, 'tp1SM.docx', 'C:\\xampp\\htdocs\\oauth2-project\\protected-resources/ressources/tp1SM.docx', 1851264, '2025-05-14 21:48:12'),
(17, 'TP 2 sécurité mobile (1).pdf', 'C:\\xampp\\htdocs\\oauth2-project\\protected-resources/ressources/TP 2 sécurité mobile (1).pdf', 812967, '2025-05-14 23:25:23');

-- --------------------------------------------------------

--
-- Structure de la table `file_permissions`
--

CREATE TABLE `file_permissions` (
  `id` int(11) NOT NULL,
  `file_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `can_read` tinyint(1) NOT NULL DEFAULT 1,
  `can_write` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `file_permissions`
--

INSERT INTO `file_permissions` (`id`, `file_id`, `user_id`, `can_read`, `can_write`) VALUES
(8, 8, 1, 1, 1),
(18, 12, 1, 1, 1),
(19, 13, 1, 1, 1),
(21, 14, 1, 1, 1),
(22, 9, 1, 1, 1),
(23, 15, 1, 1, 1),
(24, 16, 1, 1, 1),
(25, 17, 1, 1, 1),
(26, 9, 2, 1, 1);

-- --------------------------------------------------------

--
-- Structure de la table `refresh_tokens`
--

CREATE TABLE `refresh_tokens` (
  `refresh_token` varchar(128) NOT NULL,
  `client_id` varchar(80) NOT NULL,
  `user_id` int(11) NOT NULL,
  `expires` datetime NOT NULL,
  `scope` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `refresh_tokens`
--

INSERT INTO `refresh_tokens` (`refresh_token`, `client_id`, `user_id`, `expires`, `scope`) VALUES
('008ef6796a75f5092e604ed5e10caee40d15e42054f4e9e70612607ac6cd24d6ac083462b63b906a', 'quickview-client', 1, '2025-05-15 00:45:46', 'read write admin'),
('13786bf2912a45a9181b0b661a314566b866d819443d7598cbc46cdae2b137616db9554e79170b6e', 'quickview-client', 1, '2025-05-13 17:59:43', 'read write admin'),
('182f6ec8401b313d29ea0bce2981e918d3fa80a75b09385a9ec2cb08823cd7564c02dd22f1ecb8cb', 'quickview-client', 1, '2025-05-15 00:02:55', 'read write admin'),
('23679936cf5d1d994fe1f59be4778ae1b1aaeed2e67c5de8f5017c6c8fe47daacc6a846ce5258568', 'quickview-client', 1, '2025-05-13 15:55:23', 'read write admin'),
('90d24db6730b73639952cda78ec169415392d7969dbbf81ee47a65a28e857f31ace6f8e2ccc46b7b', 'quickview-client', 1, '2025-05-13 15:06:38', 'read write admin'),
('9743b24c260aacce85ecc2cdcf4a1da6046b56eb01c7083626982ba8a37a4a5b01bdf2d2bd3d13cf', 'quickview-client', 1, '2025-05-15 00:36:05', 'read write admin'),
('b08835b94aaea892a9f695e9a556ee66d7ac8d36bcaf171adb14bc4702133d89429f35aac075d78b', 'quickview-client', 1, '2025-05-14 00:21:10', 'read write admin'),
('c16ee7fa41c500619b262368e323da916500b1d1afb238480b74f42f970477928438b403ab871b0e', 'quickview-client', 1, '2025-05-13 17:50:16', 'read write admin'),
('f1ff8255f51599cb6ca38b217e66f81f062d096cdd445808f53cfd4364acdba2f201227badc25dd5', 'quickview-client', 1, '2025-05-13 15:07:55', 'read write admin'),
('f2e207e87657dad239f53099a175e36ebe9a19bdc7a8492c722854d2d760a13fd2470157b453471b', 'quickview-client', 1, '2025-05-14 22:50:50', 'read write admin'),
('fea3ae9ba95d01fbadd30ede868764baf2359c77918ef55101775637398a9b38a1f960dfd4ca890e', 'quickview-client', 1, '2025-05-13 15:07:17', 'read write admin');

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`) VALUES
(1, 'hicham', '1234', 'hicha@gmail.com'),
(2, 'test', 'test', 'tst@g.com');

-- --------------------------------------------------------

--
-- Structure de la table `user_roles`
--

CREATE TABLE `user_roles` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `role` enum('user','admin') NOT NULL DEFAULT 'user',
  `available_scopes` varchar(255) NOT NULL DEFAULT 'read'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `user_roles`
--

INSERT INTO `user_roles` (`id`, `user_id`, `role`, `available_scopes`) VALUES
(1, 1, 'admin', 'read write admin'),
(2, 2, 'user', 'read write');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `access_logs`
--
ALTER TABLE `access_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_timestamp` (`timestamp`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_file_id` (`file_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_success` (`success`),
  ADD KEY `idx_ip_address` (`ip_address`);

--
-- Index pour la table `access_tokens`
--
ALTER TABLE `access_tokens`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `authorization_codes`
--
ALTER TABLE `authorization_codes`
  ADD PRIMARY KEY (`code`);

--
-- Index pour la table `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`client_id`);

--
-- Index pour la table `files`
--
ALTER TABLE `files`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `filename` (`filename`);

--
-- Index pour la table `file_permissions`
--
ALTER TABLE `file_permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `file_user` (`file_id`,`user_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Index pour la table `refresh_tokens`
--
ALTER TABLE `refresh_tokens`
  ADD PRIMARY KEY (`refresh_token`);

--
-- Index pour la table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `user_roles`
--
ALTER TABLE `user_roles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `access_logs`
--
ALTER TABLE `access_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=213;

--
-- AUTO_INCREMENT pour la table `access_tokens`
--
ALTER TABLE `access_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=263;

--
-- AUTO_INCREMENT pour la table `files`
--
ALTER TABLE `files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT pour la table `file_permissions`
--
ALTER TABLE `file_permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT pour la table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `user_roles`
--
ALTER TABLE `user_roles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `access_logs`
--
ALTER TABLE `access_logs`
  ADD CONSTRAINT `access_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `access_logs_ibfk_2` FOREIGN KEY (`file_id`) REFERENCES `files` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `file_permissions`
--
ALTER TABLE `file_permissions`
  ADD CONSTRAINT `file_permissions_ibfk_1` FOREIGN KEY (`file_id`) REFERENCES `files` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `file_permissions_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `user_roles`
--
ALTER TABLE `user_roles`
  ADD CONSTRAINT `user_roles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
