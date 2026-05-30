<?php
require 'connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['uid'])) {
    $uid = $_POST['uid'];
    $current_date = date("Y-m-d");
    $current_time = date("H:i:s");

    // KUHAON ANG SETTINGS NGA GIBUTANG SA ADMIN
    $time_settings = ['late_time' => '08:00', 'timeout_start' => '15:00'];
    if (file_exists('time_settings.json')) {
        $time_settings = array_merge($time_settings, json_decode(file_get_contents('time_settings.json'), true));
    }

    $action = "";
    $status_msg = "";
    $st_name = "Unknown";

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
            // I-CHECK KUNG SAYO PA BA KAAYO PARA MAG TIME OUT
            if (strtotime($current_time) < strtotime($time_settings['timeout_start'] . ':00')) {
                $action = "ERROR";
                $status_msg = "Too Early";
                echo "Warning: Too early to time out!";
            } else {
                // Update para sa Time-Out
                $update_sql = "UPDATE attendance_logs SET time_out = '$current_time' WHERE student_id = '$st_id' AND date = '$current_date' AND time_out IS NULL";
                if ($conn->query($update_sql) === TRUE) {
                    $action = "TIME OUT";
                    $status_msg = "Success";
                    echo "Success: Time-Out recorded for " . $st_name;
                }
            }
        } else {
            // Check kung naka time out na siya
            $check_done = $conn->query("SELECT id FROM attendance_logs WHERE student_id = '$st_id' AND date = '$current_date' AND time_out IS NOT NULL");
            if ($check_done->num_rows > 0) {
                $action = "DONE";
                $status_msg = "Already Out";
                echo "Warning: Already timed out for today!";
            } else {
                // Insert bag-ong Time-In
                $status = (strtotime($current_time) > strtotime($time_settings['late_time'] . ':00')) ? 'Late' : 'On Time';
                $insert_sql = "INSERT INTO attendance_logs (date, student_id, student_name, time_in, status) 
                               VALUES ('$current_date', '$st_id', '$st_name', '$current_time', '$status')";
                
                if ($conn->query($insert_sql) === TRUE) {
                    $action = "TIME IN";
                    $status_msg = $status;
                    echo "Success: Time-In recorded for " . $st_name;
                } else {
                    echo "Error: " . $conn->error;
                }
            }
        }
    } else {
        $action = "REGISTER";
        $status_msg = "Pending";
        echo "Error: RFID Card (" . $uid . ") not registered in students table.";
    }

    // I-UPDATE ANG LATEST SCAN JSON PARA MO-REFLECT DAYUN SA DASHBOARD UG TAP TO REGISTER
    $event_data = [
        'timestamp' => time(), 
        'rfid'      => $uid,
        'name'      => $st_name,
        'action'    => $action,
        'status'    => $status_msg
    ];
    file_put_contents('latest_scan.json', json_encode($event_data));
}
$conn->close();
?>