<?php
require 'connection.php';

/**
 * 1. SETTINGS & HEADERS
 */
date_default_timezone_set('Asia/Manila');

// ====================================================
// ESP32 HEARTBEAT PING 
// (Para ma-detect sa system kung online ang hardware)
// ====================================================
if (isset($_GET['ping'])) {
    file_put_contents('esp32_ping.txt', time());
    echo "PING_OK";
    exit();
}

function sendEmailNotification($to_email, $subject, $message) {
    $google_script_url = "https://script.google.com/macros/s/AKfycbzeBMiEsKBheK6eaEIdHAjOOWFwFsKa0qaOfJeoyYbKO4FrCzyRpdPzcRioS-oC1fxoUw/exec";
    
    // I-format ang parameters
    $query = http_build_query([
        'to' => $to_email,
        'subject' => $subject,
        'body' => $message,
        'name' => 'Smart Attendance'
    ]);
    
    $full_url = $google_script_url . "?" . $query;

    // Gamit ang cURL para mas "powerful" ug dili ma-block
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $full_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Importante para sa Google redirect
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Bypass SSL check para sa free hosting
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $result = curl_exec($ch);
    
    if ($result === false) {
        $curl_error = curl_error($ch);
        file_put_contents('email_error.txt', "[" . date('Y-m-d H:i:s') . "] cURL Error: " . $curl_error . "\n", FILE_APPEND);
    } else {
        // I-check kung success ba ang tubag gikan sa Google
        file_put_contents('email_error.txt', "[" . date('Y-m-d H:i:s') . "] Google Response: " . $result . "\n", FILE_APPEND);
    }
    
    curl_close($ch);
}
/**
 * 3. MAIN RFID SCAN LOGIC
 */
if (isset($_GET['rfid'])) {
    
    $rfid = $conn->real_escape_string($_GET['rfid']);
    
    // KUHAON ANG SETTINGS NGA GIBUTANG SA ADMIN
    $time_settings = ['late_time' => '08:00', 'timeout_start' => '15:00'];
    if (file_exists('time_settings.json')) {
        $time_settings = array_merge($time_settings, json_decode(file_get_contents('time_settings.json'), true));
    }
    
    // A. PANGITAON ANG ESTUDYANTE SA DATABASE
    $student_query = "SELECT name, guardian_email FROM students WHERE rfid = '$rfid' AND status = 'Active' LIMIT 1";
    $student_res = $conn->query($student_query);
    
    $action = "";
    $student_name = "Unknown";
    $status = "";

    if ($student_res && $student_res->num_rows > 0) {
        
        $student = $student_res->fetch_assoc();
        $student_name = $student['name'];
        $guardian_email = $student['guardian_email'];
        
        $today = date('Y-m-d');
        $current_time = date('H:i:s');
        $display_time = date('h:i A'); 
        
        // B. I-CHECK ANG ATTENDANCE LOGS KARONG ADLAWA
        // Pangitaon ang pinaka-latest nga log para ani nga RFID karong adlawa
        $log_query = "SELECT id, time_out FROM attendance_logs WHERE date = '$today' AND student_id = '$rfid' ORDER BY id DESC LIMIT 1";
        $log_res = $conn->query($log_query);

        if ($log_res && $log_res->num_rows > 0) {
            
            $log = $log_res->fetch_assoc();
            
            // I-check kung blangko pa ba ang Time Out
            if (empty($log['time_out']) || $log['time_out'] == '00:00:00') {
                
                // I-CHECK KUNG SAYO PA BA KAAYO PARA MAG TIME OUT
                if (strtotime($current_time) < strtotime($time_settings['timeout_start'] . ':00')) {
                    $action = "ERROR";
                    $status = "Too Early";
                    echo "WARNING: Too early to time out!";
                } else {
                    
                    // 👉 [ACTION: TIME OUT]
                    $log_id = $log['id'];
                    $update_sql = "UPDATE attendance_logs SET time_out = '$current_time' WHERE id = '$log_id'";
                    
                    if ($conn->query($update_sql) === TRUE) {
                        $action = "TIME OUT";
                        
                        // Trigger Email Notification para sa Guardian
                        if (!empty($guardian_email)) {
                            $subject = "Attendance Alert: TIME OUT - " . $student_name;
                            $msg = "Good day,\n\nNotice: Your child, $student_name, has safely logged OUT of the school premises today at $display_time.\n\nThank you,\nSmart Attendance System";
                            sendEmailNotification($guardian_email, $subject, $msg);
                        }
                        echo "SUCCESS: Time Out - " . $student_name;
                    } else {
                        file_put_contents('db_error.txt', "UPDATE ERR: " . $conn->error . "\n", FILE_APPEND);
                        echo "ERROR: Database Update Failed";
                        exit();
                    }
                }

            } else {
                // Nakahuman na og Time Out karong adlawa
                $action = "DONE";
                echo "WARNING: Already Timed Out for today!";
            }
            
        } else {
            
            // 👉 [ACTION: TIME IN]
            // I-set kung Late ba (after 8:00 AM) o On Time
            $status = (strtotime($current_time) > strtotime($time_settings['late_time'] . ':00')) ? 'Late' : 'On Time';
            
            $insert_sql = "INSERT INTO attendance_logs (date, student_id, student_name, time_in, status) 
                           VALUES ('$today', '$rfid', '$student_name', '$current_time', '$status')";
            
            if ($conn->query($insert_sql) === TRUE) {
                $action = "TIME IN";
                
                // Trigger Email Notification para sa Guardian
                if (!empty($guardian_email)) {
                    $subject = "Attendance Alert: TIME IN - " . $student_name;
                    $msg = "Good day,\n\nNotice: Your child, $student_name, has safely logged IN to the school premises today at $display_time.\nStatus: $status\n\nThank you,\nSmart Attendance System";
                    sendEmailNotification($guardian_email, $subject, $msg);
                }
                echo "SUCCESS: Time In - " . $student_name;
            } else {
                file_put_contents('db_error.txt', "INSERT ERR: " . $conn->error . "\n", FILE_APPEND);
                echo "ERROR: Database Insert Failed";
                exit();
            }
        }
        
    } else {
        // 👉 [ACTION: REGISTRATION]
        // Wala sa database ang RFID, pasabot bag-o ni nga card.
        $action = "REGISTER";
        $status = "Pending";
        
        // I-save ang bag-ong ID sa usa ka temporary text file aron makuha sa imong Add Student Dashboard
        file_put_contents('temp_rfid.txt', $rfid);
        
        // I-echo ang word nga REGISTERED aron ma-basa sa ESP32 ug ma-display sa LCD
        echo "REGISTERED: New Card Scanned";
    }

    /**
     * 4. UPDATE REAL-TIME DATA (JSON)
     * Para sa AJAX refresh sa Dashboard/Records
     */
    $event_data = [
        'timestamp' => time(), 
        'rfid'      => $rfid,
        'name'      => $student_name,
        'action'    => $action,
        'status'    => $status
    ];
    
    // I-save ang scan event sa JSON file
    file_put_contents('latest_scan.json', json_encode($event_data));

} else {
    // Kung gi-abli ang endpoint nga walay RFID parameter
    echo "Smart Attendance Endpoint: Waiting for RFID data...";
}

// Tapos na ang script
?>