<?php
session_start();
require_once 'login.php';
include 'pw_redir.php';

echo <<<_HEAD1
<html>
<head>
    <title>Example Dataset</title>
</head>
<body>
_HEAD1;

include 'pw_menuf.php';

echo <<<_MAIN1
<h1>Example Dataset</h1>

<p>
This page provides access to the preloaded example dataset for the website.
The example dataset is based on <strong>glucose-6-phosphatase proteins from Aves</strong>.
</p>

<p>
It is intended to demonstrate the core functionality of the website before a user creates their own analysis run.
This includes the use of stored runs, linked protein sequences, and later will also include motif and sequence analysis outputs.
</p>

<h2>Dataset summary</h2>
<ul>
    <li>Protein family: glucose-6-phosphatase</li>
    <li>Taxonomic group: Aves</li>
    <li>Dataset type: preprocessed example run</li>
</ul>

<h2>View the example run</h2>

<p>
<a href="pw_vruns.php?run_id=1">Open the example dataset run</a>
</p>

<p>
You can use this run to explore how the website stores and displays protein-related results.
After that, you can create your own run using the New Analysis page.
</p>
_MAIN1;

echo <<<_TAIL1
</body>
</html>
_TAIL1;
?>