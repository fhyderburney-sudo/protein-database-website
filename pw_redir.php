<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!(isset($_SESSION['forname']) && isset($_SESSION['surname']))) {
    header('Location: pw_complib.php');
    exit();
}
?>
