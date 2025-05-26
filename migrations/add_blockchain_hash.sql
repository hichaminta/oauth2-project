-- Add blockchain_hash column to access_logs table
ALTER TABLE `access_logs` 
ADD COLUMN `blockchain_hash` VARCHAR(255) DEFAULT NULL AFTER `message`;

-- Add index for blockchain_hash
ALTER TABLE `access_logs` 
ADD INDEX `idx_blockchain_hash` (`blockchain_hash`); 