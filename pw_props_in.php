<?php
session_start();
require_once 'login.php';
include 'pw_redir.php';

echo <<<_HEAD1
<html>
<head>
    <title>Sequence Tools</title>
    <link rel="stylesheet" type="text/css" href="pw_style.css">
    <script type="text/javascript">
    function validateRunForm(form) {
        let fail = "";

        if (form.run_id.value.trim() === "") {
            fail += "Run ID is required.\\n";
        } else if (isNaN(form.run_id.value)) {
            fail += "Run ID must be numeric.\\n";
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
<h1>Sequence Tools</h1>

<p>
This page provides access to the main sequence analysis tools available for stored runs.
Use it as a control panel for sequence retrieval, alignment, conservation analysis, and motif scanning.
</p>

<h2>Available tools</h2>
<ul>
    <li><strong>Fetch and import sequences:</strong> retrieve protein FASTA records from NCBI and import them into the database</li>
    <li><strong>Run alignment:</strong> create a multiple sequence alignment using Clustal Omega</li>
    <li><strong>Run conservation analysis:</strong> generate an EMBOSS plotcon conservation plot from the alignment</li>
    <li><strong>Run motif scan:</strong> scan the sequences against PROSITE motifs using EMBOSS patmatmotifs</li>
</ul>

<p id="helpmsg"></p>

<h2>Select a run</h2>
<form action="pw_props_in.php" method="get" onsubmit="return validateRunForm(this)">
<pre>
Run ID    <input type="text" name="run_id"
           onfocus="showHelp('Enter the ID of a saved run from your session or the shared example run')"
           onblur="clearHelp()"/>
          <input type="submit" value="Load Tools for Run"/>
</pre>
</form>
_MAIN1;

$run_id = $_GET['run_id'] ?? '';

if ($run_id !== '') {
    if (!ctype_digit($run_id)) {
        echo "<p><strong>Error:</strong> Run ID must be numeric.</p>";
    } else {
        $run_sql = "SELECT run_id, user_forname, user_surname, user_session_key,
                           protein_family, taxon_query, run_type, status, sequence_count, created_at
                    FROM runs
                    WHERE run_id = :run_id";

        try {
            $run_stmt = $pdo->prepare($run_sql);
            $run_stmt->execute([':run_id' => $run_id]);
            $run = $run_stmt->fetch();
        } catch (PDOException $e) {
            die("Unable to retrieve run: " . $e->getMessage());
        }

        if (!$run) {
            echo "<p><strong>Error:</strong> Run not found.</p>";
        } elseif ($run['run_type'] !== 'example' && $run['user_session_key'] !== $user_session_key) {
            echo "<p><strong>Error:</strong> You do not have permission to access this run.</p>";
        } else {
            echo "<h2>Selected Run Summary</h2>";
            echo "<table border='1' cellpadding='6' cellspacing='0'>";
            echo "<tr><th>Field</th><th>Value</th></tr>";
            echo "<tr><td>Run ID</td><td>" . htmlspecialchars($run['run_id']) . "</td></tr>";
            echo "<tr><td>User</td><td>" . htmlspecialchars($run['user_forname'] . ' ' . $run['user_surname']) . "</td></tr>";
            echo "<tr><td>Protein Family</td><td>" . htmlspecialchars($run['protein_family']) . "</td></tr>";
            echo "<tr><td>Taxonomic Group</td><td>" . htmlspecialchars($run['taxon_query']) . "</td></tr>";
            echo "<tr><td>Run Type</td><td>" . htmlspecialchars($run['run_type']) . "</td></tr>";
            echo "<tr><td>Status</td><td>" . htmlspecialchars($run['status']) . "</td></tr>";
            echo "<tr><td>Sequence Count</td><td>" . htmlspecialchars($run['sequence_count']) . "</td></tr>";
            echo "<tr><td>Created At</td><td>" . htmlspecialchars($run['created_at']) . "</td></tr>";
            echo "</table>";

            echo "<h2>Run Tools</h2>";
            echo "<p><a href='pw_fetch_import.php?run_id=" . htmlspecialchars($run['run_id']) . "'>Fetch and import sequences for this run</a></p>";
            echo "<p><a href='pw_run_alignment.php?run_id=" . htmlspecialchars($run['run_id']) . "'>Run alignment for this dataset</a></p>";
            echo "<p><a href='pw_run_conservation.php?run_id=" . htmlspecialchars($run['run_id']) . "'>Run conservation analysis</a></p>";
            echo "<p><a href='pw_run_motifs.php?run_id=" . htmlspecialchars($run['run_id']) . "'>Run PROSITE motif scan</a></p>";
            echo "<p><a href='pw_vruns.php?run_id=" . htmlspecialchars($run['run_id']) . "'>Open full run details page</a></p>";

            $protein_sql = "SELECT protein_id, accession, protein_name, organism, seq_length
                            FROM proteins
                            WHERE run_id = :run_id
                            ORDER BY organism, protein_name
                            LIMIT 15";

            try {
                $protein_stmt = $pdo->prepare($protein_sql);
                $protein_stmt->execute([':run_id' => $run_id]);
                $proteins = $protein_stmt->fetchAll();
            } catch (PDOException $e) {
                die("Unable to retrieve proteins: " . $e->getMessage());
            }

            echo "<h2>Quick Protein Preview</h2>";

            if (count($proteins) === 0) {
                echo "<p>No proteins have been imported for this run yet.</p>";
            } else {
                echo "<p>Showing up to 15 imported proteins for this run.</p>";
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

            $file_sql = "SELECT file_type, file_path, description, created_at
                         FROM run_files
                         WHERE run_id = :run_id
                         ORDER BY created_at DESC, file_id DESC
                         LIMIT 10";

            try {
                $file_stmt = $pdo->prepare($file_sql);
                $file_stmt->execute([':run_id' => $run_id]);
                $files = $file_stmt->fetchAll();
            } catch (PDOException $e) {
                die("Unable to retrieve run files: " . $e->getMessage());
            }

            echo "<h2>Recent Output Files</h2>";

            if (count($files) === 0) {
                echo "<p>No output files are currently recorded for this run.</p>";
            } else {
                echo "<table border='1' cellpadding='6' cellspacing='0'>";
                echo "<tr>";
                echo "<th>File Type</th>";
                echo "<th>Description</th>";
                echo "<th>Created At</th>";
                echo "<th>Open</th>";
                echo "</tr>";

                foreach ($files as $file) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($file['file_type']) . "</td>";
                    echo "<td>" . htmlspecialchars($file['description']) . "</td>";
                    echo "<td>" . htmlspecialchars($file['created_at']) . "</td>";
                    echo "<td><a href='" . htmlspecialchars($file['file_path']) . "'>View file</a></td>";
                    echo "</tr>";
                }

                echo "</table>";
            }
        }
    }
}

echo <<<_TAIL1
</body>
</html>
_TAIL1;
?>
