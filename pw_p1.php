<?php
session_start();
require_once 'login.php';
include 'pw_redir.php';

echo <<<_HEAD1
<html>
<head>
    <title>Example Dataset</title>
    <link rel="stylesheet" type="text/css" href="pw_style.css">
</head>
<body>
_HEAD1;

echo "<div style='background-color:#dceffe; padding:12px; margin-bottom:20px; border:1px solid #c0d8ef;'>";
echo "<h1>Protein Sequence Analysis Website</h1>";
echo "<p class='section-note'>Retrieve, analyse, and revisit protein datasets across taxonomic groups.</p>";
echo "</div>";

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

// Retrieve the shared example run
$example_sql = "SELECT run_id, protein_family, taxon_query, run_type, status,
                       sequence_count, created_at, notes
                FROM runs
                WHERE run_type = 'example'
                ORDER BY run_id ASC
                LIMIT 1";

try {
    $stmt = $pdo->query($example_sql);
    $example_run = $stmt->fetch();
} catch (PDOException $e) {
    die("Unable to retrieve example dataset: " . $e->getMessage());
}

echo <<<_MAIN1
<h1>Example Dataset</h1>

<p>
This page provides access to the preloaded example dataset for the website.
The example dataset is intended to demonstrate the main workflow before you create your own run.
</p>

<p>
The example run is based on <strong>glucose-6-phosphatase proteins from Aves</strong>,
and can be used to explore stored proteins, alignments, conservation plots, motif scanning,
and revisiting saved outputs.
</p>
_MAIN1;

if (!$example_run) {
    echo "<p>No example dataset is currently available in the database.</p>";
} else {
    echo "<h2>Dataset Summary</h2>";
    echo "<table border='1' cellpadding='6' cellspacing='0'>";
    echo "<tr><th>Field</th><th>Value</th></tr>";
    echo "<tr><td>Run ID</td><td>" . htmlspecialchars($example_run['run_id']) . "</td></tr>";
    echo "<tr><td>Protein Family</td><td>" . htmlspecialchars($example_run['protein_family']) . "</td></tr>";
    echo "<tr><td>Taxonomic Group</td><td>" . htmlspecialchars($example_run['taxon_query']) . "</td></tr>";
    echo "<tr><td>Run Type</td><td>" . htmlspecialchars($example_run['run_type']) . "</td></tr>";
    echo "<tr><td>Status</td><td>" . htmlspecialchars($example_run['status']) . "</td></tr>";
    echo "<tr><td>Sequence Count</td><td>" . htmlspecialchars($example_run['sequence_count']) . "</td></tr>";
    echo "<tr><td>Created At</td><td>" . htmlspecialchars($example_run['created_at']) . "</td></tr>";
    echo "<tr><td>Notes</td><td>" . htmlspecialchars($example_run['notes']) . "</td></tr>";
    echo "</table>";

    echo "<h2>Open the Example Run</h2>";
    echo "<p><a href='pw_vruns.php?run_id=" . htmlspecialchars($example_run['run_id']) . "'>Open the example dataset run</a></p>";

    echo "<p>";
    echo "You can use this run to explore how the website stores and displays protein sequences, ";
    echo "alignment outputs, conservation plots, and motif scanning results. ";
    echo "After that, you can create your own run using the New Analysis page.";
    echo "</p>";
}

echo <<<_TAIL1
</body>
</html>
_TAIL1;
?>