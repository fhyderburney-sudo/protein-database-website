<?php
session_start();
require_once 'login.php';
include 'pw_redir.php';

echo <<<_HEAD1
<html>
<head>
    <title>Run Details</title>
</head>
<body>
_HEAD1;

include 'pw_menuf.php';

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
    die("Unable to connect to database: " . $e->getMessage());
}

$run_id = $_GET['run_id'] ?? '';

if ($run_id === '' || !ctype_digit($run_id)) {
    die("Invalid run ID.");
}

$sql = "SELECT run_id, user_forname, user_surname, protein_family, taxon_query,
               ncbi_query, run_type, status, sequence_count, created_at, notes
        FROM runs
        WHERE run_id = :run_id";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':run_id' => $run_id]);
    $run = $stmt->fetch();
} catch (PDOException $e) {
    die("Unable to retrieve run: " . $e->getMessage());
}

if (!$run) {
    die("Run not found.");
}

echo <<<_MAIN1
<h1>Run Details</h1>
<p>This page shows the details of a selected analysis run.</p>
_MAIN1;

echo "<h2>Run Metadata</h2>";
echo "<table border='1' cellpadding='6' cellspacing='0'>";
echo "<tr><th>Field</th><th>Value</th></tr>";
echo "<tr><td>Run ID</td><td>" . htmlspecialchars($run['run_id']) . "</td></tr>";
echo "<tr><td>User</td><td>" . htmlspecialchars($run['user_forname'] . ' ' . $run['user_surname']) . "</td></tr>";
echo "<tr><td>Protein Family</td><td>" . htmlspecialchars($run['protein_family']) . "</td></tr>";
echo "<tr><td>Taxonomic Group</td><td>" . htmlspecialchars($run['taxon_query']) . "</td></tr>";
echo "<tr><td>NCBI Query</td><td>" . htmlspecialchars($run['ncbi_query']) . "</td></tr>";
echo "<tr><td>Run Type</td><td>" . htmlspecialchars($run['run_type']) . "</td></tr>";
echo "<tr><td>Status</td><td>" . htmlspecialchars($run['status']) . "</td></tr>";
echo "<tr><td>Sequence Count</td><td>" . htmlspecialchars($run['sequence_count']) . "</td></tr>";
echo "<tr><td>Created At</td><td>" . htmlspecialchars($run['created_at']) . "</td></tr>";
echo "<tr><td>Notes</td><td>" . htmlspecialchars($run['notes']) . "</td></tr>";
echo "</table>";

// Retrieve linked proteins for this run
$protein_sql = "SELECT protein_id, accession, protein_name, organism, seq_length
                FROM proteins
                WHERE run_id = :run_id
                ORDER BY organism, protein_name";

try {
    $protein_stmt = $pdo->prepare($protein_sql);
    $protein_stmt->execute([':run_id' => $run_id]);
    $proteins = $protein_stmt->fetchAll();
} catch (PDOException $e) {
    die("Unable to retrieve proteins: " . $e->getMessage());
}

echo "<h2>Protein Sequences in This Run</h2>";

if (count($proteins) === 0) {
    echo "<p>No protein sequences have been stored for this run yet.</p>";
} else {
    echo "<table border='1' cellpadding='6' cellspacing='0'>";
    echo "<tr>";
    echo "<th>Protein ID</th>";
    echo "<th>Accession</th>";
    echo "<th>Protein Name</th>";
    echo "<th>Organism</th>";
    echo "<th>Sequence Length</th>";
    echo "</tr>";

    foreach ($proteins as $protein) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($protein['protein_id']) . "</td>";
        echo "<td>" . htmlspecialchars($protein['accession']) . "</td>";
        echo "<td>" . htmlspecialchars($protein['protein_name']) . "</td>";
        echo "<td>" . htmlspecialchars($protein['organism']) . "</td>";
        echo "<td>" . htmlspecialchars($protein['seq_length']) . "</td>";
        echo "</tr>";
    }

    echo "</table>";
}

echo "<p><a href='pw_pruns.php'>Back to Previous Runs</a></p>";

echo <<<_TAIL1
</body>
</html>
_TAIL1;
?>