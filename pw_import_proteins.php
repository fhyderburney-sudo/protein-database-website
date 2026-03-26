<?php
session_start();
require_once 'login.php';
include 'pw_redir.php';

echo "<html><body>";
include 'pw_menuf.php';

$charset = 'utf8mb4';
$dsn = "mysql:host=$hostname;dbname=$database;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    die("Unable to connect to database: " . $e->getMessage());
}

$run_id = $_GET['run_id'] ?? '';

if ($run_id === '' || !ctype_digit($run_id)) {
    die("Invalid run ID.");
}

$tsv_file = __DIR__ . "/runs/run_" . $run_id . "/proteins.tsv";

if (!file_exists($tsv_file)) {
    die("TSV file not found. Parse the FASTA first.");
}

try {
    $delete_stmt = $pdo->prepare("DELETE FROM proteins WHERE run_id = :run_id");
    $delete_stmt->execute([':run_id' => $run_id]);

    $insert_sql = "INSERT INTO proteins
        (run_id, accession, protein_name, organism, taxon_id, fasta_header, sequence, seq_length, source_db)
        VALUES
        (:run_id, :accession, :protein_name, :organism, :taxon_id, :fasta_header, :sequence, :seq_length, :source_db)";
    $insert_stmt = $pdo->prepare($insert_sql);

    $handle = fopen($tsv_file, "r");
    if (!$handle) {
        die("Unable to open TSV file.");
    }

    $count = 0;

    while (($line = fgets($handle)) !== false) {
        $line = rtrim($line, "\r\n");
        if ($line === '') {
            continue;
        }

        $parts = explode("\t", $line);
        if (count($parts) < 6) {
            continue;
        }

        $accession = $parts[0];
        $protein_name = $parts[1];
        $organism = $parts[2];
        $fasta_header = $parts[3];
        $sequence = $parts[4];
        $seq_length = (int)$parts[5];

        $insert_stmt->execute([
            ':run_id' => $run_id,
            ':accession' => $accession,
            ':protein_name' => $protein_name,
            ':organism' => $organism,
            ':taxon_id' => null,
            ':fasta_header' => $fasta_header,
            ':sequence' => $sequence,
            ':seq_length' => $seq_length,
            ':source_db' => 'NCBI'
        ]);

        $count++;
    }

    fclose($handle);

    $update_stmt = $pdo->prepare("UPDATE runs SET sequence_count = :count WHERE run_id = :run_id");
    $update_stmt->execute([
        ':count' => $count,
        ':run_id' => $run_id
    ]);

    echo "<h1>Import Proteins</h1>";
    echo "<p>Imported $count protein records for run $run_id.</p>";
    echo "<p><a href='pw_vruns.php?run_id=" . htmlspecialchars($run_id) . "'>Back to run details</a></p>";

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

echo "</body></html>";
?>