<?php
require_once 'login.php';

$charset = 'utf8mb4';
$dsn = "mysql:host=$hostname;dbname=$database;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $username, $password, $options);
    echo "<p>Connected successfully.</p>";

    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll();

    echo "<h2>Tables in database:</h2><ul>";
    foreach ($tables as $table) {
        echo "<li>" . htmlspecialchars(array_values($table)[0]) . "</li>";
    }
    echo "</ul>";

} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>