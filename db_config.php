<?php
$servername = "sqlXXX.infinityfree.com"; // Tan-awa sa control panel
$username   = "if0_XXXXXX";              // Imong vPanel username
$password   = "imong_password";          // Imong account password
$dbname     = "if0_XXXXXX_attendance";   // Ang ngalan sa DB nga imong gihimo

// Create connection gamit ang MySQLi
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    // Kung naay error, ipakita kini
    die("Connection failed: " . $conn->connect_error);
}

// Optional: I-set ang charset para dili maguba ang mga special characters
$conn->set_charset("utf8");
?>