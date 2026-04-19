<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$sqlFile = 'db.sql';

try {
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $sql = file_get_contents($sqlFile);
    $pdo->exec($sql);
    echo "Database setup completed successfully.\n";

    // Create necessary directories
    $dirs = ['assets/uploads', 'assets/images'];
    foreach($dirs as $dir) {
        if(!is_dir($dir)) {
            mkdir($dir, 0755, true);
            echo "Created directory: $dir\n";
        }
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
