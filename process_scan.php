<?php
require 'connection.php'; 
date_default_timezone_set('Asia/Manila');

if (isset($_POST['rfid'])) {
    $rfid = $conn->real_escape_string($_POST['rfid']);
    $today = date('Y-m-d');
    $current_time = date('H:i:s');

    // Pangitaon ang student gamit ang RFID
    $student_query = $conn->query("SELECT * FROM students WHERE rfid = '$rfid' AND status = 'Active'");
    
    if ($student_query->num_rows > 0) {
        $student = $student_query->fetch_assoc();
        $student_name = $student['name'];

        // I-check kung naka Time-In na ba siya karong adlawa
        $log_query = $conn->query("SELECT * FROM attendance_logs WHERE student_id = '$rfid' AND date = '$today'");

        if ($log_query->num_rows > 0) {
            $log = $log_query->fetch_assoc();
            
            // Kung wala pay Time-Out (NULL o '00:00:00'), i-update ang row para butangan ug Time-Out
            if (empty($log['time_out']) || $log['time_out'] == '00:00:00') {
                $conn->query("UPDATE attendance_logs SET time_out = '$current_time' WHERE id = " . $log['id']);
                echo json_encode(['success' => true, 'message' => "Time Out Success:<br>$student_name"]);
                exit();
            } else {
                echo json_encode(['success' => false, 'message' => "$student_name<br>already logged out!"]);
                exit();
            }
        } else {
            // Kung wala pay record karong adlawa, mag insert ug Time-In
            // Ma-Late kung lapas na sa 8:00 AM
            $status = ($current_time > '08:00:00') ? 'Late' : 'On Time';
            
            $conn->query("INSERT INTO attendance_logs (date, student_id, student_name, time_in, status) 
                          VALUES ('$today', '$rfid', '$student_name', '$current_time', '$status')");
            echo json_encode(['success' => true, 'message' => "Time In Success:<br>$student_name"]);
            exit();
        }
    } else {
        echo json_encode(['success' => false, 'message' => "Unregistered RFID!"]);
        exit();
    }
} else {
    echo json_encode(['success' => false, 'message' => "No RFID received."]);
    exit();
}
?>