<?php
session_start();
require_once 'login.php';

if (isset($_POST['fn']) && isset($_POST['sn'])) {
    $_SESSION['forname'] = $_POST['fn'];
    $_SESSION['surname'] = $_POST['sn'];
}

if (!(isset($_SESSION['forname']) && isset($_SESSION['surname']))) {
    header('Location: pw_complib.php');
    exit();
}

$forname = htmlspecialchars($_SESSION['forname']);
$surname = htmlspecialchars($_SESSION['surname']);

echo <<<_HEAD1
<html>
<head>
    <title>Protein Website Home</title>
</head>
<body>
_HEAD1;

include 'pw_menuf.php';

echo <<<_MAIN1
<h1>Welcome to the Protein Analysis Website</h1>

<p>Hello, $forname $surname.</p>

<p>
This website is designed to let users explore and analyse a protein dataset through a simple web interface.
It will support searching, browsing, and summarising protein-related information in a structured way.
</p>

<h2>What this website allows you to do</h2>

<ul>
    <li>Browse an example protein dataset</li>
    <li>Search for proteins and related sequence information</li>
    <li>View summary statistics from the dataset</li>
    <li>Generate plots and simple analyses</li>
    <li>Access help and background information about the website</li>
</ul>

<h2>Getting started</h2>

<p>
Use the menu above to move between pages. You can begin by exploring the example dataset,
then move on to search, statistics, and analysis pages.
</p>
_MAIN1;

echo <<<_TAIL1
</body>
</html>
_TAIL1;
?>