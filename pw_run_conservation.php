<?php
session_start();
require_once 'login.php';
include 'pw_redir.php';

echo "<html><head><title>Run Conservation Analysis</title> <link rel="stylesheet" type="text/css" href="pw_style.css"></head><body>";
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

$script = __DIR__ . "/run_conservation.sh";

if (!file_exists($script)) {
    die("Conservation script not found.");
}

$script_arg = escapeshellarg($script);
$run_id_arg = escapeshellarg($run_id);

echo "<h1>Run Conservation Analysis</h1>";

$command = "$script_arg $run_id_arg 2>&1";
$output = shell_exec($command);

echo "<pre>" . htmlspecialchars($output ?? 'No output returned.') . "</pre>";

$plot1 = __DIR__ . "/runs/run_" . $run_id . "/conservation.1.png";
$plot2 = __DIR__ . "/runs/run_" . $run_id . "/conservation.png";

$plot_path = null;
$rel_path = null;

if (strpos($output, "Conservation plot saved") !== false) {
    if (file_exists($plot1) && filesize($plot1) > 0) {
        $plot_path = $plot1;
        $rel_path = "runs/run_" . $run_id . "/conservation.1.png";
    } elseif (file_exists($plot2) && filesize($plot2) > 0) {
        $plot_path = $plot2;
        $rel_path = "runs/run_" . $run_id . "/conservation.png";
    }
}

if ($plot_path !== null) {
    try {
        $check_sql = "SELECT file_id
                      FROM run_files
                      WHERE run_id = :run_id
                        AND file_type = :file_type
                        AND file_path = :file_path";
        $check_stmt = $pdo->prepare($check_sql);
        $check_stmt->execute([
            ':run_id' => $run_id,
            ':file_type' => 'conservation_plot',
            ':file_path' => $rel_path
        ]);

        $existing = $check_stmt->fetch();

        if (!$existing) {
            $insert_sql = "INSERT INTO run_files (run_id, file_type, file_path, description)
                           VALUES (:run_id, :file_type, :file_path, :description)";
            $insert_stmt = $pdo->prepare($insert_sql);
            $insert_stmt->execute([
                ':run_id' => $run_id,
                ':file_type' => 'conservation_plot',
                ':file_path' => $rel_path,
                ':description' => 'EMBOSS plotcon conservation plot'
            ]);
        }

        echo "<p>Conservation analysis completed successfully.</p>";
        echo "<p><a href='" . htmlspecialchars($rel_path) . "'>Open conservation plot</a></p>";
    } catch (PDOException $e) {
        die("Conservation analysis completed, but database update failed: " . $e->getMessage());
    }
} else {
    echo "<p>Conservation analysis failed or no plot file was produced.</p>";
}

echo "<p><a href='pw_vruns.php?run_id=" . htmlspecialchars($run_id) . "'>Back to run details</a></p>";
echo "</body></html>";
?>
