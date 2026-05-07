<?php
session_start();

if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

require 'connection.php'; 

$grade_counts = [
    'Grade 1' => 0, 'Grade 2' => 0, 'Grade 3' => 0,
    'Grade 4' => 0, 'Grade 5' => 0, 'Grade 6' => 0
];

$sql = "SELECT grade, COUNT(*) as total FROM students GROUP BY grade";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $grade_name = $row['grade'];
        if(array_key_exists($grade_name, $grade_counts)) {
            $grade_counts[$grade_name] = $row['total'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Smart RFID Attendance</title>
    <?php include 'sidebar.php'; ?>
    <link rel="manifest" href="manifest.json">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <link rel="apple-touch-icon" href="icon-192.png">
    <script>
      // I-register ang Service Worker
      if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
          navigator.serviceWorker.register('sw.js')
            .then(reg => console.log('Service worker registered', reg))
            .catch(err => console.log('Service worker not registered', err));
        });
      }
    </script>
    <style>
        @keyframes pulse {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.05); opacity: 0.9; }
            100% { transform: scale(1); opacity: 1; }
        }
        @media (max-width: 768px) {
            .top-header {
                flex-direction: column;
                align-items: flex-start !important;
                gap: 15px;
            }
            .live-scanner-container {
                flex-direction: column;
                text-align: center;
                gap: 20px;
            }
        }
    </style>
