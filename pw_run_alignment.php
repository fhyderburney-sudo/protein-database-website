<?php
session_start();
require_once 'login.php';
include 'pw_redir.php';

echo "<html><head><title>Run Alignment</title></head><body>";
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

$script = __DIR__ . "/run_alignment.sh";
$script_arg = escapeshellarg($script);
$run_id_arg = escapeshellarg($run_id);

//if (!file_exists($script)) {
//    die("Alignment script not found.");
//}

echo "<h1>Run Alignment</h1>";

$command = "$script_arg $run_id_arg 2>&1";
$output = shell_exec($command);

$alignment_file = __DIR__ . "/runs/run_" . $run_id . "/alignment.aln";

if (strpos($output, "Alignment saved") !== false && file_exists($alignment_file) && filesize($alignment_file) > 0) {
    try {
        $check_sql = "SELECT file_id FROM run_files WHERE run_id = :run_id AND file_type = :file_type AND file_path = :file_path";
        $check_stmt = $pdo->prepare($check_sql);
        $rel_path = "runs/run_" . $run_id . "/alignment.aln";

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

        echo "<p>Alignment completed successfully.</p>";
        echo "<p><a href='runs/run_" . htmlspecialchars($run_id) . "/alignment.aln'>Open alignment file</a></p>";
    } catch (PDOException $e) {
        die("Alignment completed, but database update failed: " . $e->getMessage());
    }
} else {
    echo "<p>Alignment failed.</p>";
}

echo "<p><a href='pw_vruns.php?run_id=" . htmlspecialchars($run_id) . "'>Back to run details</a></p>";
echo "</body></html>";
?>