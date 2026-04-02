<?php
session_start();
require_once 'login.php';
include 'pw_redir.php';

echo <<<_HEAD1
<html>
<head>
    <title>Run Conservation Analysis</title>
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

$alignment_script = __DIR__ . "/run_alignment.sh";
$conservation_script = __DIR__ . "/run_conservation.sh";

if (!file_exists($alignment_script)) {
    die("Alignment script not found.");
}

if (!file_exists($conservation_script)) {
    die("Conservation script not found.");
}

$run_dir = __DIR__ . "/runs/run_" . $run_id;
$fasta_file = $run_dir . "/sequences.fasta";
$alignment_file = $run_dir . "/alignment.aln";

echo "<h1>Run Conservation Analysis</h1>";
echo "<p>This page runs EMBOSS plotcon on the selected dataset. If no alignment exists yet, it will first run Clustal Omega automatically.</p>";

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

// -------------------------------------------------
// Step 1: Ensure FASTA exists
// -------------------------------------------------
if (!file_exists($fasta_file) || filesize($fasta_file) === 0) {
    try {
        $fail_stmt = $pdo->prepare("UPDATE runs SET status = :status WHERE run_id = :run_id");
        $fail_stmt->execute([
            ':status' => 'failed',
            ':run_id' => $run_id
        ]);
    } catch (PDOException $e) {
        die("Missing FASTA file, and status update also failed: " . $e->getMessage());
    }

    echo "<h2>Step 1: Sequences required</h2>";
    echo "<p>No imported FASTA file is available for this run, so conservation analysis cannot continue.</p>";
    echo "<p>Please fetch and import sequences for this run first.</p>";
    echo "<p><a href='pw_import_proteins.php?run_id=" . htmlspecialchars($run_id) . "'>Fetch and import sequences for this run</a></p>";
    echo "<p><a href='pw_vruns.php?run_id=" . htmlspecialchars($run_id) . "'>Back to run details</a></p>";

    echo <<<_TAIL1
</body>
</html>
_TAIL1;
    exit();
}

// -------------------------------------------------
// Step 2: Ensure alignment exists
// -------------------------------------------------
if (!file_exists($alignment_file) || filesize($alignment_file) === 0) {
    echo "<h2>Step 2: Alignment required</h2>";
    echo "<p>No alignment file was found for this run, so alignment will be run automatically first.</p>";

    $alignment_cmd = escapeshellarg($alignment_script) . " " . escapeshellarg($run_id) . " 2>&1";
    $alignment_output = shell_exec($alignment_cmd);

    echo "<h3>Alignment Output</h3>";
    echo "<pre>" . htmlspecialchars($alignment_output ?? 'No output returned.') . "</pre>";

    if (!file_exists($alignment_file) || filesize($alignment_file) === 0) {
        try {
            $fail_stmt = $pdo->prepare("UPDATE runs SET status = :status WHERE run_id = :run_id");
            $fail_stmt->execute([
                ':status' => 'failed',
                ':run_id' => $run_id
            ]);
        } catch (PDOException $e) {
            die("Alignment failed, and status update also failed: " . $e->getMessage());
        }

        echo "<p>Automatic alignment failed, so conservation analysis could not continue.</p>";
        echo "<p>This usually means the FASTA file was missing, Clustal Omega failed, or the run folder was not writable.</p>";
        echo "<p><a href='pw_run_alignment.php?run_id=" . htmlspecialchars($run_id) . "'>Try running alignment directly</a></p>";
        echo "<p><a href='pw_vruns.php?run_id=" . htmlspecialchars($run_id) . "'>Back to run details</a></p>";

        echo <<<_TAIL2
</body>
</html>
_TAIL2;
        exit();
    } else {
        // record alignment file in run_files if not already present
        $alignment_rel_path = "runs/run_" . $run_id . "/alignment.aln";

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
                ':file_path' => $alignment_rel_path
            ]);

            if (!$check_stmt->fetch()) {
                $insert_sql = "INSERT INTO run_files (run_id, file_type, file_path, description)
                               VALUES (:run_id, :file_type, :file_path, :description)";
                $insert_stmt = $pdo->prepare($insert_sql);
                $insert_stmt->execute([
                    ':run_id' => $run_id,
                    ':file_type' => 'alignment',
                    ':file_path' => $alignment_rel_path,
                    ':description' => 'Clustal Omega multiple sequence alignment'
                ]);
            }
        } catch (PDOException $e) {
            die("Alignment succeeded, but alignment file could not be recorded: " . $e->getMessage());
        }
    }
} else {
    echo "<h2>Step 2: Alignment already available</h2>";
    echo "<p>An existing alignment file was found, so conservation analysis can continue directly.</p>";
}

// -------------------------------------------------
// Step 3: Run conservation
// -------------------------------------------------
echo "<h2>Step 3: Run conservation analysis</h2>";

$conservation_cmd = escapeshellarg($conservation_script) . " " . escapeshellarg($run_id) . " 2>&1";
$conservation_output = shell_exec($conservation_cmd);

echo "<h3>Conservation Analysis Output</h3>";
echo "<pre>" . htmlspecialchars($conservation_output ?? 'No output returned.') . "</pre>";

$plot_candidates = [
    __DIR__ . "/runs/run_" . $run_id . "/conservation.1.png" => "runs/run_" . $run_id . "/conservation.1.png",
    __DIR__ . "/runs/run_" . $run_id . "/conservation.png" => "runs/run_" . $run_id . "/conservation.png",
    __DIR__ . "/runs/run_" . $run_id . "/conservation.png.1.png" => "runs/run_" . $run_id . "/conservation.png.1.png"
];

$plot_path = null;
$rel_path = null;

foreach ($plot_candidates as $abs => $rel) {
    if (file_exists($abs) && filesize($abs) > 0) {
        $plot_path = $abs;
        $rel_path = $rel;
        break;
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

        if (!$check_stmt->fetch()) {
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

        $complete_stmt = $pdo->prepare("UPDATE runs SET status = :status WHERE run_id = :run_id");
        $complete_stmt->execute([
            ':status' => 'complete',
            ':run_id' => $run_id
        ]);

        echo "<p>Conservation analysis completed successfully.</p>";
        echo "<p><a href='" . htmlspecialchars($rel_path) . "'>Open conservation plot</a></p>";
        echo "<p>Redirecting to run details page...</p>";
        echo "<meta http-equiv='refresh' content='2;url=pw_vruns.php?run_id=" . htmlspecialchars($run_id) . "'>";
        echo "<p><a href='pw_vruns.php?run_id=" . htmlspecialchars($run_id) . "'>Go to run details now</a></p>";
    } catch (PDOException $e) {
        die("Conservation analysis completed, but database update failed: " . $e->getMessage());
    }
} else {
    try {
        $fail_stmt = $pdo->prepare("UPDATE runs SET status = :status WHERE run_id = :run_id");
        $fail_stmt->execute([
            ':status' => 'failed',
            ':run_id' => $run_id
        ]);
    } catch (PDOException $e) {
        die("Conservation analysis failed, and status update also failed: " . $e->getMessage());
    }

    echo "<p>Conservation analysis failed or no plot file was produced.</p>";
    echo "<p><a href='pw_vruns.php?run_id=" . htmlspecialchars($run_id) . "'>Back to run details</a></p>";
}

echo <<<_TAIL3
</body>
</html>
_TAIL3;
?>