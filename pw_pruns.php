<?php
session_start();
require_once 'login.php';
include 'pw_redir.php';

echo <<<_HEAD1
<html>
<head>
    <title>Previous Runs</title>
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

$user_session_key = $_SESSION['user_session_key'] ?? session_id();

$sql = "SELECT run_id, user_forname, user_surname, protein_family, taxon_query,
               run_type, status, sequence_count, created_at
        FROM runs
        WHERE user_session_key = :usk
           OR run_type = 'example'
        ORDER BY created_at DESC, run_id DESC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':usk' => $user_session_key]);
    $runs = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Unable to retrieve runs: " . $e->getMessage());
}

echo "<h1>Previous Runs</h1>";
echo "<p>This page shows the example dataset and the runs created in your current session.</p>";

if (count($runs) === 0) {
    echo "<p>No runs have been saved yet.</p>";
} else {
    echo "<table border='1' cellpadding='6' cellspacing='0' align='center'>";
    echo "<tr>";
    echo "<th>Run ID</th>";
    echo "<th>User</th>";
    echo "<th>Protein Family</th>";
    echo "<th>Taxonomic Group</th>";
    echo "<th>Run Type</th>";
    echo "<th>Status</th>";
    echo "<th>Sequence Count</th>";
    echo "<th>Created At</th>";
    echo "<th>Details</th>";
    echo "</tr>";

    foreach ($runs as $row) {
        $run_id = htmlspecialchars($row['run_id']);
        $user = htmlspecialchars($row['user_forname'] . ' ' . $row['user_surname']);
        $protein_family = htmlspecialchars($row['protein_family']);
        $taxon_query = htmlspecialchars($row['taxon_query']);
        $run_type = htmlspecialchars($row['run_type']);
        $status = htmlspecialchars($row['status']);
        $sequence_count = htmlspecialchars($row['sequence_count']);
        $created_at = htmlspecialchars($row['created_at']);

        echo "<tr>";
        echo "<td>$run_id</td>";
        echo "<td>$user</td>";
        echo "<td>$protein_family</td>";
        echo "<td>$taxon_query</td>";
        echo "<td>$run_type</td>";
        echo "<td>$status</td>";
        echo "<td>$sequence_count</td>";
        echo "<td>$created_at</td>";
        echo "<td><a href='pw_vruns.php?run_id=$run_id'>View</a></td>";
        echo "</tr>";
    }

    echo "</table>";
}

echo <<<_TAIL1
</body>
</html>
_TAIL1;
?>