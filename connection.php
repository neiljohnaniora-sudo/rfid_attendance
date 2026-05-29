<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$servername = getenv('MYSQLHOST') ?: "zephyr.proxy.rlwy.net";
$username   = getenv('MYSQLUSER') ?: "root";
$password   = getenv('MYSQLPASSWORD') ?: "kjQlEasVoQranHLlCyIowNZcfJmCoHEJ";
$dbname     = getenv('MYSQLDATABASE') ?: "railway";
$port       = getenv('MYSQLPORT') ?: 28873;

// Paghimo ug connection gamit ang mga variables
$conn = new mysqli($servername, $username, $password, $dbname, $port);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>