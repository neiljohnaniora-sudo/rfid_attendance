<?php
require 'connection.php'; 
date_default_timezone_set('Asia/Manila');

if (isset($_POST['rfid'])) {
    $rfid = $conn->real_escape_string($_POST['rfid']);
    $today = date('Y-m-d');
    $current_time = date('H:i:s');

    // KUHAON ANG SETTINGS NGA GIBUTANG SA ADMIN
    $time_settings = ['late_time' => '08:00', 'timeout_start' => '15:00'];
    if (file_exists('time_settings.json')) {
        $time_settings = array_merge($time_settings, json_decode(file_get_contents('time_settings.json'), true));
    }

    $action = "";
    $status_msg = "";
    $student_name = "Unknown";

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
                
                if (strtotime($current_time) < strtotime($time_settings['timeout_start'] . ':00')) {
                    $action = "ERROR";
                    file_put_contents('latest_scan.json', json_encode(['timestamp'=>time(),'rfid'=>$rfid,'name'=>$student_name,'action'=>$action,'status'=>"Too Early"]));
                    echo json_encode(['success' => false, 'message' => "Too Early to Time Out!<br>Wait until " . date("h:i A", strtotime($time_settings['timeout_start']))]);
                    exit();
                }

                $conn->query("UPDATE attendance_logs SET time_out = '$current_time' WHERE id = " . $log['id']);
                
                $action = "TIME OUT";
                file_put_contents('latest_scan.json', json_encode(['timestamp'=>time(),'rfid'=>$rfid,'name'=>$student_name,'action'=>$action,'status'=>"Success"]));
                
                echo json_encode(['success' => true, 'message' => "Time Out Success:<br>$student_name"]);
                exit();
            } else {
                $action = "DONE";
                file_put_contents('latest_scan.json', json_encode(['timestamp'=>time(),'rfid'=>$rfid,'name'=>$student_name,'action'=>$action,'status'=>"Already Out"]));
                echo json_encode(['success' => false, 'message' => "$student_name<br>already logged out!"]);
                exit();
            }
        } else {
            // Kung wala pay record karong adlawa, mag insert ug Time-In
            // Ma-Late kung lapas na sa 8:00 AM
            $status = (strtotime($current_time) > strtotime($time_settings['late_time'] . ':00')) ? 'Late' : 'On Time';
            
            $conn->query("INSERT INTO attendance_logs (date, student_id, student_name, time_in, status) 
                          VALUES ('$today', '$rfid', '$student_name', '$current_time', '$status')");
            
            $action = "TIME IN";
            file_put_contents('latest_scan.json', json_encode(['timestamp'=>time(),'rfid'=>$rfid,'name'=>$student_name,'action'=>$action,'status'=>$status]));
            
            echo json_encode(['success' => true, 'message' => "Time In Success:<br>$student_name"]);
            exit();
        }
    } else {
        $action = "REGISTER";
        file_put_contents('latest_scan.json', json_encode(['timestamp'=>time(),'rfid'=>$rfid,'name'=>"Unknown",'action'=>$action,'status'=>"Pending"]));
        echo json_encode(['success' => false, 'message' => "Unregistered RFID!"]);
        exit();
    }
} else {
    echo json_encode(['success' => false, 'message' => "No RFID received."]);
    exit();
}
?>