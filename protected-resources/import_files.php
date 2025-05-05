<?php
// import_files.php - À exécuter une fois pour importer les fichiers existants
require_once 'server-oauth/database.php';

$directory = __DIR__ . '/protected-resources/ressources/';
if (!is_dir($directory)) {
    die("Le dossier ressources/ est introuvable.");
}

$files = scandir($directory);
$files = array_diff($files, ['.', '..']);

foreach ($files as $file) {
    $path = $directory . $file;
    if (is_file($path)) {
        // Vérifier si le fichier existe déjà dans la base de données
        $stmt = $pdo->prepare("SELECT id FROM files WHERE filename = ?");
        $stmt->execute([$file]);
        
        if (!$stmt->fetch()) {
            // Insérer le fichier dans la table files
            $stmt = $pdo->prepare("INSERT INTO files (filename, path, size, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$file, $path, filesize($path)]);
            
            $file_id = $pdo->lastInsertId();
            
            // Accorder l'accès à tous les utilisateurs (par défaut)
            $stmt = $pdo->prepare("INSERT INTO file_permissions (file_id, user_id, can_read, can_write) 
                                  SELECT ?, id, 1, 0 FROM users");
            $stmt->execute([$file_id]);
            
            echo "Fichier importé: $file\n";
        } else {
            echo "Fichier déjà existant: $file\n";
        }
    }
}

echo "Import terminé!";