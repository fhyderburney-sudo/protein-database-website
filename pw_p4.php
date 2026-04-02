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
    function escapeHtml(text) {
        const div = document.createElement("div");
        div.textContent = text;
        return div.innerHTML;
    }

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
                            outputDiv.innerHTML = "<p><strong>Error:</strong> " + escapeHtml(data.error) + "</p>";
                            return;
                        }

                        var html = "";

                        html += "<h3>AJAX Run Summary</h3>";
                        html += "<table border='1' cellpadding='6' cellspacing='0'>";
                        html += "<tr><th>Field</th><th>Value</th></tr>";
                        html += "<tr><td>Run ID</td><td>" + escapeHtml(String(data.run.run_id)) + "</td></tr>";
                        html += "<tr><td>Protein Family</td><td>" + escapeHtml(String(data.run.protein_family)) + "</td></tr>";
                        html += "<tr><td>Taxonomic Group</td><td>" + escapeHtml(String(data.run.taxon_query)) + "</td></tr>";
                        html += "<tr><td>Run Type</td><td>" + escapeHtml(String(data.run.run_type)) + "</td></tr>";
                        html += "<tr><td>Status</td><td>" + escapeHtml(String(data.run.status)) + "</td></tr>";
                        html += "<tr><td>Sequence Count</td><td>" + escapeHtml(String(data.run.sequence_count)) + "</td></tr>";
                        html += "<tr><td>Created At</td><td>" + escapeHtml(String(data.run.created_at)) + "</td></tr>";
                        html += "</table>";

                        html += "<h3>Interactive Sequence Length Chart</h3>";
                        if (data.proteins.length === 0) {
                            html += "<p>No proteins stored for this run, so no chart can be drawn.</p>";
                        } else {
                            html += "<p>This chart shows sequence length for proteins in the selected run.</p>";
                            html += "<canvas id='lengthChart' width='900' height='420' style='border:1px solid #cccccc; background:#ffffff;'></canvas>";
                            html += "<p class='section-note'>Bars represent individual imported proteins. Labels show accession and organism.</p>";
                        }

                        html += "<h3>Proteins</h3>";
                        if (data.proteins.length === 0) {
                            html += "<p>No proteins stored for this run.</p>";
                        } else {
                            html += "<table border='1' cellpadding='6' cellspacing='0'>";
                            html += "<tr><th>Accession</th><th>Protein Name</th><th>Organism</th><th>Length</th></tr>";
                            for (var i = 0; i < data.proteins.length; i++) {
                                html += "<tr>";
                                html += "<td>" + escapeHtml(String(data.proteins[i].accession)) + "</td>";
                                html += "<td>" + escapeHtml(String(data.proteins[i].protein_name)) + "</td>";
                                html += "<td>" + escapeHtml(String(data.proteins[i].organism)) + "</td>";
                                html += "<td>" + escapeHtml(String(data.proteins[i].seq_length)) + "</td>";
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
                                html += "<td>" + escapeHtml(String(data.output_files[j].file_type)) + "</td>";
                                html += "<td>" + escapeHtml(String(data.output_files[j].description)) + "</td>";
                                html += "<td>" + escapeHtml(String(data.output_files[j].created_at)) + "</td>";
                                html += "<td><a href='" + encodeURI(data.output_files[j].file_path) + "' target='_blank'>View file</a></td>";
                                html += "</tr>";
                            }
                            html += "</table>";
                        }

                        var plotPath = "runs/run_" + data.run.run_id + "/conservation.1.png";
                        html += "<h3>Conservation Plot</h3>";
                        html += "<img src='" + plotPath + "' alt='Conservation plot' width='700' onerror=\"this.outerHTML='<p>No conservation plot available for this run.</p>'\">";

                        html += "<p><a href='pw_vruns.php?run_id=" + encodeURIComponent(data.run.run_id) + "'>Open full run details page</a></p>";

                        outputDiv.innerHTML = html;

                        if (data.proteins.length > 0) {
                            drawLengthChart(data.proteins);
                        }

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

    function drawLengthChart(proteins) {
        var canvas = document.getElementById("lengthChart");
        if (!canvas) {
            return;
        }

        var ctx = canvas.getContext("2d");
        var width = canvas.width;
        var height = canvas.height;

        ctx.clearRect(0, 0, width, height);

        var marginLeft = 70;
        var marginRight = 20;
        var marginTop = 30;
        var marginBottom = 140;

        var chartWidth = width - marginLeft - marginRight;
        var chartHeight = height - marginTop - marginBottom;

        var maxLen = 0;
        for (var i = 0; i < proteins.length; i++) {
            var len = parseInt(proteins[i].seq_length);
            if (len > maxLen) {
                maxLen = len;
            }
        }

        if (maxLen === 0) {
            return;
        }

        // Axes
        ctx.beginPath();
        ctx.moveTo(marginLeft, marginTop);
        ctx.lineTo(marginLeft, marginTop + chartHeight);
        ctx.lineTo(marginLeft + chartWidth, marginTop + chartHeight);
        ctx.strokeStyle = "#222222";
        ctx.lineWidth = 1;
        ctx.stroke();

        // Y-axis ticks
        ctx.fillStyle = "#222222";
        ctx.font = "12px Arial";
        var tickCount = 5;
        for (var t = 0; t <= tickCount; t++) {
            var value = Math.round((maxLen / tickCount) * t);
            var y = marginTop + chartHeight - (chartHeight * t / tickCount);

            ctx.beginPath();
            ctx.moveTo(marginLeft - 5, y);
            ctx.lineTo(marginLeft, y);
            ctx.stroke();

            ctx.fillText(String(value), 10, y + 4);
        }

        // Title
        ctx.font = "16px Arial";
        ctx.fillText("Protein sequence length by imported protein", marginLeft, 18);

        // Bars
        var n = proteins.length;
        var gap = 10;
        var barWidth = Math.max(8, (chartWidth - gap * (n - 1)) / n);

        for (var j = 0; j < n; j++) {
            var p = proteins[j];
            var seqLen = parseInt(p.seq_length);
            var barHeight = (seqLen / maxLen) * chartHeight;

            var x = marginLeft + j * (barWidth + gap);
            var yTop = marginTop + chartHeight - barHeight;

            ctx.fillStyle = "#6fa8dc";
            ctx.fillRect(x, yTop, barWidth, barHeight);

            ctx.strokeStyle = "#3d6b99";
            ctx.strokeRect(x, yTop, barWidth, barHeight);

            // Rotated x labels
            var label = p.accession + " | " + p.organism;
            ctx.save();
            ctx.translate(x + barWidth / 2, marginTop + chartHeight + 10);
            ctx.rotate(-Math.PI / 3);
            ctx.fillStyle = "#222222";
            ctx.font = "11px Arial";
            ctx.fillText(label, 0, 0);
            ctx.restore();
        }

        // Y axis label
        ctx.save();
        ctx.translate(18, marginTop + chartHeight / 2);
        ctx.rotate(-Math.PI / 2);
        ctx.font = "13px Arial";
        ctx.fillStyle = "#222222";
        ctx.fillText("Sequence length (aa)", 0, 0);
        ctx.restore();
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

// Retrieve recent runs for AJAX shortcuts (current session + example runs only)
$recent_sql = "SELECT run_id, protein_family, taxon_query, run_type, created_at
               FROM runs
               WHERE user_session_key = :usk
                  OR run_type = 'example'
               ORDER BY created_at DESC, run_id DESC
               LIMIT 8";

try {
    $recent_stmt = $pdo->prepare($recent_sql);
    $recent_stmt->execute([':usk' => $user_session_key]);
    $recent_runs = $recent_stmt->fetchAll();
} catch (PDOException $e) {
    die("Unable to retrieve recent runs: " . $e->getMessage());
}

echo "<h2>AJAX Run Viewer and Interactive Chart</h2>";
echo "<p>Quickly retrieve and visualise run data from the JSON export without reloading the page.</p>";

echo "<p>";
echo "Run ID: ";
echo "<input type='text' id='run_id' name='run_id' />";
echo " <button type='button' onclick='loadRunData()'>Load Run Data</button>";
echo "</p>";

echo "<h3>Recent Runs</h3>";

if (count($recent_runs) === 0) {
    echo "<p>No recent runs available for this session.</p>";
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

// Retrieve conservation plot outputs (current session + example runs only)
$plot_sql = "SELECT r.run_id, r.protein_family, r.taxon_query, r.run_type,
                    rf.file_path, rf.description, rf.created_at
             FROM run_files rf
             JOIN runs r ON rf.run_id = r.run_id
             WHERE rf.file_type = 'conservation_plot'
               AND (r.user_session_key = :usk OR r.run_type = 'example')
             ORDER BY rf.created_at DESC, rf.file_id DESC";

try {
    $plot_stmt = $pdo->prepare($plot_sql);
    $plot_stmt->execute([':usk' => $user_session_key]);
    $plots = $plot_stmt->fetchAll();
} catch (PDOException $e) {
    die("Unable to retrieve conservation plots: " . $e->getMessage());
}

echo "<h2>Available Conservation Plots</h2>";

if (count($plots) === 0) {
    echo "<p>No conservation plots have been recorded yet for this session.</p>";
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

// Retrieve all output files (current session + example runs only)
$file_sql = "SELECT r.run_id, r.protein_family, r.taxon_query, r.run_type,
                    rf.file_type, rf.file_path, rf.description, rf.created_at
             FROM run_files rf
             JOIN runs r ON rf.run_id = r.run_id
             WHERE r.user_session_key = :usk
                OR r.run_type = 'example'
             ORDER BY rf.created_at DESC, rf.file_id DESC";

try {
    $file_stmt = $pdo->prepare($file_sql);
    $file_stmt->execute([':usk' => $user_session_key]);
    $files = $file_stmt->fetchAll();
} catch (PDOException $e) {
    die("Unable to retrieve output files: " . $e->getMessage());
}

echo "<h2>All Recorded Analysis Outputs</h2>";

if (count($files) === 0) {
    echo "<p>No plot or analysis output files have been recorded yet for this session.</p>";
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