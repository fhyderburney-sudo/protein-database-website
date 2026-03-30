<?php
session_start();
require_once 'login.php';
include 'pw_redir.php';

echo "<html><head><title>Fetch and Import Sequences</title></head><body>";
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

$run_sql = "SELECT run_id, protein_family, taxon_query, max_sequences, ncbi_query, status
            FROM runs
            WHERE run_id = :run_id";

try {
    $run_stmt = $pdo->prepare($run_sql);
    $run_stmt->execute([':run_id' => $run_id]);
    $run = $run_stmt->fetch();
} catch (PDOException $e) {
    die("Unable to retrieve run: " . $e->getMessage());
}

if (!$run) {
    die("Run not found.");
}

echo "<h1>Fetch and Import Sequences</h1>";

$query = $run['ncbi_query'];
$max_sequences = (int)$run['max_sequences'];

if ($max_sequences <= 0) {
    $max_sequences = 20;
}

$fetch_script = __DIR__ . "/fetch_run.sh";
$parse_script = __DIR__ . "/parse_fasta.sh";

if (!file_exists($fetch_script)) {
    die("fetch_run.sh not found.");
}

if (!file_exists($parse_script)) {
    die("parse_fasta.sh not found.");
}

// Mark run as running
try {
    $running_stmt = $pdo->prepare("UPDATE runs SET status = :status WHERE run_id = :run_id");
    $running_stmt->execute([
        ':status' => 'running',
        ':run_id' => $run_id
    ]);
} catch (PDOException $e) {
    die("Unable to update run status to running: " . $e->getMessage());
}

$fetch_cmd = escapeshellarg($fetch_script) . " " .
             escapeshellarg($query) . " " .
             escapeshellarg($run_id) . " " .
             escapeshellarg($max_sequences) . " 2>&1";

$parse_cmd = escapeshellarg($parse_script) . " " . escapeshellarg($run_id) . " 2>&1";

echo "<h2>Step 1: Retrieve FASTA from NCBI</h2>";
$fetch_output = shell_exec($fetch_cmd);
echo "<pre>" . htmlspecialchars($fetch_output ?? 'No output returned.') . "</pre>";

$fasta_file = __DIR__ . "/runs/run_" . $run_id . "/sequences.fasta";

if (!file_exists($fasta_file) || filesize($fasta_file) === 0) {
    $fail_stmt = $pdo->prepare("UPDATE runs SET status = :status WHERE run_id = :run_id");
    $fail_stmt->execute([
        ':status' => 'failed',
        ':run_id' => $run_id
    ]);
    die("<p>FASTA retrieval failed. Run status updated to failed.</p>");
}

echo "<h2>Step 2: Parse FASTA into TSV</h2>";
$parse_output = shell_exec($parse_cmd);
echo "<pre>" . htmlspecialchars($parse_output ?? 'No output returned.') . "</pre>";

$tsv_file = __DIR__ . "/runs/run_" . $run_id . "/proteins.tsv";

if (!file_exists($tsv_file) || filesize($tsv_file) === 0) {
    $fail_stmt = $pdo->prepare("UPDATE runs SET status = :status WHERE run_id = :run_id");
    $fail_stmt->execute([
        ':status' => 'failed',
        ':run_id' => $run_id
    ]);
    die("<p>TSV parsing failed. Run status updated to failed.</p>");
}

echo "<h2>Step 3: Import proteins into database</h2>";

try {
    // Remove existing proteins for this run before re-import
    $delete_stmt = $pdo->prepare("DELETE FROM proteins WHERE run_id = :run_id");
    $delete_stmt->execute([':run_id' => $run_id]);

    $insert_sql = "INSERT INTO proteins
        (run_id, accession, protein_name, organism, taxon_id, fasta_header, sequence, seq_length, source_db)
        VALUES
        (:run_id, :accession, :protein_name, :organism, :taxon_id, :fasta_header, :sequence, :seq_length, :source_db)";
    $insert_stmt = $pdo->prepare($insert_sql);

    $handle = fopen($tsv_file, "r");
    if (!$handle) {
        throw new Exception("Unable to open TSV file.");
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

    $final_status = ($count > 0) ? 'complete' : 'failed';

    $update_stmt = $pdo->prepare("UPDATE runs
                                  SET sequence_count = :count, status = :status
                                  WHERE run_id = :run_id");
    $update_stmt->execute([
        ':count' => $count,
        ':status' => $final_status,
        ':run_id' => $run_id
    ]);

    // Record FASTA and TSV output files
    $files_to_add = [
        [
            'file_type' => 'fasta',
            'file_path' => "runs/run_" . $run_id . "/sequences.fasta",
            'description' => 'Retrieved FASTA sequences from NCBI'
        ],
        [
            'file_type' => 'protein_table',
            'file_path' => "runs/run_" . $run_id . "/proteins.tsv",
            'description' => 'Parsed TSV table of protein records'
        ]
    ];

    foreach ($files_to_add as $f) {
        $check_sql = "SELECT file_id
                      FROM run_files
                      WHERE run_id = :run_id
                        AND file_type = :file_type
                        AND file_path = :file_path";
        $check_stmt = $pdo->prepare($check_sql);
        $check_stmt->execute([
            ':run_id' => $run_id,
            ':file_type' => $f['file_type'],
            ':file_path' => $f['file_path']
        ]);

        if (!$check_stmt->fetch()) {
            $insert_file_sql = "INSERT INTO run_files (run_id, file_type, file_path, description)
                                VALUES (:run_id, :file_type, :file_path, :description)";
            $insert_file_stmt = $pdo->prepare($insert_file_sql);
            $insert_file_stmt->execute([
                ':run_id' => $run_id,
                ':file_type' => $f['file_type'],
                ':file_path' => $f['file_path'],
                ':description' => $f['description']
            ]);
        }
    }

    echo "<p>Imported $count protein records for run $run_id.</p>";
    echo "<p>Final run status: " . htmlspecialchars($final_status) . "</p>";
    echo "<p><a href='pw_vruns.php?run_id=" . htmlspecialchars($run_id) . "'>Back to run details</a></p>";

} catch (Exception $e) {
    $fail_stmt = $pdo->prepare("UPDATE runs SET status = :status WHERE run_id = :run_id");
    $fail_stmt->execute([
        ':status' => 'failed',
        ':run_id' => $run_id
    ]);
    die("Import error: " . $e->getMessage());
} catch (PDOException $e) {
    $fail_stmt = $pdo->prepare("UPDATE runs SET status = :status WHERE run_id = :run_id");
    $fail_stmt->execute([
        ':status' => 'failed',
        ':run_id' => $run_id
    ]);
    die("Database error: " . $e->getMessage());
}

echo "</body></html>";
?>