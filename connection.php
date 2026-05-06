<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$servername = "mainline.proxy.rlwy.net";
$username = "root";
$password = "imong_railway_password_diri"; // I-paste ang tinuod nga password gikan sa Railway
$dbname = "railway";
$port = 57930;

// Paghimo ug connection apil ang port
$conn = new mysqli($servername, $username, $password, $dbname, $port);

// I-check kung naay error
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}
?>