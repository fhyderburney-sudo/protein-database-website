<?php
session_start();
require_once 'login.php';
include 'pw_redir.php';

echo <<<_HEAD1
<html>
<head>
    <title>New Analysis</title>
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
<h1>New Analysis</h1>
<p>
Use this page to create a new protein analysis run.
Enter a protein family and a taxonomic group to save your query.
</p>
_MAIN1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $protein_family = trim($_POST['protein_family'] ?? '');
    $taxon_query = trim($_POST['taxon_query'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    $user_forname = $_SESSION['forname'] ?? 'Unknown';
    $user_surname = $_SESSION['surname'] ?? 'User';

    if ($protein_family === '' || $taxon_query === '') {
        echo "<p><strong>Error:</strong> Protein family and taxonomic group are required.</p>";
    } else {
        $ncbi_query = $protein_family . "[Protein Name] AND " . $taxon_query . "[Organism]";

        $sql = "INSERT INTO runs
                (user_forname, user_surname, protein_family, taxon_query, ncbi_query, run_type, status, sequence_count, notes)
                VALUES
                (:ufn, :usn, :pf, :tq, :nq, :rt, :st, :sc, :nt)";

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':ufn' => $user_forname,
                ':usn' => $user_surname,
                ':pf'  => $protein_family,
                ':tq'  => $taxon_query,
                ':nq'  => $ncbi_query,
                ':rt'  => 'user',
                ':st'  => 'pending',
                ':sc'  => 0,
                ':nt'  => $notes
            ]);

            $new_run_id = $pdo->lastInsertId();

            echo "<h2>Run created successfully</h2>";
            echo "<pre>";
            echo "Run ID: " . htmlspecialchars($new_run_id) . "\n";
            echo "Protein family: " . htmlspecialchars($protein_family) . "\n";
            echo "Taxonomic group: " . htmlspecialchars($taxon_query) . "\n";
            echo "NCBI query: " . htmlspecialchars($ncbi_query) . "\n";
            echo "Status: pending\n";
            echo "</pre>";
            echo "<p><a href='pw_vruns.php?run_id=" . htmlspecialchars($new_run_id) . "'>View this run</a></p>";

        } catch (PDOException $e) {
            die("Unable to create run: " . $e->getMessage());
        }
    }
}

echo <<<_TAIL1
<form action="pw_p2.php" method="post">
<pre>
Protein family     <input type="text" name="protein_family" size="40"/>
Taxonomic group    <input type="text" name="taxon_query" size="40"/>
Notes              <input type="text" name="notes" size="60"/>

                   <input type="submit" value="Create Run"/>
</pre>
</form>

</body>
</html>
_TAIL1;
?>