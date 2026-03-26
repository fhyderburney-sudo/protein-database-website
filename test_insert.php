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

    $sql = "INSERT INTO runs
            (user_forname, user_surname, protein_family, taxon_query, ncbi_query, run_type, status, sequence_count, notes)
            VALUES
            (:ufn, :usn, :pf, :tq, :nq, :rt, :st, :sc, :nt)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':ufn' => 'Fatima',
        ':usn' => 'Hyder-Burney',
        ':pf' => 'glucose-6-phosphatase',
        ':tq' => 'Aves',
        ':nq' => 'glucose-6-phosphatase[Protein Name] AND Aves[Organism]',
        ':rt' => 'example',
        ':st' => 'complete',
        ':sc' => 0,
        ':nt' => 'Test insert from PHP'
    ]);

    echo "Test run inserted successfully.";

} catch (PDOException $e) {
    die("Insert failed: " . $e->getMessage());
}
?>