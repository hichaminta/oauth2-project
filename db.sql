
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





//
-- Création de la table files_permissions
CREATE TABLE `files_permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `file_name` varchar(255) NOT NULL,
  `user_id` int(11) NOT NULL,
  `access_type` varchar(10) NOT NULL DEFAULT 'read', -- 'read', 'write', 'admin'
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `files_permissions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insérer quelques permissions d'exemple
INSERT INTO `files_permissions` (`file_name`, `user_id`, `access_type`) VALUES
('resource.txt', 1, 'read'),
('hi.pdf', 1, 'read');

-- Modification de la table access_tokens pour mieux gérer les scopes
ALTER TABLE `access_tokens` 
MODIFY `scope` varchar(255) NOT NULL DEFAULT 'read';

-- Modification de la table authorization_codes pour mieux gérer les scopes
ALTER TABLE `authorization_codes` 
MODIFY `scope` varchar(255) NOT NULL DEFAULT 'read';