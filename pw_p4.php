<?php
session_start();
require_once 'login.php';
include 'pw_redir.php';

echo <<<_HEAD1
<html>
<head>
    <title>Plots</title>
    <link rel="stylesheet" type="text/css" href="pw_style.css">
    <script type="text/javascript">
    function loadRunData() {
        var runId = document.getElementById("run_id").value.trim();
        var outputDiv = document.getElementById("ajax_output");

        if (runId === "") {
            outputDiv.innerHTML = "<p>Please enter a run ID.</p>";
            return;
        }

        var xhr = new XMLHttpRequest();
        xhr.open("GET", "pw_run_json.php?run_id=" + encodeURIComponent(runId), true);

        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                if (xhr.status === 200) {
                    try {
                        var data = JSON.parse(xhr.responseText);

                        if (data.error) {
                            outputDiv.innerHTML = "<p><strong>Error:</strong> " + data.error + "</p>";
                            return;
                        }

                        var html = "";

                        html += "<h3>AJAX Run Summary</h3>";
                        html += "<table border='1' cellpadding='6' cellspacing='0'>";
                        html += "<tr><th>Field</th><th>Value</th></tr>";
                        html += "<tr><td>Run ID</td><td>" + data.run.run_id + "</td></tr>";
                        html += "<tr><td>Protein Family</td><td>" + data.run.protein_family + "</td></tr>";
                        html += "<tr><td>Taxonomic Group</td><td>" + data.run.taxon_query + "</td></tr>";
                        html += "<tr><td>Run Type</td><td>" + data.run.run_type + "</td></tr>";
                        html += "<tr><td>Status</td><td>" + data.run.status + "</td></tr>";
                        html += "<tr><td>Sequence Count</td><td>" + data.run.sequence_count + "</td></tr>";
                        html += "<tr><td>Created At</td><td>" + data.run.created_at + "</td></tr>";
                        html += "</table>";

                        html += "<h3>Proteins</h3>";
                        if (data.proteins.length === 0) {
                            html += "<p>No proteins stored for this run.</p>";
                        } else {
                            html += "<table border='1' cellpadding='6' cellspacing='0'>";
                            html += "<tr><th>Accession</th><th>Protein Name</th><th>Organism</th><th>Length</th></tr>";
                            for (var i = 0; i < data.proteins.length; i++) {
                                html += "<tr>";
                                html += "<td>" + data.proteins[i].accession + "</td>";
                                html += "<td>" + data.proteins[i].protein_name + "</td>";
                                html += "<td>" + data.proteins[i].organism + "</td>";
                                html += "<td>" + data.proteins[i].seq_length + "</td>";
                                html += "</tr>";
                            }
                            html += "</table>";
                        }

                        html += "<h3>Output Files</h3>";
                        if (data.output_files.length === 0) {
                            html += "<p>No output files recorded for this run.</p>";
                        } else {
                            html += "<table border='1' cellpadding='6' cellspacing='0'>";
                            html += "<tr><th>Type</th><th>Description</th><th>Created</th><th>Open</th></tr>";
                            for (var j = 0; j < data.output_files.length; j++) {
                                html += "<tr>";
                                html += "<td>" + data.output_files[j].file_type + "</td>";
                                html += "<td>" + data.output_files[j].description + "</td>";
                                html += "<td>" + data.output_files[j].created_at + "</td>";
                                html += "<td><a href='" + data.output_files[j].file_path + "' target='_blank'>View file</a></td>";
                                html += "</tr>";
                            }
                            html += "</table>";
                        }

                        var plotPath = "runs/run_" + data.run.run_id + "/conservation.1.png";
                        html += "<h3>Conservation Plot</h3>";
                        html += "<img src='" + plotPath + "' alt='Conservation plot' width='700' onerror=\"this.outerHTML='<p>No conservation plot available for this run.</p>'\">";

                        html += "<p><a href='pw_vruns.php?run_id=" + data.run.run_id + "'>Open full run details page</a></p>";

                        outputDiv.innerHTML = html;

                    } catch (err) {
                        outputDiv.innerHTML = "<p><strong>Error:</strong> Could not parse JSON response.</p>";
                    }
                } else {
                    document.getElementById("ajax_output").innerHTML = "<p><strong>Error:</strong> Request failed.</p>";
                }
            }
        };

        outputDiv.innerHTML = "<p>Loading run data...</p>";
        xhr.send();
    }

    function useRunId(runId) {
        document.getElementById("run_id").value = runId;
        loadRunData();
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

echo <<<_MAIN1
<h1>Plots and Visual Outputs</h1>
<p>
This page shows graphical and file-based outputs generated during sequence analysis,
including conservation plots and linked analysis files.
</p>

<p>
Conservation plots are generated from multiple sequence alignments and help visualise
which regions of a protein family are relatively conserved across the selected species.
</p>
_MAIN1;

// Retrieve recent runs for AJAX shortcuts
$recent_sql = "SELECT run_id, protein_family, taxon_query, run_type, created_at
               FROM runs
               ORDER BY created_at DESC, run_id DESC
               LIMIT 8";

try {
    $recent_stmt = $pdo->query($recent_sql);
    $recent_runs = $recent_stmt->fetchAll();
} catch (PDOException $e) {
    die("Unable to retrieve recent runs: " . $e->getMessage());
}

echo "<h2>AJAX Run Viewer</h2>";
echo "<p>Quickly retrieve and view runs data from JSON export.</p>";

echo "<p>";
echo "Run ID: ";
echo "<input type='text' id='run_id' name='run_id' />";
echo " <button type='button' onclick='loadRunData()'>Load Run Data</button>";
echo "</p>";

echo "<h3>Recent Runs</h3>";

if (count($recent_runs) === 0) {
    echo "<p>No recent runs available.</p>";
} else {
    echo "<table border='1' cellpadding='6' cellspacing='0'>";
    echo "<tr>";
    echo "<th>Run ID</th>";
    echo "<th>Protein Family</th>";
    echo "<th>Taxonomic Group</th>";
    echo "<th>Run Type</th>";
    echo "<th>Created At</th>";
    echo "<th>Load via AJAX</th>";
    echo "</tr>";

    foreach ($recent_runs as $recent) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($recent['run_id']) . "</td>";
        echo "<td>" . htmlspecialchars($recent['protein_family']) . "</td>";
        echo "<td>" . htmlspecialchars($recent['taxon_query']) . "</td>";
        echo "<td>" . htmlspecialchars($recent['run_type']) . "</td>";
        echo "<td>" . htmlspecialchars($recent['created_at']) . "</td>";
        echo "<td><button type='button' onclick='useRunId(" . htmlspecialchars($recent['run_id']) . ")'>Load Run " . htmlspecialchars($recent['run_id']) . "</button></td>";
        echo "</tr>";
    }

    echo "</table>";
}

