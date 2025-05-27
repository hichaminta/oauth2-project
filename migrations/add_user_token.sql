-- Add user_token column to access_logs table
ALTER TABLE `access_logs` 
ADD COLUMN `user_token` VARCHAR(255) DEFAULT NULL AFTER `message`;

-- Add index for user_token
ALTER TABLE `access_logs` 
ADD INDEX `idx_user_token` (`user_token`); 