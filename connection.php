<?php
//sa infinityfree
$servername = "sql105.infinityfree.com"; // Ilisi sa imong Host gikan sa AwardSpace
$username = "if0_41841593"; // Ilisi sa imong Database Username
$password = "2213attend"; // Ilisi sa password nga imong gi-set sa Step 3
$dbname = "if0_41841593_schoolattendance"; // Ilisi sa imong Database Name

// Maghimo ug connection
$conn = new mysqli($servername, $username, $password, $dbname);

// I-check kung ni-connect ba
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>