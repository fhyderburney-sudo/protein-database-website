<?php
session_start();
require_once 'login.php';
include 'pw_redir.php';

// Return XML output
header('Content-Type: application/xml; charset=utf-8');

// PDO connection
$charset = 'utf8mb4';
$dsn = "mysql:host=$hostname;dbname=$database;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

function xml_escape($text) {
    return htmlspecialchars((string)$text, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

try {
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
    echo "<error>\n";
    echo "  <message>" . xml_escape($e->getMessage()) . "</message>\n";
    echo "</error>\n";
    exit();
}

$run_id = $_GET['run_id'] ?? '';

if ($run_id === '' || !ctype_digit($run_id)) {
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
    echo "<error>\n";
    echo "  <message>Invalid run ID</message>\n";
    echo "</error>\n";
    exit();
}

// Retrieve run metadata
$run_sql = "SELECT run_id, user_forname, user_surname, protein_family, taxon_query,
                   max_sequences, ncbi_query, run_type, status, sequence_count, created_at, notes
            FROM runs
            WHERE run_id = :run_id";

try {
    $run_stmt = $pdo->prepare($run_sql);
    $run_stmt->execute([':run_id' => $run_id]);
    $run = $run_stmt->fetch();
} catch (PDOException $e) {
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
    echo "<error>\n";
    echo "  <message>" . xml_escape($e->getMessage()) . "</message>\n";
    echo "</error>\n";
    exit();
}

if (!$run) {
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
    echo "<error>\n";
    echo "  <message>Run not found</message>\n";
    echo "</error>\n";
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
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
    echo "<error>\n";
    echo "  <message>" . xml_escape($e->getMessage()) . "</message>\n";
    echo "</error>\n";
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
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
    echo "<error>\n";
    echo "  <message>" . xml_escape($e->getMessage()) . "</message>\n";
    echo "</error>\n";
    exit();
}

echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
echo "<run_export>\n";

echo "  <run>\n";
echo "    <run_id>" . xml_escape($run['run_id']) . "</run_id>\n";
echo "    <user_forname>" . xml_escape($run['user_forname']) . "</user_forname>\n";
echo "    <user_surname>" . xml_escape($run['user_surname']) . "</user_surname>\n";
echo "    <protein_family>" . xml_escape($run['protein_family']) . "</protein_family>\n";
echo "    <taxon_query>" . xml_escape($run['taxon_query']) . "</taxon_query>\n";
echo "    <max_sequences>" . xml_escape($run['max_sequences']) . "</max_sequences>\n";
echo "    <ncbi_query>" . xml_escape($run['ncbi_query']) . "</ncbi_query>\n";
echo "    <run_type>" . xml_escape($run['run_type']) . "</run_type>\n";
echo "    <status>" . xml_escape($run['status']) . "</status>\n";
echo "    <sequence_count>" . xml_escape($run['sequence_count']) . "</sequence_count>\n";
echo "    <created_at>" . xml_escape($run['created_at']) . "</created_at>\n";
echo "    <notes>" . xml_escape($run['notes']) . "</notes>\n";
echo "  </run>\n";

echo "  <proteins>\n";
foreach ($proteins as $protein) {
    echo "    <protein>\n";
    echo "      <protein_id>" . xml_escape($protein['protein_id']) . "</protein_id>\n";
    echo "      <accession>" . xml_escape($protein['accession']) . "</accession>\n";
    echo "      <protein_name>" . xml_escape($protein['protein_name']) . "</protein_name>\n";
    echo "      <organism>" . xml_escape($protein['organism']) . "</organism>\n";
    echo "      <seq_length>" . xml_escape($protein['seq_length']) . "</seq_length>\n";
    echo "    </protein>\n";
}
echo "  </proteins>\n";

echo "  <output_files>\n";
foreach ($run_files as $file) {
    echo "    <file>\n";
    echo "      <file_id>" . xml_escape($file['file_id']) . "</file_id>\n";
    echo "      <file_type>" . xml_escape($file['file_type']) . "</file_type>\n";
    echo "      <file_path>" . xml_escape($file['file_path']) . "</file_path>\n";
    echo "      <description>" . xml_escape($file['description']) . "</description>\n";
    echo "      <created_at>" . xml_escape($file['created_at']) . "</created_at>\n";
    echo "    </file>\n";
}
echo "  </output_files>\n";

echo "</run_export>\n";
?>
