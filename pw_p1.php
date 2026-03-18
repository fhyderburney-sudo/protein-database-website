Use this as the page where user can choose protein family and taxonomic group 
<?php
session_start();
require_once 'login.php';
include 'pw_redir.php';

echo<<<_HEAD1
<html>
<body>
_HEAD1;

include 'menuf.php';

// THE CONNECTION AND QUERY SECTIONS NEED TO BE MADE TO WORK FOR PHP 8 USING PDO... //
$charset = 'utf8mb4'; 
$dsn = "mysql:host=$db_hostname; dbname=$db_database; charset=$charset";

$options = [
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  PDO::ATTR_EMULATE_PREPARES => false,   
];

try {
    $pdo = new PDO($dsn, $db_username, $db_password, $options);
    
    $query = "SELECT * FROM Manufacturers";
    $stmt = $pdo->query($query);
    $data = $stmt->fetchAll();

} catch (PDOException $e) {
    die("Unable to connect to database or process query: " . $e->getMessage());
}

$rows = count($data);

$smask = $_SESSION['supmask'];


//verified
for($j = 0 ; $j < $rows ; ++$j)
  {
    $row = $data[$j];
    $sid[$j] = $row['id'];//can change for column name
    $snm[$j] = $row['name'];//can change for column name 
    $sact[$j] = 0;
    $tvl = 1 << ($sid[$j] - 1);
    
    if($tvl == ($tvl & $smask)) {
      $sact[$j] = 1;
      }
  }
if(isset($_POST['supplier'])) 
{
   $supplier = $_POST['supplier'];
   $nele = sizeof($supplier);
     
   for($k = 0; $k <$rows; ++$k) {
       $sact[$k] = 0;
       
       for($j = 0 ; $j < $nele ; ++$j) {
         if(strcmp($supplier[$j],$snm[$k]) == 0) {
             $sact[$k] = 1;
         }
       }
   }  
   
   $smask = 0;
   
   for($j = 0 ; $j < $rows ; ++$j)
   {
       if($sact[$j] == 1) {
         $smask = $smask + (1 << ($sid[$j] - 1));
       }
   }
   
   $_SESSION['supmask'] = $smask;
   
}


echo 'Currently selected Suppliers: ';

for($j = 0 ; $j < $rows ; ++$j)
  {
    if($sact[$j] == 1) {
    echo $snm[$j] ;
    echo " ";
	}

}
    
echo  '<br><pre> <form action="p1.php" method="post">';

for($j = 0 ; $j < $rows ; ++$j)
  {
  echo $snm[$j];
	echo' <input type="checkbox" name="supplier[]" value="';
	echo $snm[$j];
  echo'"/>';
	echo"\n";
  }
  
echo <<<_TAIL1
 <input type="submit" value="OK" />
</pre></form>
</body>
</html>
_TAIL1;
?>
