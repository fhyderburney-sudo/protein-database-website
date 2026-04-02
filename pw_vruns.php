<?php
session_start();
require_once 'login.php';
include 'pw_redir.php';

echo <<<_HEAD1
<html>
<head>
    <title>Run Details</title>
    <link rel="stylesheet" type="text/css" href="pw_style.css">
    <script type="text/javascript">
    function toggleSection(id) {
        var sec = document.getElementById(id);
        if (sec.style.display === "none") {
            sec.style.display = "block";
        } else {
            sec.style.display = "none";
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

$run_id = $_GET['run_id'] ?? '';
$user_session_key = $_SESSION['user_session_key'] ?? session_id();

if ($run_id === '' || !ctype_digit($run_id)) {
    die("Invalid run ID.");
}

$sql = "SELECT run_id, user_forname, user_surname, user_session_key, protein_family, taxon_query,
               max_sequences, ncbi_query, run_type, status, sequence_count, created_at, notes
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

// Access control: only current session or shared example runs
if ($run['run_type'] !== 'example' && $run['user_session_key'] !== $user_session_key) {
    die("You do not have permission to view this run.");
}

echo <<<_MAIN1
<h1>Run Details</h1>
<p>This page shows the details and outputs of a selected analysis run.</p>
_MAIN1;

echo "<p class='section-note'>Click section headings below to expand or collapse results.</p>";

// Detect key files
$fasta_abs = __DIR__ . "/runs/run_" . $run_id . "/sequences.fasta";
$alignment_abs = __DIR__ . "/runs/run_" . $run_id . "/alignment.aln";
$motif_abs = __DIR__ . "/runs/run_" . $run_id . "/motifs.txt";

$has_fasta = file_exists($fasta_abs) && filesize($fasta_abs) > 0;
$has_alignment = file_exists($alignment_abs) && filesize($alignment_abs) > 0;
$has_motif = file_exists($motif_abs) && filesize($motif_abs) > 0;

// Find conservation plot robustly
$conservation_candidates = [
    "runs/run_" . $run_id . "/conservation.1.png",
    "runs/run_" . $run_id . "/conservation.png",
    "runs/run_" . $run_id . "/conservation.png.1.png"
];

$conservation_rel = null;
$conservation_abs = null;

foreach ($conservation_candidates as $candidate) {
    $abs = __DIR__ . "/" . $candidate;
    if (file_exists($abs) && filesize($abs) > 0) {
        $conservation_rel = $candidate;
        $conservation_abs = $abs;
        break;
    }
}

// Run metadata
echo '<h2 onclick="toggleSection(\'metadata_section\')" style="cursor:pointer;">Run Metadata (click to expand/collapse)</h2>';
echo '<div id="metadata_section">';

echo "<table border='1' cellpadding='6' cellspacing='0'>";
echo "<tr><th>Field</th><th>Value</th></tr>";
echo "<tr><td>Run ID</td><td>" . htmlspecialchars($run['run_id']) . "</td></tr>";
echo "<tr><td>User</td><td>" . htmlspecialchars($run['user_forname'] . ' ' . $run['user_surname']) . "</td></tr>";
echo "<tr><td>Protein Family</td><td>" . htmlspecialchars($run['protein_family']) . "</td></tr>";
echo "<tr><td>Taxonomic Group</td><td>" . htmlspecialchars($run['taxon_query']) . "</td></tr>";
echo "<tr><td>Max Sequences</td><td>" . htmlspecialchars($run['max_sequences']) . "</td></tr>";
echo "<tr><td>NCBI Query</td><td>" . htmlspecialchars($run['ncbi_query']) . "</td></tr>";
echo "<tr><td>Run Type</td><td>" . htmlspecialchars($run['run_type']) . "</td></tr>";
echo "<tr><td>Status</td><td>" . htmlspecialchars($run['status']) . "</td></tr>";
echo "<tr><td>Sequence Count</td><td>" . htmlspecialchars($run['sequence_count']) . "</td></tr>";
echo "<tr><td>Created At</td><td>" . htmlspecialchars($run['created_at']) . "</td></tr>";
echo "<tr><td>Notes</td><td>" . htmlspecialchars($run['notes']) . "</td></tr>";
echo "</table>";

echo '</div>';

// Export data
echo "<h2>Export Data</h2>";
echo "<p><a href='pw_run_json.php?run_id=" . htmlspecialchars($run_id) . "'>Download run as JSON</a></p>";
echo "<p><a href='pw_run_xml.php?run_id=" . htmlspecialchars($run_id) . "'>Download run as XML</a></p>";

// Analysis actions
echo "<h2>Analysis Actions</h2>";

echo "<p><a href='pw_import_proteins.php?run_id=" . htmlspecialchars($run['run_id']) . "'>Fetch and import sequences for this run</a></p>";

if ($has_fasta) {
    echo "<p><a href='pw_run_alignment.php?run_id=" . htmlspecialchars($run['run_id']) . "'>Run alignment for this dataset</a></p>";
} else {
    echo "<p>Run alignment for this dataset <span class='section-note'>(available after sequences have been imported)</span></p>";
}

if ($has_alignment) {
    echo "<p><a href='pw_run_conservation.php?run_id=" . htmlspecialchars($run['run_id']) . "'>Run conservation analysis</a></p>";
} else {
    echo "<p><a href='pw_run_conservation.php?run_id=" . htmlspecialchars($run['run_id']) . "'>Run conservation analysis</a> <span class='section-note'>(alignment will be created automatically if missing)</span></p>";
}

if ($has_fasta) {
    echo "<p><a href='pw_run_motifs.php?run_id=" . htmlspecialchars($run['run_id']) . "'>Run PROSITE motif scan for this dataset</a></p>";
} else {
    echo "<p>Run PROSITE motif scan for this dataset <span class='section-note'>(available after sequences have been imported)</span></p>";
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
    die("Unable to retrieve proteins: " . $e->getMessage());
}

echo '<h2 onclick="toggleSection(\'proteins_section\')" style="cursor:pointer;">Protein Sequences in This Run (click to expand/collapse)</h2>';
echo '<div id="proteins_section">';

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

echo '</div>';

// Motif preview
echo '<h2 onclick="toggleSection(\'motif_section\')" style="cursor:pointer;">Motif Report Preview (click to expand/collapse)</h2>';
echo '<div id="motif_section" style="display:none;">';

if ($has_motif) {
    $preview = file($motif_abs);
    $preview = array_slice($preview, 0, 40);
    echo "<pre>" . htmlspecialchars(implode("", $preview)) . "</pre>";
} else {
    echo "<p>No motif report preview available yet.</p>";
}

echo '</div>';

// Conservation plot
echo '<h2 onclick="toggleSection(\'conservation_section\')" style="cursor:pointer;">Conservation Plot (click to expand/collapse)</h2>';
echo '<div id="conservation_section">';

if ($conservation_abs !== null) {
    echo "<img src='" . htmlspecialchars($conservation_rel) . "' width='700' alt='Conservation plot'>";
} else {
    echo "<p>No conservation plot available yet.</p>";
    if (!$has_alignment) {
        echo "<p class='section-note'>This is usually because no alignment has been generated yet. Running conservation analysis will now try to create the alignment automatically first.</p>";
    }
}

echo '</div>';

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
    die("Unable to retrieve run files: " . $e->getMessage());
}

echo '<h2 onclick="toggleSection(\'files_section\')" style="cursor:pointer;">Analysis Output Files (click to expand/collapse)</h2>';
echo '<div id="files_section" style="display:none;">';

if (count($run_files) === 0) {
    echo "<p>No output files have been recorded for this run yet.</p>";
} else {
    echo "<table border='1' cellpadding='6' cellspacing='0'>";
    echo "<tr>";
    echo "<th>File Type</th>";
    echo "<th>Description</th>";
    echo "<th>Created At</th>";
    echo "<th>Open</th>";
    echo "</tr>";

    foreach ($run_files as $file) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($file['file_type']) . "</td>";
        echo "<td>" . htmlspecialchars($file['description']) . "</td>";
        echo "<td>" . htmlspecialchars($file['created_at']) . "</td>";
        echo "<td><a href='" . htmlspecialchars($file['file_path']) . "'>View file</a></td>";
        echo "</tr>";
    }

    echo "</table>";
}

echo '</div>';

echo "<p><a href='pw_pruns.php'>Back to Previous Runs</a></p>";

echo <<<_TAIL1
</body>
</html>
_TAIL1;
?>