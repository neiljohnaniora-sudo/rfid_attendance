<?php
// check_live_scan.php
$file = 'latest_scan.json';

// Check ESP32 Heartbeat Ping
$is_online = false;
if (file_exists('esp32_ping.txt')) {
    $last_ping = (int)file_get_contents('esp32_ping.txt');
    // Kung naay ping sulod sa miaging 10 ka segundo, Online siya
    if (time() - $last_ping <= 10) { 
        $is_online = true;
    }
}

$response = ['timestamp' => 0, 'is_online' => $is_online];

if (file_exists($file)) {
    $data = json_decode(file_get_contents($file), true);
    if (is_array($data)) {
        $response = array_merge($response, $data);
    }
}

header('Content-Type: application/json');
echo json_encode($response);
?>