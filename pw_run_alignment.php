<?php
session_start();
require_once 'login.php';
include 'pw_redir.php';

echo <<<_HEAD1
<html>
<head>
    <title>Run Alignment</title>
    <link rel="stylesheet" type="text/css" href="pw_style.css">
</head>
<body>
_HEAD1;

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
$user_session_key = $_SESSION['user_session_key'] ?? session_id();

if ($run_id === '' || !ctype_digit($run_id)) {
    die("Invalid run ID.");
}

// Retrieve run and check access rights
$run_sql = "SELECT run_id, user_session_key, protein_family, taxon_query, run_type, status
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

// Access control: only current session or shared example runs
if ($run['run_type'] !== 'example' && $run['user_session_key'] !== $user_session_key) {
    die("You do not have permission to modify this run.");
}

$script = __DIR__ . "/run_alignment.sh";
$fasta_file = __DIR__ . "/runs/run_" . $run_id . "/sequences.fasta";
$alignment_file = __DIR__ . "/runs/run_" . $run_id . "/alignment.aln";
$rel_path = "runs/run_" . $run_id . "/alignment.aln";

if (!file_exists($script)) {
    die("Alignment script not found.");
}

echo "<h1>Run Alignment</h1>";
echo "<p>This page runs a Clustal Omega alignment for the selected dataset and records the resulting alignment file.</p>";

// Check that imported FASTA exists before trying alignment
if (!file_exists($fasta_file) || filesize($fasta_file) === 0) {
    echo "<p>No FASTA sequence file is available for this run yet.</p>";
    echo "<p>Please run sequence fetch/import before attempting alignment.</p>";
    echo "<p><a href='pw_import_proteins.php?run_id=" . htmlspecialchars($run_id) . "'>Fetch and import sequences for this run</a></p>";
    echo "<p><a href='pw_vruns.php?run_id=" . htmlspecialchars($run_id) . "'>Back to run details</a></p>";

    echo <<<_TAIL1
</body>
</html>
_TAIL1;
    exit();
}

$script_arg = escapeshellarg($script);
$run_id_arg = escapeshellarg($run_id);

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

$command = "$script_arg $run_id_arg 2>&1";
$output = shell_exec($command);

echo "<h2>Alignment Output</h2>";
echo "<pre>" . htmlspecialchars($output ?? 'No output returned.') . "</pre>";

if (strpos($output, "Alignment saved") !== false && file_exists($alignment_file) && filesize($alignment_file) > 0) {
    try {
        $check_sql = "SELECT file_id
                      FROM run_files
                      WHERE run_id = :run_id
                        AND file_type = :file_type
                        AND file_path = :file_path";
        $check_stmt = $pdo->prepare($check_sql);
        $check_stmt->execute([
            ':run_id' => $run_id,
            ':file_type' => 'alignment',
            ':file_path' => $rel_path
        ]);

        $existing = $check_stmt->fetch();

        if (!$existing) {
            $insert_sql = "INSERT INTO run_files (run_id, file_type, file_path, description)
                           VALUES (:run_id, :file_type, :file_path, :description)";
            $insert_stmt = $pdo->prepare($insert_sql);
            $insert_stmt->execute([
                ':run_id' => $run_id,
                ':file_type' => 'alignment',
                ':file_path' => $rel_path,
                ':description' => 'Clustal Omega multiple sequence alignment'
            ]);
        }

        $complete_stmt = $pdo->prepare("UPDATE runs SET status = :status WHERE run_id = :run_id");
        $complete_stmt->execute([
            ':status' => 'complete',
            ':run_id' => $run_id
        ]);

        echo "<p>Alignment completed successfully.</p>";
        echo "<p><a href='" . htmlspecialchars($rel_path) . "'>Open alignment file</a></p>";
        echo "<p>Redirecting to run details page...</p>";
        echo "<meta http-equiv='refresh' content='2;url=pw_vruns.php?run_id=" . htmlspecialchars($run_id) . "'>";
        echo "<p><a href='pw_vruns.php?run_id=" . htmlspecialchars($run_id) . "'>Go to run details now</a></p>";
    } catch (PDOException $e) {
        die("Alignment completed, but database update failed: " . $e->getMessage());
    }
} else {
    try {
        $fail_stmt = $pdo->prepare("UPDATE runs SET status = :status WHERE run_id = :run_id");
        $fail_stmt->execute([
            ':status' => 'failed',
            ':run_id' => $run_id
        ]);
    } catch (PDOException $e) {
        die("Alignment failed, and status update also failed: " . $e->getMessage());
    }

    echo "<p>Alignment failed. No alignment file was produced.</p>";
    echo "<p>Check that the run has imported protein sequences and that Clustal Omega is available on the server.</p>";
    echo "<p><a href='pw_vruns.php?run_id=" . htmlspecialchars($run_id) . "'>Back to run details</a></p>";
}

echo <<<_TAIL1
</body>
</html>
_TAIL1;
?>