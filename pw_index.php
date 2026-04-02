<?php
session_start();
require_once 'login.php';

if (isset($_POST['fn']) && isset($_POST['sn'])) {
    $_SESSION['forname'] = trim($_POST['fn']);
    $_SESSION['surname'] = trim($_POST['sn']);

    if (!isset($_SESSION['user_session_key']) || $_SESSION['user_session_key'] === '') {
        $_SESSION['user_session_key'] = session_id();
    }
}

if (!(isset($_SESSION['forname']) && isset($_SESSION['surname']) && isset($_SESSION['user_session_key']))) {
    header('Location: pw_complib.php');
    exit();
}

$forname = htmlspecialchars($_SESSION['forname']);
$surname = htmlspecialchars($_SESSION['surname']);

echo <<<_HEAD1
<html>
<head>
    <title>Protein Website Home</title>
    <link rel="stylesheet" type="text/css" href="pw_style.css">
</head>
<body>
_HEAD1;

echo "<div style='background-color:#dceffe; padding:12px; margin-bottom:20px; border:1px solid #c0d8ef;'>";
echo "<h1>Protein Sequence Analysis Website</h1>";
echo "<p class='section-note'>Retrieve, analyse, and revisit protein datasets across taxonomic groups.</p>";
echo "</div>";

include 'pw_menuf.php';

echo <<<_MAIN1
<h1>Welcome to the Protein Analysis Website</h1>

<p>Hello, $forname $surname.</p>

<p>
This website allows users to retrieve and analyse protein datasets defined by protein family
and taxonomic group. Saved runs can then be used for sequence retrieval, protein import,
alignment, conservation analysis, motif scanning, and export in structured formats.
</p>

<h2>What this website allows you to do</h2>

<ul>
    <li>Browse a shared example protein dataset</li>
    <li>Create a new protein analysis run for your current session</li>
    <li>Retrieve protein sequences from NCBI</li>
    <li>View saved runs and revisit previous analyses</li>
    <li>Generate alignments, conservation plots, and motif reports</li>
    <li>Export selected runs as JSON or XML</li>
    <li>Use AJAX-based viewing of selected run outputs</li>
</ul>

<h2>How the website is organised</h2>

<p>
The example dataset is shared and available to all users of the site.
Any new runs you create are associated with your current session, so that multiple users can use
the website independently at the same time.
</p>

<h2>Getting started</h2>

<p>
Use the menu above to move between pages. You can begin by exploring the Example Dataset page,
or create your own analysis using the New Analysis page.
</p>
_MAIN1;

echo <<<_TAIL1
</body>
</html>
_TAIL1;
?>