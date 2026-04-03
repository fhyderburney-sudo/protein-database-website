<?php
session_start();
require_once 'login.php';

// Optional: clear any previous session values when returning to the login page
$_SESSION['forname'] = $_SESSION['forname'] ?? '';
$_SESSION['surname'] = $_SESSION['surname'] ?? '';

echo <<<_HEAD1
<html>
<head>
    <title>Login</title>
    <link rel="stylesheet" type="text/css" href="pw_style.css">
    <script type="text/javascript">
    function showHelp(msg) {
        document.getElementById("helpmsg").innerHTML = msg;
    }

    function clearHelp() {
        document.getElementById("helpmsg").innerHTML = "";
    }

    function validate(form) {
        let fail = "";

        if (form.fn.value.trim() === "") {
            fail += "Must give first name.\\n";
        }

        if (form.sn.value.trim() === "") {
            fail += "Must give surname.\\n";
        }

        if (fail === "") {
            return true;
        } else {
            alert(fail);
            return false;
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

echo <<<_MAIN1
<h1>Login</h1>

<p>
Welcome to the Protein Sequence Analysis Website.
Please enter your name below to begin a session.
</p>

<p id="helpmsg"></p>

<form action="pw_index.php" method="post" onsubmit="return validate(this)">
<pre>
First Name   <input type="text" name="fn"
               onfocus="showHelp('Enter your first name to begin your session')"
               onblur="clearHelp()"/>

Second Name  <input type="text" name="sn"
               onfocus="showHelp('Enter your surname to begin your session')"
               onblur="clearHelp()"/>

             <input type="submit" value="Go" />
</pre>
</form>

<p>
After logging in, you will be able to explore the shared example dataset or create your own analysis runs.
</p>
_MAIN1;

echo <<<_TAIL1
</body>
</html>
_TAIL1;
?>