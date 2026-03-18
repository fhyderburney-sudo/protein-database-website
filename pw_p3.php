<?php
session_start();
include 'pw_redir.php';
require_once 'login.php';

echo<<<_HEAD1
<html>
<body>
_HEAD1;

include 'pw_menuf.php';

// Example database fields from the old compounds table
$dbfs = array("natm","ncar","nnit","noxy","nsul","ncycl","nhdon","nhacc","nrotb","mw","TPSA","XLogP");
$nms = array("n atoms","n carbons","n nitrogens","n oxygens","n sulphurs","n cycles","n H donors","n H acceptors","n rot bonds","mol wt","TPSA","XLogP");

echo <<<_MAIN1
    <pre>
This is the Statistics Page
    </pre>
_MAIN1;

// PDO connection
$charset = 'utf8mb4';
$dsn = "mysql:host=$db_hostname;dbname=$db_database;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_NUM,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $db_username, $db_password, $options);
} catch (PDOException $e) {
    die("Unable to connect to database: " . $e->getMessage());
}

if (isset($_POST['tgval']))
{
    $chosen = 0;
    $tgval = $_POST['tgval'];

    for ($j = 0; $j < sizeof($dbfs); ++$j) {
        if (strcmp($dbfs[$j], $tgval) == 0) {
            $chosen = $j;
        }
    }

    printf(" Statistics for %s (%s)<br />\n", $dbfs[$chosen], $nms[$chosen]);

    // Use only whitelisted field names from $dbfs
    $field = $dbfs[$chosen];
    $query = "SELECT AVG($field), STD($field) FROM Compounds";

    try {
        $stmt = $pdo->query($query);
        $row = $stmt->fetch();

        printf(" Average %f  Standard Dev %f <br />\n", $row[0], $row[1]);
    } catch (PDOException $e) {
        die("Unable to process query: " . $e->getMessage());
    }
}

echo '<form action="p3.php" method="post"><pre>';
for ($j = 0; $j < sizeof($dbfs); ++$j) {
    if ($j == 0) {
        printf(' %15s <input type="radio" name="tgval" value="%s" checked/>', $nms[$j], $dbfs[$j]);
    } else {
        printf(' %15s <input type="radio" name="tgval" value="%s"/>', $nms[$j], $dbfs[$j]);
    }
    echo "\n";
}
echo '<input type="submit" value="OK" />';
echo '</pre></form>';

echo <<<_TAIL1
</body>
</html>
_TAIL1;

?>