<?php
session_start();
require_once 'login.php';
include 'pw_redir.php';

// Return JSON output
header('Content-Type: application/json; charset=utf-8');

// PDO connection
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
    echo json_encode([
        'error' => 'Unable to connect to database',
        'message' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
    exit();
}

$run_id = $_GET['run_id'] ?? '';
$user_session_key = $_SESSION['user_session_key'] ?? session_id();

if ($run_id === '' || !ctype_digit($run_id)) {
    echo json_encode([
        'error' => 'Invalid run ID'
    ], JSON_PRETTY_PRINT);
    exit();
}

// Retrieve run metadata
$run_sql = "SELECT run_id, user_forname, user_surname, user_session_key,
                   protein_family, taxon_query, max_sequences, ncbi_query,
                   run_type, status, sequence_count, created_at, notes
            FROM runs
            WHERE run_id = :run_id";

try {
    $run_stmt = $pdo->prepare($run_sql);
    $run_stmt->execute([':run_id' => $run_id]);
    $run = $run_stmt->fetch();
} catch (PDOException $e) {
    echo json_encode([
        'error' => 'Unable to retrieve run',
        'message' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
    exit();
}

if (!$run) {
    echo json_encode([
        'error' => 'Run not found'
    ], JSON_PRETTY_PRINT);
    exit();
}

// Access control: only current session or shared example runs
if ($run['run_type'] !== 'example' && $run['user_session_key'] !== $user_session_key) {
    echo json_encode([
        'error' => 'You do not have permission to access this run'
    ], JSON_PRETTY_PRINT);
    exit();
}

// Retrieve proteins
$protein_sql = "SELECT protein_id, accession, protein_name, organism, seq_length
                FROM proteins
                WHERE run_id = :run_id
                ORDER BY organism, protein_name";

try {
    $protein_stmt = $pdo->prepare($protein_sql);
    $protein_stmt->execute([':run_id' => $run_id]);
    $proteins = $protein_stmt->fetchAll();
} catch (PDOException $e) {
    echo json_encode([
        'error' => 'Unable to retrieve proteins',
        'message' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
    exit();
}

// Retrieve output files
$file_sql = "SELECT file_id, file_type, file_path, description, created_at
             FROM run_files
             WHERE run_id = :run_id
             ORDER BY created_at DESC, file_id DESC";

try {
    $file_stmt = $pdo->prepare($file_sql);
    $file_stmt->execute([':run_id' => $run_id]);
    $run_files = $file_stmt->fetchAll();
} catch (PDOException $e) {
    echo json_encode([
        'error' => 'Unable to retrieve output files',
        'message' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
    exit();
}

// Remove internal session key before returning JSON if you do not want to expose it
unset($run['user_session_key']);

$output = [
    'run' => $run,
    'proteins' => $proteins,
    'output_files' => $run_files
];

echo json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>