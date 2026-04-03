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

echo "<div style='background-color:#dceffe; padding:12px; margin-bottom:20px; border:1px solid #c0d8ef;'>";
echo "<h1>Protein Sequence Analysis Website</h1>";
echo "<p class='section-note'>Retrieve, analyse, and revisit protein datasets across taxonomic groups.</p>";
echo "</div>";

include 'pw_menuf.php';

echo <<<_MAIN1
<h1>Statement of Credits</h1>

<p>
This page documents the sources of code, software, databases, lecture material, and AI-assisted support used in the development of this website,
in accordance with the assessment requirements.
</p>

<h2>1. Code, software, and technical resources used</h2>

<ul>
    <li>
        <strong>PHP PDO</strong> was used for database connectivity and prepared SQL execution throughout the website.
        The PDO connection pattern and DSN structure were adapted from the PHP Delusions PDO tutorial:
        <a href="https://phpdelusions.net/pdo#dsn" target="_blank">phpdelusions.net/pdo#dsn</a>.
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
        <strong>JavaScript and AJAX</strong> were used to load run data asynchronously on the Plots page and to draw an in-browser sequence-length chart using the HTML canvas element.
    </li>
    <li>
        <strong>Git and GitHub</strong> were used for version control, commit tracking, and backup of the website source code during development.
    </li>
</ul>

<h2>2. Lecture material and class resources used</h2>

<ul>
    <li>
        Lecture and practical material from the <strong>Introduction to Website and Database Design (IWD2)</strong> course was used throughout the project as guidance for website structure, usability, styling, database querying, JavaScript, AJAX, XML/JSON export, and session-based workflows.
    </li>
    <li>
        The supplied course practical skeleton code provided an initial starting point for page structure and PHP/MySQL interaction patterns.
        This material was then substantially adapted for a new biological application focused on protein sequence analysis.
    </li>
    <li>
        Class examples relating to forms, menus, validation, DOM interaction, AJAX, structured data exchange, and general website usability informed the final implementation of several pages.
    </li>
</ul>

<h2>3. Sources of adapted code</h2>

<ul>
    <li>
        Some early files were initially based on the supplied course skeleton code,
        originally designed around an older MySQL/PHP workflow using chemistry-related example data.
        This code was substantially modified to:
        <ul>
            <li>replace deprecated <code>mysql_*</code> functions with PHP 8 PDO</li>
            <li>change the application from compound/supplier browsing to protein sequence analysis</li>
            <li>introduce new database tables and workflows for runs, proteins, alignments, conservation plots, motif reports, and exported file records</li>
            <li>support session-based access control for user runs and a separate shared example dataset</li>
        </ul>
    </li>
    <li>
        Shell scripts for sequence retrieval, alignment, conservation analysis, FASTA parsing, and motif scanning were written specifically for this project,
        but were informed by the documented command-line usage of the installed bioinformatics tools.
    </li>
    <li>
        Page logic for asynchronous JSON loading, interactive chart drawing, and export handling was developed for this project using course concepts from the JavaScript, XML/JSON, and AJAX teaching material.
    </li>
</ul>

<h2>4. Git repository</h2>

<p>
The source code for this project was managed using Git and stored in a GitHub repository during development.
</p>

<p>
Repository link:
<a href="https://github.com/fhyderburney-sudo/protein-database-website" target="_blank">https://github.com/fhyderburney-sudo/protein-database-website</a>
</p>

<h2>5. AI tools used</h2>

<ul>
    <li>
        <strong>ChatGPT</strong> was used to assist with:
        <ul>
            <li>debugging session, redirect, and permission issues in PHP and shell scripts</li>
            <li>planning the MySQL schema for runs, proteins, and output files</li>
            <li>drafting and refining PHP pages such as the homepage, previous runs page, run details page, statistics page, plots page, help page, and credits page</li>
            <li>drafting bash scripts for sequence retrieval, alignment, conservation analysis, FASTA parsing, and motif scanning</li>
            <li>debugging file path, permissions, and script-execution issues on the server</li>
            <li>structuring the workflow so that saved runs, imported proteins, and generated outputs were connected logically</li>
            <li>improving the usability and clarity of page content, navigation, and output presentation</li>
        </ul>
    </li>
</ul>

<h2>6. External biological data resources</h2>

<ul>
    <li>
        <strong>NCBI Protein</strong> database was used as the source of retrieved protein sequence data.
    </li>
    <li>
        <strong>PROSITE</strong> motif definitions accessed through EMBOSS were used for motif and domain scanning.
    </li>
</ul>

<h2>7. Authorship statement</h2>

<p>
All code used in the final website was reviewed, tested, adapted, and integrated by the author.
Any externally sourced, lecture-derived, or AI-assisted material was modified as needed to fit the design and technical requirements of this specific biological website project.
</p>
_MAIN1;

echo <<<_TAIL1
</body>
</html>
_TAIL1;
?>