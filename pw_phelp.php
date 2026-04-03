<?php
session_start();
require_once 'login.php';
include 'pw_redir.php';

echo <<<_HEAD1
<html>
<head>
    <title>Help and About</title>
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
<h1>Help and About</h1>

<p>
This website was developed to support protein sequence retrieval and analysis in a biological context.
It allows users to define a protein family and a taxonomic group, retrieve relevant protein sequences,
and explore the resulting data through alignments, conservation analysis, and motif scanning.
</p>

<h2>How to use this website</h2>

<ul>
    <li>
        <strong>Home:</strong> overview of the website and its purpose.
    </li>
    <li>
        <strong>Example Dataset:</strong> opens a preloaded example dataset based on glucose-6-phosphatase proteins from Aves.
    </li>
    <li>
        <strong>New Analysis:</strong> create a new run by entering a protein family and a taxonomic group.
    </li>
    <li>
        <strong>Previous Runs:</strong> browse previously saved runs and reopen their results.
    </li>
    <li>
        <strong>Statistics:</strong> view summary statistics for stored protein sequence data.
    </li>
    <li>
        <strong>Outputs:</strong> quick view runs, view available visual outputs, and sequence length barchart.
    </li>
    <li>
        <strong>Sequence Tools:</strong> reserved for sequence-related actions and extensions.
    </li>
    <li>
        <strong>Credits:</strong> lists software, code sources, and AI-assisted contributions used during development.
    </li>
</ul>

<h2>Example dataset</h2>

<p>
The example dataset is based on <strong>glucose-6-phosphatase proteins from Aves</strong>.
It is included so that users can explore the website functionality before running their own analyses.
The example run demonstrates sequence storage, viewing of run details, protein retrieval, alignment, conservation analysis, and motif scanning.
</p>

<h2>Analysis outputs</h2>

<ul>
    <li>
        <strong>Multiple sequence alignment:</strong> generated using Clustal Omega to compare related protein sequences across species.
    </li>
    <li>
        <strong>Conservation plot:</strong> generated from the alignment to show how strongly conserved different alignment regions are.
        Highly conserved regions may indicate structural or functional importance.
    </li>
    <li>
        <strong>PROSITE motif scan:</strong> generated using EMBOSS patmatmotifs to identify known protein motifs or domains.
        Some datasets may return no motif hits, which is still a valid result.
    </li>
</ul>

<h2>Notes and limitations</h2>

<ul>
    <li>
        Sequence retrieval depends on the query terms entered by the user and on the results available in the NCBI protein database.
    </li>
    <li>
        The number and quality of retrieved sequences can vary depending on taxonomic group and protein family.
    </li>
    <li>
        Some runs may generate no motif hits, depending on the proteins retrieved and the motifs represented in PROSITE.
    </li>
    <li>
        Analyses such as alignment and conservation plotting rely on generated intermediate files stored for each run.
    </li>
</ul>

<h2>About this project</h2>

<p>
This website was developed as part of a coursework project focused on website design and implementation in a biological database context.
Its aim is to demonstrate how a web interface, a relational database, and command-line bioinformatics tools can be combined to support protein sequence analysis.
</p>
_MAIN1;

echo <<<_TAIL1
</body>
</html>
_TAIL1;
?>