<?php
session_start();
require_once 'login.php';
include 'pw_redir.php';

echo <<<_HEAD1
<html>
<head>
    <title>Statistics</title>
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

$user_session_key = $_SESSION['user_session_key'] ?? session_id();

echo <<<_MAIN1
<h1>Statistics</h1>
<p>
This page summarises the protein data currently visible to your session.
It provides descriptive statistics for imported protein sequences and for individual analysis runs.
Shared example data are included, alongside runs created in your current session.
</p>
_MAIN1;

// --------------------
// Overall protein statistics for current session + example runs
// --------------------
$stats_sql = "SELECT 
                COUNT(*) AS total_proteins,
                COUNT(DISTINCT p.organism) AS total_organisms,
                AVG(p.seq_length) AS avg_length,
                MIN(p.seq_length) AS min_length,
                MAX(p.seq_length) AS max_length,
                STD(p.seq_length) AS std_length
              FROM proteins p
              JOIN runs r ON p.run_id = r.run_id
              WHERE r.user_session_key = :usk
                 OR r.run_type = 'example'";

try {
    $stmt = $pdo->prepare($stats_sql);
    $stmt->execute([':usk' => $user_session_key]);
    $stats = $stmt->fetch();
} catch (PDOException $e) {
    die("Unable to retrieve overall statistics: " . $e->getMessage());
}

echo "<h2>Overall Protein Statistics</h2>";

if (!$stats || $stats['total_proteins'] == 0) {
    echo "<p>No protein records are currently available for this session.</p>";
} else {
    echo "<table border='1' cellpadding='6' cellspacing='0'>";
    echo "<tr><th>Statistic</th><th>Value</th></tr>";
    echo "<tr><td>Total proteins</td><td>" . htmlspecialchars($stats['total_proteins']) . "</td></tr>";
    echo "<tr><td>Distinct organisms</td><td>" . htmlspecialchars($stats['total_organisms']) . "</td></tr>";
    echo "<tr><td>Average sequence length</td><td>" . round($stats['avg_length'], 2) . "</td></tr>";
    echo "<tr><td>Minimum sequence length</td><td>" . htmlspecialchars($stats['min_length']) . "</td></tr>";
    echo "<tr><td>Maximum sequence length</td><td>" . htmlspecialchars($stats['max_length']) . "</td></tr>";
    echo "<tr><td>Standard deviation of sequence length</td><td>" . round($stats['std_length'], 2) . "</td></tr>";
    echo "</table>";

    echo "<p>";
    echo "These values summarise all protein sequences currently visible to your session, including the shared example dataset. ";
    echo "Sequence length statistics can help indicate whether datasets are relatively uniform or contain broader variation.";
    echo "</p>";
}

// --------------------
// Top organisms summary for current session + example runs
// --------------------
$organism_sql = "SELECT p.organism, COUNT(*) AS protein_count
                 FROM proteins p
                 JOIN runs r ON p.run_id = r.run_id
                 WHERE (r.user_session_key = :usk OR r.run_type = 'example')
                   AND p.organism IS NOT NULL
                   AND p.organism <> ''
                 GROUP BY p.organism
                 ORDER BY protein_count DESC, p.organism ASC
                 LIMIT 10";

try {
    $org_stmt = $pdo->prepare($organism_sql);
    $org_stmt->execute([':usk' => $user_session_key]);
    $organism_stats = $org_stmt->fetchAll();
} catch (PDOException $e) {
    die("Unable to retrieve organism statistics: " . $e->getMessage());
}

echo "<h2>Most Frequent Organisms</h2>";

if (count($organism_stats) === 0) {
    echo "<p>No organism summary is currently available.</p>";
} else {
    echo "<table border='1' cellpadding='6' cellspacing='0'>";
    echo "<tr><th>Organism</th><th>Protein Count</th></tr>";

    foreach ($organism_stats as $row) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['organism']) . "</td>";
        echo "<td>" . htmlspecialchars($row['protein_count']) . "</td>";
        echo "</tr>";
    }

    echo "</table>";
}

// --------------------
// Statistics by run for current session + example runs
// --------------------
$run_stats_sql = "SELECT 
                    r.run_id,
                    r.protein_family,
                    r.taxon_query,
                    r.run_type,
                    r.status,
                    r.sequence_count,
                    COUNT(p.protein_id) AS protein_count,
                    AVG(p.seq_length) AS avg_length,
                    MIN(p.seq_length) AS min_length,
                    MAX(p.seq_length) AS max_length
                  FROM runs r
                  LEFT JOIN proteins p ON r.run_id = p.run_id
                  WHERE r.user_session_key = :usk
                     OR r.run_type = 'example'
                  GROUP BY r.run_id, r.protein_family, r.taxon_query, r.run_type, r.status, r.sequence_count
                  ORDER BY r.run_id DESC";

try {
    $stmt2 = $pdo->prepare($run_stats_sql);
    $stmt2->execute([':usk' => $user_session_key]);
    $run_stats = $stmt2->fetchAll();
} catch (PDOException $e) {
    die("Unable to retrieve run statistics: " . $e->getMessage());
}

echo "<h2>Statistics by Run</h2>";

if (count($run_stats) === 0) {
    echo "<p>No runs are currently available for this session.</p>";
} else {
    echo "<table border='1' cellpadding='6' cellspacing='0'>";
    echo "<tr>";
    echo "<th>Run ID</th>";
    echo "<th>Protein Family</th>";
    echo "<th>Taxonomic Group</th>";
    echo "<th>Run Type</th>";
    echo "<th>Status</th>";
    echo "<th>Stored Sequence Count</th>";
    echo "<th>Imported Protein Count</th>";
    echo "<th>Average Length</th>";
    echo "<th>Min Length</th>";
    echo "<th>Max Length</th>";
    echo "<th>Details</th>";
    echo "</tr>";

    foreach ($run_stats as $row) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['run_id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['protein_family']) . "</td>";
        echo "<td>" . htmlspecialchars($row['taxon_query']) . "</td>";
        echo "<td>" . htmlspecialchars($row['run_type']) . "</td>";
        echo "<td>" . htmlspecialchars($row['status']) . "</td>";
        echo "<td>" . htmlspecialchars($row['sequence_count']) . "</td>";
        echo "<td>" . htmlspecialchars($row['protein_count']) . "</td>";
        echo "<td>" . ($row['avg_length'] !== null ? round($row['avg_length'], 2) : "N/A") . "</td>";
        echo "<td>" . ($row['min_length'] !== null ? htmlspecialchars($row['min_length']) : "N/A") . "</td>";
        echo "<td>" . ($row['max_length'] !== null ? htmlspecialchars($row['max_length']) : "N/A") . "</td>";
        echo "<td><a href='pw_vruns.php?run_id=" . htmlspecialchars($row['run_id']) . "'>View</a></td>";
        echo "</tr>";
    }

    echo "</table>";

    echo "<p>";
    echo "Run-level statistics help compare the shared example dataset with runs created in your current session, ";
    echo "including how many proteins were imported and whether sequence lengths vary substantially between runs.";
    echo "</p>";
}

echo <<<_TAIL1
</body>
</html>
_TAIL1;
?>