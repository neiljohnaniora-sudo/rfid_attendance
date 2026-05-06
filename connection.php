<?php

$servername = "ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);mainline.proxy.rlwy.net";

$username = "root";
$password = "bIadgPoRRsOhYqzVKiXrDIONEROJgJnm"; // I-paste ang tinuod nga password gikan sa Railway
$dbname = "railway";
$port = 57930;

// Paghimo ug connection apil ang port
$conn = new mysqli($servername, $username, $password, $dbname, $port);

// I-check kung naay error
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}
?>