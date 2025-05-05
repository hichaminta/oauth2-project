
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";
CREATE TABLE `access_tokens` (
  `id` int(11) NOT NULL,
  `access_token` varchar(255) NOT NULL,
  `client_id` varchar(255) NOT NULL,
  `user_id` int(11) NOT NULL,
  `expires` datetime NOT NULL,
  `scope` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
CREATE TABLE `authorization_codes` (
  `code` varchar(255) NOT NULL,
  `client_id` varchar(80) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `redirect_uri` varchar(200) DEFAULT NULL,
  `expires` datetime DEFAULT NULL,
  `scope` varchar(200) DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
CREATE TABLE `clients` (
  `client_id` varchar(80) NOT NULL,
  `client_secret` varchar(80) NOT NULL,
  `redirect_uri` varchar(200) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
INSERT INTO `clients` (`client_id`, `client_secret`, `redirect_uri`) VALUES
('quickview-client', 'secret123', 'http://localhost/oauth2-project/client-web/callback.php');
CREATE TABLE `refresh_tokens` (
  `refresh_token` varchar(128) NOT NULL,
  `client_id` varchar(80) NOT NULL,
  `user_id` int(11) NOT NULL,
  `expires` datetime NOT NULL,
  `scope` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



INSERT INTO `users` (`id`, `username`, `password`, `email`) VALUES
(1, 'hicham', '1234', 'hicha@gmail.com');

ALTER TABLE `access_tokens`
  ADD PRIMARY KEY (`id`);


ALTER TABLE `authorization_codes`
  ADD PRIMARY KEY (`code`);


ALTER TABLE `clients`
  ADD PRIMARY KEY (`client_id`);


ALTER TABLE `refresh_tokens`
  ADD PRIMARY KEY (`refresh_token`);

ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);


ALTER TABLE `access_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;


ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;



-- Ajout de la table pour stocker les fichiers
CREATE TABLE `files` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `filename` varchar(255) NOT NULL,
  `type` varchar(50) NOT NULL,
  `size` int(11) NOT NULL,
  `date_added` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `filename` (`filename`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Ajout de la table pour stocker les permissions des fichiers
CREATE TABLE `files_permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `file_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `can_read` tinyint(1) NOT NULL DEFAULT 1,
  `can_write` tinyint(1) NOT NULL DEFAULT 0,
  `can_delete` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `file_user_unique` (`file_id`, `user_id`),
  CONSTRAINT `fk_file_id` FOREIGN KEY (`file_id`) REFERENCES `files` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Script pour initialiser les fichiers existants dans le système
INSERT INTO `files` (`filename`, `type`, `size`, `date_added`)
SELECT 
  DISTINCT filename,
  SUBSTRING_INDEX(filename, '.', -1) AS type,
  0 AS size,
  NOW() AS date_added
FROM (
  SELECT 'hi.pdf' AS filename UNION ALL
  SELECT 'resource.txt' AS filename
) AS existing_files;

-- Donner accès par défaut à l'utilisateur existant
INSERT INTO `files_permissions` (`file_id`, `user_id`, `can_read`, `can_write`, `can_delete`)
SELECT f.id, 1, 1, 0, 0
FROM `files` f;