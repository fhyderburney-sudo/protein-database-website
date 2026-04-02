<?php
session_start();
require_once 'login.php';
include 'pw_redir.php';

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

// Handle form submission BEFORE output
$error_message = '';

$protein_family = '';
$taxon_query = '';
$max_sequences = '20';
$notes = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $protein_family = trim($_POST['protein_family'] ?? '');
    $taxon_query = trim($_POST['taxon_query'] ?? '');
    $max_sequences = trim($_POST['max_sequences'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    $user_forname = $_SESSION['forname'] ?? 'Unknown';
    $user_surname = $_SESSION['surname'] ?? 'User';
    $user_session_key = $_SESSION['user_session_key'] ?? session_id();

    if ($protein_family === '' || $taxon_query === '') {
        $error_message = "Protein family and taxonomic group are required.";
    } else {
        if ($max_sequences === '' || !ctype_digit($max_sequences)) {
            $max_sequences = 20;
        } else {
            $max_sequences = (int)$max_sequences;
        }

        // Keep the query strict by design
        $ncbi_query = $protein_family . "[Protein Name] AND " . $taxon_query . "[Organism]";

        $sql = "INSERT INTO runs
                (user_forname, user_surname, user_session_key, protein_family, taxon_query, max_sequences, ncbi_query, run_type, status, sequence_count, notes)
                VALUES
                (:ufn, :usn, :usk, :pf, :tq, :mx, :nq, :rt, :st, :sc, :nt)";

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':ufn' => $user_forname,
                ':usn' => $user_surname,
                ':usk' => $user_session_key,
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

            // Automatically continue to fetch/import step
            header("Location: pw_import_proteins.php?run_id=" . urlencode($new_run_id));
            exit();

        } catch (PDOException $e) {
            $error_message = "Unable to create run: " . $e->getMessage();
        }
    }
}

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

    function showHelp(msg) {
        document.getElementById("helpmsg").innerHTML = msg;
    }

    function clearHelp() {
        document.getElementById("helpmsg").innerHTML = "";
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

echo <<<_MAIN1
<h1>New Analysis</h1>
<p>
Use this page to create a new protein analysis run.
After the run is created successfully, sequence retrieval and import from NCBI will start automatically.
</p>

<p>
This website uses a deliberately strict NCBI query format to keep retrieval focused and responsive.
For best results, enter a precise protein family name and a precise organism or taxonomic name.
</p>

<p id="helpmsg"></p>
_MAIN1;

if ($error_message !== '') {
    echo "<p><strong>Error:</strong> " . htmlspecialchars($error_message) . "</p>";
}

echo <<<_TAIL1
<form action="pw_p2.php" method="post" onsubmit="return validate(this)">
<pre>
Protein family     <input type="text" name="protein_family" size="40" value="{$protein_family}"
                     onfocus="showHelp('Enter a specific protein family name, for example glucose-6-phosphatase or ABC transporter')"
                     onblur="clearHelp()"/>
Taxonomic group    <input type="text" name="taxon_query" size="40" value="{$taxon_query}"
                     onfocus="showHelp('Enter a precise organism or taxonomic name. Strict queries work better with specific names such as Panthera leo rather than vague labels.')"
                     onblur="clearHelp()"/>
Max sequences      <input type="text" name="max_sequences" size="10" value="{$max_sequences}"
                     onfocus="showHelp('Enter the maximum number of sequences to retrieve. Smaller values improve speed.')"
                     onblur="clearHelp()"/>
Notes              <input type="text" name="notes" size="60" value="{$notes}"
                     onfocus="showHelp('Optional: add notes about this analysis run')"
                     onblur="clearHelp()"/>

                   <input type="submit" value="Create Run and Fetch Sequences"/>
</pre>
</form>

<p>
The strict query generated for this form will be:
<code>ProteinFamily[Protein Name] AND Taxon[Organism]</code>
</p>

</body>
</html>
_TAIL1;
?>