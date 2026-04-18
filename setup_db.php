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
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
