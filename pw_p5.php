<?php
session_start();

// Unset all session variables
$_SESSION = [];

// Destroy the session
session_destroy();

echo <<<_HEAD1
<html>
<head>
    <title>Exit</title>
</head>
<body>
_HEAD1;

echo <<<_MAIN1
<h1>You have been logged out</h1>

<p>
Your session has been successfully ended.
</p>

<p>
<a href="pw_complib.php">Return to login page</a>
</p>
_MAIN1;

echo <<<_TAIL1
</body>
</html>
_TAIL1;
?>
