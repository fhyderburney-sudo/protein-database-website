<?php
session_start();
require_once 'login.php';
include 'pw_redir.php';

echo <<<_HEAD1
<html>
<head>
    <title>Plots</title>
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

echo <<<_MAIN1
<h1>Plots and Visual Outputs</h1>
<p>
This page shows graphical and file-based outputs generated during sequence analysis,
including conservation plots and linked analysis files.
</p>
_MAIN1;

// Show example run conservation plot if available
$example_plot = __DIR__ . "/runs/run_1/conservation.1.png";

echo "<h2>Example Dataset Conservation Plot</h2>";

if (file_exists($example_plot) && filesize($example_plot) > 0) {
    echo "<p>The image below shows the conservation profile for the example dataset alignment.</p>";
    echo "<img src='runs/run_1/conservation.1.png' width='700' alt='Example conservation plot'>";
} else {
    echo "<p>No example conservation plot is currently available.</p>";
}

// Retrieve run file outputs
$sql = "SELECT r.run_id, r.protein_family, r.taxon_query, rf.file_type, rf.file_path, rf.description, rf.created_at
        FROM run_files rf
        JOIN runs r ON rf.run_id = r.run_id
        ORDER BY rf.created_at DESC, rf.file_id DESC";

try {
    $stmt = $pdo->query($sql);
    $files = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Unable to retrieve plot/output files: " . $e->getMessage());
}

echo "<h2>Available Analysis Outputs</h2>";

if (count($files) === 0) {
    echo "<p>No plot or analysis output files have been recorded yet.</p>";
} else {
    echo "<table border='1' cellpadding='6' cellspacing='0'>";
    echo "<tr>";
    echo "<th>Run ID</th>";
    echo "<th>Protein Family</th>";
    echo "<th>Taxonomic Group</th>";
    echo "<th>File Type</th>";
    echo "<th>Description</th>";
    echo "<th>Created At</th>";
    echo "<th>Open</th>";
    echo "<th>Run Details</th>";
    echo "</tr>";

    foreach ($files as $row) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['run_id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['protein_family']) . "</td>";
        echo "<td>" . htmlspecialchars($row['taxon_query']) . "</td>";
        echo "<td>" . htmlspecialchars($row['file_type']) . "</td>";
        echo "<td>" . htmlspecialchars($row['description']) . "</td>";
        echo "<td>" . htmlspecialchars($row['created_at']) . "</td>";
        echo "<td><a href='" . htmlspecialchars($row['file_path']) . "'>View file</a></td>";
        echo "<td><a href='pw_vruns.php?run_id=" . htmlspecialchars($row['run_id']) . "'>View run</a></td>";
        echo "</tr>";
    }

    echo "</table>";
}

echo <<<_TAIL1
</body>
</html>
_TAIL1;
?>