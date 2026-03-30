<?php
session_start();
require_once 'login.php';
include 'pw_redir.php';

echo <<<_HEAD1
<html>
<head>
    <title>Statement of Credits</title>
    <link rel="stylesheet" type="text/css" href="pw_style.css">
</head>
<body>
_HEAD1;



include 'pw_menuf.php';

echo <<<_MAIN1
<h1>Statement of Credits</h1>

<p>
This page documents the sources of code, software, databases, and AI-assisted support used in the development of this website,
in accordance with the assessment requirements.
</p>

<h2>1. Code, software, and technical resources used</h2>

<ul>
    <li>
        <strong>PHP PDO</strong> was used for database connectivity and SQL execution throughout the website.
        This was based on class materials and the original practical skeleton code, which was adapted and rewritten for PHP 8 compatibility.
    </li>
    <li>
        <strong>MySQL</strong> was used as the backend relational database to store runs, imported protein records, and generated analysis file metadata.
    </li>
    <li>
        <strong>NCBI EDirect</strong> tools, including <code>esearch</code> and <code>efetch</code>, were used to retrieve protein sequence data from the NCBI protein database.
    </li>
    <li>
        <strong>Clustal Omega</strong> (<code>clustalo</code>) was used to generate multiple sequence alignments for retrieved protein datasets.
    </li>
    <li>
        <strong>EMBOSS plotcon</strong> was used to generate conservation plots from aligned protein sequences.
    </li>
    <li>
        <strong>EMBOSS patmatmotifs</strong> was used to scan protein sequences for PROSITE motif matches.
    </li>
    <li>
        <strong>Git and GitHub</strong> were used for version control, commit tracking, and backup of the website source code during development.
    </li>
</ul>

<h2>2. Sources of adapted code</h2>

<ul>
    <li>
        The starting point for some early files was the supplied course practical skeleton code,
        originally designed around an older MySQL/PHP workflow using chemistry-related example data.
        This code was substantially modified to:
        <ul>
            <li>replace deprecated <code>mysql_*</code> functions with PHP 8 PDO</li>
            <li>change the application from compound/supplier browsing to protein sequence analysis</li>
            <li>introduce new database tables and workflows for runs, proteins, alignments, conservation plots, and motif reports</li>
        </ul>
    </li>
    <li>
        Shell scripts for sequence retrieval, alignment, conservation analysis, and motif scanning were written specifically for this project,
        but were informed by the documented command-line usage of the installed bioinformatics tools.
    </li>
</ul>

<h2>3. AI tools used</h2>

<ul>
    <li>
        <strong>ChatGPT</strong> was used to assist with:
        <ul>
            <li>converting legacy PHP/MySQL code to PHP 8 PDO syntax</li>
            <li>debugging session and redirect issues in PHP</li>
            <li>planning the MySQL schema for runs, proteins, motif hits, and output files</li>
            <li>drafting and refining PHP pages such as the homepage, previous runs page, run details page, statistics page, plots page, and credits page</li>
            <li>drafting bash scripts for sequence retrieval, alignment, conservation analysis, FASTA parsing, and motif scanning</li>
            <li>debugging file path, permissions, and script-execution issues on the server</li>
            <li>structuring the website workflow so that saved runs, imported proteins, and generated outputs were connected logically</li>
        </ul>
    </li>
</ul>

<h2>4. External biological data resources</h2>

<ul>
    <li>
        <strong>NCBI Protein</strong> database was used as the source of retrieved protein sequence data.
    </li>
    <li>
        <strong>PROSITE</strong> motif definitions accessed through EMBOSS were used for motif/domain scanning.
    </li>
</ul>

<h2>5. Authorship statement</h2>

<p>
All code used in the final website was reviewed, tested, adapted, and integrated by the author.
Any externally sourced or AI-assisted material was modified as needed to fit the design and technical requirements of this specific project.
</p>
_MAIN1;

echo <<<_TAIL1
</body>
</html>
_TAIL1;
?>