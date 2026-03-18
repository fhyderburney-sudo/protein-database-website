<?php
session_start();
require_once 'login.php';
include 'pw_redir.php';
echo<<<_HEAD1
<html>
<body>
_HEAD1;
include 'pw_menuf.php';
// THE CONNECTION AND QUERY SECTIONS NEED TO BE MADE TO WORK FOR PHP 8 USING PDO... //
$charset = 'utfm8mb4'; 
$dsn = "mysql:host=$db_hostname; dbname=$db_database; charset=$charset";

$options = [
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  PDO::ATTR_EMULATE_PREPARES => false,   
];

try {
    $pdo = new PDO($dsn, $db_udername, $db_password, $options);
} catch (PDOException $e) {
    die("Unable to connect to database or process query: " . $e->getMessage());
}

$query = "SELECT * FROM Manufacturers";
try {
    $stmt = $pdo->query($query);
    $data = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Unable to process query: " . $e->getMessage());
}

$rows = count($data);
$smask = $_SESSION['supmask'] ?? 0;
$firstmn = false;
$mansel = "(";

for ($j = 0; $j < $rows; ++$j) {
    $row = $data[$j];
    $sid[$j] = $row[0];
    $snm[$j] = $row[1];
    $sact[$j] = 0;
    $tvl = 1 << ($sid[$j] - 1);

    if ($tvl == ($tvl & $smask)) {
        $sact[$j] = 1;
        if ($firstmn) $mansel .= " OR ";
        $firstmn = true;
        $mansel .= " (ManuID = " . (int)$sid[$j] . ")";
    }
}
$mansel .= ")";

$setpar = isset($_POST['natmax']);

echo <<<_MAIN1
<pre>
This is the catalogue retrieval Page
</pre>
_MAIN1;

if ($setpar) {
    $firstsl = false;
    $compsel = "SELECT catn FROM Compounds WHERE (";

    if (($_POST['natmax'] != "") && ($_POST['natmin'] != "")) {
        $compsel .= "(natm > " . (int)$_POST['natmin'] . " AND natm < " . (int)$_POST['natmax'] . ")";
        $firstsl = true;
    }

    if (($_POST['ncrmax'] != "") && ($_POST['ncrmin'] != "")) {
        if ($firstsl) $compsel .= " AND ";
        $compsel .= "(ncar > " . (int)$_POST['ncrmin'] . " AND ncar < " . (int)$_POST['ncrmax'] . ")";
        $firstsl = true;
    }

    if (($_POST['nntmax'] != "") && ($_POST['nntmin'] != "")) {
        if ($firstsl) $compsel .= " AND ";
        $compsel .= "(nnit > " . (int)$_POST['nntmin'] . " AND nnit < " . (int)$_POST['nntmax'] . ")";
        $firstsl = true;
    }

    if (($_POST['noxmax'] != "") && ($_POST['noxmin'] != "")) {
        if ($firstsl) $compsel .= " AND ";
        $compsel .= "(noxy > " . (int)$_POST['noxmin'] . " AND noxy < " . (int)$_POST['noxmax'] . ")";
        $firstsl = true;
    }

    echo "<pre>";

    if ($firstsl) {
        $compsel .= ") AND " . $mansel;
        echo $compsel;
        echo "\n";

        try {
            $stmt = $pdo->query($compsel);
            $results = $stmt->fetchAll();
            $rows = count($results);

            if ($rows > 100) {
                echo "Too many results $rows Max is 100\n";
            } else {
                for ($j = 0; $j < $rows; ++$j) {
                    echo $results[$j][0], "\n";
                }
            }
        } catch (PDOException $e) {
            die("Unable to process query: " . $e->getMessage());
        }
    } else {
        echo "No Query Given\n";
    }

    echo "</pre>";
}

echo <<<_TAIL1
<form action="p2.php" method="post"><pre>
       Max Atoms      <input type="text" name="natmax"/>    Min Atoms    <input type="text" name="natmin"/>
       Max Carbons    <input type="text" name="ncrmax"/>    Min Carbons  <input type="text" name="ncrmin"/>
       Max Nitrogens  <input type="text" name="nntmax"/>    Min Nitrogens<input type="text" name="nntmin"/>
       Max Oxygens    <input type="text" name="noxmax"/>    Min Oxygens  <input type="text" name="noxmin"/>
                   <input type="submit" value="list" />
</pre></form>

</body>
</html>
_TAIL1;
?>