<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check required session variables
if (
    !isset($_SESSION['forname']) ||
    !isset($_SESSION['surname']) ||
    !isset($_SESSION['user_session_key']) ||
    $_SESSION['forname'] === '' ||
    $_SESSION['surname'] === '' ||
    $_SESSION['user_session_key'] === ''
) {
    header('Location: pw_complib.php');
    exit();
}
?>