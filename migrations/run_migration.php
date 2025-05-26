<?php
require_once '../server-oauth/database.php';

try {
    // Read the migration file
    $migration = file_get_contents(__DIR__ . '/add_blockchain_hash.sql');
    
    // Execute the migration
    $pdo->exec($migration);
    
    echo "Migration completed successfully!\n";
} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
} 