echo "<div id='ajax_output'><p>No run loaded yet.</p></div>";

// Example dataset conservation plot
$example_plot = __DIR__ . "/runs/run_1/conservation.1.png";

echo "<h2>Example Dataset Conservation Plot</h2>";

if (file_exists($example_plot) && filesize($example_plot) > 0) {
    echo "<p>The image below shows the conservation profile for the example dataset alignment.</p>";
    echo "<img src='runs/run_1/conservation.1.png' width='700' alt='Example conservation plot'>";
    echo "<p><a href='pw_vruns.php?run_id=1'>View example run details</a></p>";
} else {
    echo "<p>No example conservation plot is currently available.</p>";
}

// Retrieve conservation plot outputs
$plot_sql = "SELECT r.run_id, r.protein_family, r.taxon_query, r.run_type,
                    rf.file_path, rf.description, rf.created_at
             FROM run_files rf
             JOIN runs r ON rf.run_id = r.run_id
             WHERE rf.file_type = 'conservation_plot'
             ORDER BY rf.created_at DESC, rf.file_id DESC";

try {
    $plot_stmt = $pdo->query($plot_sql);
    $plots = $plot_stmt->fetchAll();
} catch (PDOException $e) {
    die("Unable to retrieve conservation plots: " . $e->getMessage());
}

echo "<h2>Available Conservation Plots</h2>";

if (count($plots) === 0) {
    echo "<p>No conservation plots have been recorded yet.</p>";
} else {
    echo "<table border='1' cellpadding='6' cellspacing='0'>";
    echo "<tr>";
    echo "<th>Run ID</th>";
    echo "<th>Protein Family</th>";
    echo "<th>Taxonomic Group</th>";
    echo "<th>Run Type</th>";
    echo "<th>Description</th>";
    echo "<th>Created At</th>";
    echo "<th>Open Plot</th>";
    echo "<th>Run Details</th>";
    echo "</tr>";

    foreach ($plots as $row) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['run_id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['protein_family']) . "</td>";
        echo "<td>" . htmlspecialchars($row['taxon_query']) . "</td>";
        echo "<td>" . htmlspecialchars($row['run_type']) . "</td>";
        echo "<td>" . htmlspecialchars($row['description']) . "</td>";
        echo "<td>" . htmlspecialchars($row['created_at']) . "</td>";
        echo "<td><a href='" . htmlspecialchars($row['file_path']) . "'>View plot</a></td>";
        echo "<td><a href='pw_vruns.php?run_id=" . htmlspecialchars($row['run_id']) . "'>View run</a></td>";
        echo "</tr>";
    }

    echo "</table>";
}

// Retrieve all output files
$file_sql = "SELECT r.run_id, r.protein_family, r.taxon_query, r.run_type,
                    rf.file_type, rf.file_path, rf.description, rf.created_at
             FROM run_files rf
             JOIN runs r ON rf.run_id = r.run_id
             ORDER BY rf.created_at DESC, rf.file_id DESC";

try {
    $file_stmt = $pdo->query($file_sql);
    $files = $file_stmt->fetchAll();
} catch (PDOException $e) {
    die("Unable to retrieve output files: " . $e->getMessage());
}

echo "<h2>All Recorded Analysis Outputs</h2>";

if (count($files) === 0) {
    echo "<p>No plot or analysis output files have been recorded yet.</p>";
} else {
    echo "<table border='1' cellpadding='6' cellspacing='0'>";
    echo "<tr>";
    echo "<th>Run ID</th>";
    echo "<th>Protein Family</th>";
    echo "<th>Taxonomic Group</th>";
    echo "<th>Run Type</th>";
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
        echo "<td>" . htmlspecialchars($row['run_type']) . "</td>";
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