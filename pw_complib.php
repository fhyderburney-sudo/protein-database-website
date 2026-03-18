<?php
session_start();
require_once 'login.php';
echo<<<_HEAD1
<html>
<body>
_HEAD1;

// THE CONNECTION AND QUERY SECTIONS NEED TO BE MADE TO WORK FOR PHP 8 USING PDO... //
$charset = 'utfm8mb4'; // character encoding
$dsn = "mysql:host=$db_hostname; dbname=$db_database; charset=$charset";

$options = [
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $db_udername, $db_password, $options);
    
    $query = "SELECT * FROM Manufacturers";
    $stmt = $pdo->query($query);
    
    $data = $stmt->fetchAll();
    $rows = count($data);
    
    $mask = 0;
    for ($j = 0; $j < $rows; ++$j) {
      $mask = (2 * $mask) +1;
    }
    
    $_SESSION['supmask'] = $mask;
    
}

echo <<<_EOP
<script>
   function validate(form) {
   fail = ""
   if(form.fn.value =="") fail = "Must Give Forname "
   if(form.sn.value == "") fail += "Must Give Surname"
   if(fail =="") return true
       else {alert(fail); return false}
   }
</script>
<form action="ica_indexp.php" method="post" onSubmit="return validate(this)">
  <pre>
       First Name<input type="text" name="fn"/>
       Second Name <input type="text" name="sn"/>
                   <input type="submit" value="go" />
</pre></form>
_EOP;

echo <<<_TAIL1
</pre>
</body>
</html>
_TAIL1;

?>
