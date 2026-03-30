<?php
session_start();
require_once 'login.php';
include 'pw_redir.php';

echo <<<_HEAD1
<html>
<head>
    <title>New Analysis</title>
    <link rel="stylesheet" type="text/css" href="pw_style.css">
    <script type="text/javascript">
    function validate(form) {
        let fail = "";

        if (form.protein_family.value.trim() === "") {
            fail += "Protein family is required.\\n";
        }

        if (form.taxon_query.value.trim() === "") {
            fail += "Taxonomic group is required.\\n";
        }

        if (form.max_sequences.value.trim() !== "") {
            if (isNaN(form.max_sequences.value)) {
                fail += "Max sequences must be a number.\\n";
            }
        }

        if (fail === "") {
            return true;
        } else {
            alert(fail);
            return false;
        }
    }
    </script>
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

echo "<h1>New Analysis</h1>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $protein_family = trim($_POST['protein_family'] ?? '');
    $taxon_query = trim($_POST['taxon_query'] ?? '');
    $max_sequences = trim($_POST['max_sequences'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    $user_forname = $_SESSION['forname'] ?? 'Unknown';
    $user_surname = $_SESSION['surname'] ?? 'User';

    if ($protein_family === '' || $taxon_query === '') {
        echo "<p><strong>Error:</strong> Protein family and taxonomic group are required.</p>";
    } else {
        if ($max_sequences === '' || !ctype_digit($max_sequences)) {
            $max_sequences = 20;
        } else {
            $max_sequences = (int)$max_sequences;
        }

        $ncbi_query = $protein_family . "[Protein Name] AND " . $taxon_query . "[Organism]";

        $sql = "INSERT INTO runs
                (user_forname, user_surname, protein_family, taxon_query, max_sequences, ncbi_query, run_type, status, sequence_count, notes)
                VALUES
                (:ufn, :usn, :pf, :tq, :mx, :nq, :rt, :st, :sc, :nt)";

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':ufn' => $user_forname,
                ':usn' => $user_surname,
                ':pf'  => $protein_family,
                ':tq'  => $taxon_query,
                ':mx'  => $max_sequences,
                ':nq'  => $ncbi_query,
                ':rt'  => 'user',
                ':st'  => 'pending',
                ':sc'  => 0,
                ':nt'  => $notes
            ]);

            $new_run_id = $pdo->lastInsertId();

            echo "<h2>Run created successfully</h2>";

            echo "<p><a href='pw_import_proteins.php?run_id=" . htmlspecialchars($new_run_id) . "'>Fetch and import sequences</a></p>";
            echo "<p><a href='pw_vruns.php?run_id=" . htmlspecialchars($new_run_id) . "'>View run</a></p>";

        } catch (PDOException $e) {
            die("Unable to create run: " . $e->getMessage());
        }
    }
}

echo <<<_TAIL1
<form action="pw_p2.php" method="post" onsubmit="return validate(this)">
<pre>
Protein family     <input type="text" name="protein_family" size="40"
                     onfocus="showHelp('Enter a protein family, for example glucose-6-phosphatase or kinase')"
                     onblur="clearHelp()"/>
Taxonomic group    <input type="text" name="taxon_query" size="40"
                     onfocus="showHelp('Enter a taxonomic group, for example Aves, Mammalia, or Rodentia')"
                     onblur="clearHelp()"/>
Max sequences      <input type="text" name="max_sequences" size="10"
                     onfocus="showHelp('Enter the maximum number of sequences to retrieve')"
                     onblur="clearHelp()"/>
Notes              <input type="text" name="notes" size="60"
                     onfocus="showHelp('Optional: add notes about this analysis run')"
                     onblur="clearHelp()"/>

                   <input type="submit" value="Create Run"/>
</pre>
</form>

</body>
</html>
_TAIL1;
?>