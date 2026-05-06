<?php
session_start();
session_unset(); // Tangtangon ang tanan session variables
session_destroy(); // Gub-on ang session record sa server
header("Location: index.php"); // I-balik ka sa login page
exit();
?>