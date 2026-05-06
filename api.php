<?php
require 'connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['uid'])) {
    $uid = $_POST['uid'];
    $current_date = date("Y-m-d");
    $current_time = date("H:i:s");

    // 1. Match ang UID sa 'rfid' column sa 'students' table
    $student_query = "SELECT id, name FROM students WHERE rfid = '$uid' LIMIT 1";
    $result = $conn->query($student_query);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $st_id = $row['id']; // ID gikan sa students table
        $st_name = $row['name']; // 'name' ang column sa SQL nimo

        // 2. Check attendance_logs para sa Time-In/Time-Out logic
        $check_log = "SELECT id FROM attendance_logs WHERE student_id = '$st_id' AND date = '$current_date' AND time_out IS NULL";
        $log_result = $conn->query($check_log);

        if ($log_result->num_rows > 0) {
            // Update para sa Time-Out
            $update_sql = "UPDATE attendance_logs SET time_out = '$current_time' WHERE student_id = '$st_id' AND date = '$current_date' AND time_out IS NULL";
            if ($conn->query($update_sql) === TRUE) {
                echo "Success: Time-Out recorded for " . $st_name;
            }
        } else {
            // Insert bag-ong Time-In
            $status = "Present"; 
            $insert_sql = "INSERT INTO attendance_logs (date, student_id, student_name, time_in, status) 
                           VALUES ('$current_date', '$st_id', '$st_name', '$current_time', '$status')";
            
            if ($conn->query($insert_sql) === TRUE) {
                echo "Success: Time-In recorded for " . $st_name;
            } else {
                echo "Error: " . $conn->error;
            }
        }
    } else {
        echo "Error: RFID Card (" . $uid . ") not registered in students table.";
    }
}
$conn->close();
?>