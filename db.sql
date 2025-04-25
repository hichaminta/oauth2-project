CREATE TABLE IF NOT EXISTS `refresh_tokens` (
  `refresh_token` varchar(128) NOT NULL,
  `client_id` varchar(80) NOT NULL,
  `user_id` int(11) NOT NULL,
  `expires` datetime NOT NULL,
  `scope` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`refresh_token`)
) 