</head>
<body style="background-color: #f1f5f9; margin: 0; font-family: 'Inter', sans-serif;">

    <div class="main-content" style="padding: 20px; position: relative; z-index: 1;">
        <div class="top-header" style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 30px;">
            <div class="header-title">
                <h1 style="color: #1e293b; font-size: 26px; font-weight: 800; margin: 0;">Attendance Overview</h1>
                <div id="esp-status-badge" style="background-color: #f1f5f9; color: #64748b; padding: 8px 15px; border-radius: 20px; font-size: 13px; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; margin-top: 10px; transition: 0.3s;">
                    <i id="esp-status-icon" class="fa-solid fa-circle" style="font-size: 8px;"></i> <span id="esp-status-text">Checking ESP32...</span>
                </div>
            </div>
            <div style="text-align: right;">
                <div id="live-date" style="color: #64748b; font-size: 14px; font-weight: 500;">Loading...</div>
                <div id="live-time" style="color: #1e3a8a; font-size: 24px; font-weight: 800;">00:00:00 AM</div>
            </div>
        </div>
        
        <div class="live-scanner-container" style="background: #ffffff; border-radius: 15px; padding: 35px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); margin-bottom: 35px; display: flex; align-items: center; justify-content: space-between; border-left: 6px solid #3b82f6;">
            <div style="flex: 1;">
                <h2 style="color: #1e293b; margin-bottom: 10px; font-size: 22px;"><i class="fa-solid fa-wifi" style="color: #3b82f6; margin-right: 10px;"></i> Live Scanner</h2>
                <p style="color: #64748b; font-size: 15px;"></p>
            </div>
            <div style="flex: 1; text-align: center;">
                
                <div id="live-status-box" style="width: 100%; max-width: 350px; margin: 0 auto;">
                    <div style="padding: 25px; border: 2px dashed #cbd5e1; border-radius: 15px; background: #f8fafc;">
                        <i class="fa-solid fa-wifi fa-fade" style="font-size: 30px; color: #3b82f6; margin-bottom: 15px;"></i>
                        <h3 style="color: #64748b; margin: 0; font-size: 16px;">Waiting for ESP32 Scan...</h3>
                    </div>
                </div>

            </div>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px;">
            <?php
            // FETCH ROLE AND GRADE OF LOGGED-IN USER
            $admin_role = isset($_SESSION['admin_role']) ? $_SESSION['admin_role'] : '';
            $assigned_grade = isset($_SESSION['assigned_grade']) ? $_SESSION['assigned_grade'] : '';

            $cards = [
                'Grade 1' => ['icon' => 'fa-child', 'bg' => '#eff6ff', 'text' => '#3b82f6'],
                'Grade 2' => ['icon' => 'fa-child', 'bg' => '#f0fdf4', 'text' => '#22c55e'],
                'Grade 3' => ['icon' => 'fa-child-reaching', 'bg' => '#faf5ff', 'text' => '#a855f7'],
                'Grade 4' => ['icon' => 'fa-child-reaching', 'bg' => '#fff7ed', 'text' => '#f97316'],
                'Grade 5' => ['icon' => 'fa-user-graduate', 'bg' => '#fef2f2', 'text' => '#ef4444'],
                'Grade 6' => ['icon' => 'fa-user-graduate', 'bg' => '#f0fdfa', 'text' => '#14b8a6']
            ];
            foreach($grade_counts as $grade => $count):
                $ui = $cards[$grade];
                
                // FILTER: Show only if Admin OR if it matches assigned grade
                if ($admin_role === 'Admin' || $assigned_grade === $grade):
            ?>
            <div style="background: #ffffff; padding: 20px; border-radius: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); display: flex; align-items: center; justify-content: space-between;">
                <div><h3 style="color: #64748b; font-size: 13px; margin: 0;"><?php echo $grade; ?></h3><h1 style="font-size: 28px; margin: 5px 0;"><?php echo $count; ?></h1></div>
                <div style="width: 50px; height: 50px; border-radius: 12px; display: flex; justify-content: center; align-items: center; background: <?php echo $ui['bg']; ?>; color: <?php echo $ui['text']; ?>;"><i class="fa-solid <?php echo $ui['icon']; ?>"></i></div>
            </div>
            <?php 
                endif; 
            endforeach; 
            ?>
        </div>

    <script>
        // Clock Logic
        function updateClock() {
            const now = new Date();
            document.getElementById('live-date').textContent = now.toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
            document.getElementById('live-time').textContent = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        }
        setInterval(updateClock, 1000); updateClock();

        // ==========================================
        // 🟢 ESP32 LIVE SCAN LISTENER (AJAX POLLING)
        // ==========================================
        let lastTimestamp = null;
        let isFirstLoad = true;
        let wasOnline = null;
        const statusBox = document.getElementById('live-status-box');

        function checkLiveScan() {
            // Request JSON file (Add ?t= to prevent caching)
            fetch('check_live_scan.php?t=' + Date.now())
            .then(r => r.json())
            .then(data => {
                
                // --- ONLINE / OFFLINE UI LOGIC ---
                const badge = document.getElementById('esp-status-badge');
                const icon = document.getElementById('esp-status-icon');
                const text = document.getElementById('esp-status-text');

                if (data.is_online) {
                    if (wasOnline !== true) {
                        badge.style.backgroundColor = '#dcfce7';
                        badge.style.color = '#166534';
                        icon.style.animation = 'pulse 2s infinite';
                        text.textContent = 'ESP32 Wi-Fi Ready';
                        wasOnline = true;
                        
                        // Restore default box when back online
                        statusBox.innerHTML = `
                            <div style="padding: 25px; border: 2px dashed #cbd5e1; border-radius: 15px; background: #f8fafc;">
                                <i class="fa-solid fa-wifi fa-fade" style="font-size: 30px; color: #3b82f6; margin-bottom: 15px;"></i>
                                <h3 style="color: #64748b; margin: 0; font-size: 16px;">Waiting for ESP32 Scan...</h3>
                            </div>
                        `;
                    }
                } else {
                    if (wasOnline !== false) {
                        badge.style.backgroundColor = '#fee2e2';
                        badge.style.color = '#ef4444';
                        icon.style.animation = 'none';
                        text.textContent = 'ESP32 Offline';
                        wasOnline = false;

                        statusBox.innerHTML = `
                            <div style="padding: 25px; border: 2px dashed #f87171; border-radius: 15px; background: #fef2f2;">
                                <i class="fa-solid fa-triangle-exclamation fa-fade" style="font-size: 30px; color: #ef4444; margin-bottom: 15px;"></i>
                                <h3 style="color: #991b1b; margin: 0; font-size: 16px;">ESP32 is Disconnected</h3>
                                <p style="color: #ef4444; font-size: 12px; margin-top: 5px;">Please check if it is plugged in.</p>
                            </div>
                        `;
                    }
                }

                // --- TAP EVENT LOGIC ---
                if(data.timestamp && data.timestamp !== lastTimestamp) {
                    
                    if (isFirstLoad) {
                        lastTimestamp = data.timestamp;
                        isFirstLoad = false;
                        return; // Don't trigger the box on first page load
                    }
                    
                    lastTimestamp = data.timestamp;

                    let actionText = "";
                    let colorTheme = "";
                    let bgTheme = "";
                    let icon = "";
                    let details = "";

                    // CHECK WHICH ACTION OCCURRED
                    if (data.action === "TIME IN") {
                        actionText = "SUCCESSFULLY LOGGED IN!";
                        colorTheme = "#22c55e"; // Green
                        bgTheme = "#dcfce7";
                        icon = "fa-circle-check";
                        details = `Status: <span style="color: ${colorTheme}; font-weight: bold;">${data.status}</span>`;
                    } 
                    else if (data.action === "TIME OUT") {
                        actionText = "SUCCESSFULLY LOGGED OUT!";
                        colorTheme = "#f59e0b"; // Amber/Orange
                        bgTheme = "#fef3c7";
                        icon = "fa-circle-check";
                        details = `Have a safe trip!`;
                    } 
                    else if (data.action === "DONE") {
                        // 👉 ALREADY LOGGED OUT TODAY
                        actionText = "SEE YOU NEXT TIME!";
                        colorTheme = "#3b82f6"; // Blue
                        bgTheme = "#eff6ff";
                        icon = "fa-handshake"; 
                        details = `You have already logged out today.`;
                    } 
                    else if (data.action === "ERROR") {
                        // 👉 IF AN UNREGISTERED CARD TAPS
                        actionText = "UNREGISTERED ID!";
                        colorTheme = "#ef4444"; // Red
                        bgTheme = "#fee2e2";
                        icon = "fa-circle-xmark";
                        details = `Please register ID: <span style="color: red; font-family: monospace;">${data.rfid}</span>`;
                    }

                    // DISPLAY THE BOX
                    statusBox.innerHTML = `
                        <div style="background-color: ${bgTheme}; border: 3px solid ${colorTheme}; padding: 20px; border-radius: 15px; text-align: center; box-shadow: 0 10px 25px rgba(0,0,0,0.1); animation: pulse 0.5s ease-in-out;">
                            <div style="color: ${colorTheme}; font-size: 24px; font-weight: 900; margin-bottom: 10px;">
                                <i class="fa-solid ${icon}"></i> ${actionText}
                            </div>
                            <div style="color: #1e293b; font-size: 20px; font-weight: 800; text-transform: uppercase;">
                                ${data.name}
                            </div>
                            <div style="background: white; display: inline-block; padding: 5px 15px; border-radius: 20px; margin-top: 10px; font-size: 13px; font-weight: 700; color: #64748b; border: 1px solid #e2e8f0;">
                                ${details}
                            </div>
                        </div>
                    `;

                    // After 5 seconds, revert to "Waiting..."
                    setTimeout(() => {
                        if (wasOnline) {
                            statusBox.innerHTML = `
                                <div style="padding: 25px; border: 2px dashed #cbd5e1; border-radius: 15px; background: #f8fafc;">
                                    <i class="fa-solid fa-wifi fa-fade" style="font-size: 30px; color: #3b82f6; margin-bottom: 15px;"></i>
                                    <h3 style="color: #64748b; margin: 0; font-size: 16px;">Waiting for ESP32 Scan...</h3>
                                </div>
                            `;
                        }
                    }, 5000);
                }
            })
            .catch(err => {
                console.log("Error:", err);
            });
        }

        setInterval(checkLiveScan, 1500);
    </script>

    <?php include 'chat_widget.php'; ?>
</body>
</html>