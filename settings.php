<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}
require 'connection.php';

// Gamiton ang session data imbes nga default value
$admin_id = $_SESSION['admin_id']; 
$admin_role = $_SESSION['admin_role'];
// 1. PROFILE UPDATE LOGIC
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    $full_name = $_POST['full_name']; $email = $_POST['email']; $phone = $_POST['phone']; 
    $address = $_POST['address'] ?? ''; $institutional_email = $_POST['institutional_email'] ?? '';
    $profile_pic_path = null;

    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0) {
        $target_dir = "uploads/"; if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        $target_file = $target_dir . "profile_" . $admin_id . "_" . time() . "." . strtolower(pathinfo($_FILES["profile_pic"]["name"], PATHINFO_EXTENSION));
        if (move_uploaded_file($_FILES["profile_pic"]["tmp_name"], $target_file)) $profile_pic_path = $target_file; 
    }
    
    $query = "UPDATE admins SET full_name=?, email=?, phone=?, address=?, institutional_email=?";
    $params = [$full_name, $email, $phone, $address, $institutional_email]; $types = "sssss";
    if(!empty($_POST['password'])) { $query .= ", password=?"; $params[] = password_hash($_POST['password'], PASSWORD_DEFAULT); $types .= "s"; }
    if($profile_pic_path) { $query .= ", profile_pic=?"; $params[] = $profile_pic_path; $types .= "s"; }
    $query .= " WHERE id=?"; $params[] = $admin_id; $types .= "i";
    
    $stmt = $conn->prepare($query); $stmt->bind_param($types, ...$params);
    if ($stmt->execute()) {
        $_SESSION['status_alert'] = 'profile_updated'; // I-save sa session
        header("Location: settings.php"); // Redirect sa limpyo nga URL
        exit();
    }
}

// 2. TIME LIMITS SETTINGS LOGIC
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_time_settings'])) {
    $time_settings = [
        'timein_start' => $_POST['timein_start'],
        'late_time' => $_POST['late_time'],
        'timeout_start' => $_POST['timeout_start']
    ];
    file_put_contents('time_settings.json', json_encode($time_settings));
    $_SESSION['status_alert'] = 'time_updated';
    header("Location: settings.php");
    exit();
}

// 3. APPROVAL/DECLINE LOGIC
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action_user'])) {
    $target_id = $_POST['target_user_id'];
    $action = $_POST['action_type'];

    if ($action === 'approve') {
        $stmt = $conn->prepare("UPDATE admins SET status = 'Active' WHERE id = ?");
    } else {
        $stmt = $conn->prepare("DELETE FROM admins WHERE id = ?");
    }
    
    $stmt->bind_param("i", $target_id);
    if ($stmt->execute()) {
        $_SESSION['status_alert'] = 'action_success'; // I-save sa session
        header("Location: settings.php"); // Redirect sa limpyo nga URL
        exit();
    }
}

// --- LOGIC PARA SA SESSION ALERT ---
if (isset($_SESSION['status_alert'])) {
    if ($_SESSION['status_alert'] == 'profile_updated') {
        $alert_script = "Swal.fire({ icon: 'success', title: 'Updated!', text: 'Profile updated successfully!', showConfirmButton: false, timer: 1500 });";
    } elseif ($_SESSION['status_alert'] == 'action_success') {
        $alert_script = "Swal.fire({ icon: 'success', title: 'Success!', text: 'User status updated.', showConfirmButton: false, timer: 1500 });";
    } elseif ($_SESSION['status_alert'] == 'time_updated') {
        $alert_script = "Swal.fire({ icon: 'success', title: 'Saved!', text: 'Attendance time limits updated successfully.', showConfirmButton: false, timer: 1500 });";
    }
    unset($_SESSION['status_alert']); // I-DELETE DAYON PARA DILI MO-REPEAT SA REFRESH
}

// Fetch Data
$admin = $conn->query("SELECT * FROM admins WHERE id = $admin_id")->fetch_assoc();
$pending_result = ($admin_role === 'Admin') ? $conn->query("SELECT * FROM admins WHERE status = 'Pending'") : null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Smart Attendance</title>
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
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .settings-container { width: 100%; max-width: 1000px; margin: 0 auto; }
        .settings-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 25px; }
        .card { background: #fff; border-radius: 15px; padding: 30px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; }
        .section-title { font-size: 20px; font-weight: 800; color: #1e293b; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-weight: 700; color: #64748b; font-size: 12px; text-transform: uppercase; margin-bottom: 8px; }
        .form-group input { width: 100%; padding: 12px 16px; border: 1.5px solid #e2e8f0; border-radius: 10px; outline: none; }
        .form-group input[readonly] { background: #f1f5f9; }
        .img-preview { width: 110px; height: 110px; border-radius: 50%; object-fit: cover; border: 4px solid #3b82f6; margin-bottom: 10px; }
        .update-btn { background: #1e3a8a; color: white; border: none; padding: 15px; border-radius: 12px; cursor: pointer; width: 100%; font-weight: 700; transition: 0.3s; }
        .update-btn:hover { background: #2563eb; }
        .btn-approve { background:#10b981; color:white; border:none; padding:8px 12px; border-radius:5px; cursor:pointer; }
        .btn-decline { background:#ef4444; color:white; border:none; padding:8px 12px; border-radius:5px; cursor:pointer; margin-left:5px; }
        
        @media (max-width: 768px) {
            .settings-grid { grid-template-columns: 1fr; }
            .btn-approve, .btn-decline { width: 100%; margin-top: 5px; margin-left: 0; box-sizing: border-box; }
        }
    </style>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="settings-container">
            <h2 style="margin-bottom: 30px; font-weight: 800; color: #1e293b; font-size: 28px;">Settings</h2>
            
            <div class="settings-grid">
                <div class="card">
                    <h3 class="section-title"><i class="fa-solid fa-user-gear"></i> Update Profile</h3>
                    <form method="POST" enctype="multipart/form-data">
                        <div style="text-align: center; margin-bottom: 20px;">
                            <img src="<?php echo (!empty($admin['profile_pic'])) ? $admin['profile_pic'] : 'https://cdn-icons-png.flaticon.com/512/3135/3135715.png'; ?>" id="picDisplay" class="img-preview">
                            <br><input type="file" name="profile_pic" accept="image/*" onchange="document.getElementById('picDisplay').src = window.URL.createObjectURL(this.files[0])">
                        </div>
                        <div class="form-group"><label>Full Name</label><input type="text" name="full_name" value="<?php echo htmlspecialchars($admin['full_name']); ?>" required></div>
                        <div class="form-group"><label>Email</label><input type="email" name="email" value="<?php echo htmlspecialchars($admin['email']); ?>" required></div>
                        <div class="form-group"><label>Institutional Email</label><input type="email" name="institutional_email" value="<?php echo htmlspecialchars($admin['institutional_email'] ?? ''); ?>"></div>
                        <div class="form-group"><label>Phone</label><input type="text" name="phone" value="<?php echo htmlspecialchars($admin['phone']); ?>"></div>
                        <div class="form-group"><label>New Password</label><input type="password" name="password" placeholder="Leave blank to keep current"></div>
                        <button type="submit" name="update_profile" class="update-btn">Save Changes</button>
                    </form>
                </div>

                <div class="card">
                    <h3 class="section-title"><i class="fa-solid fa-clock"></i> Attendance Time Limits</h3>
                    <form method="POST">
                        <?php
                        $time_settings = ['timein_start' => '07:00', 'late_time' => '08:00', 'timeout_start' => '15:00'];
                        if (file_exists('time_settings.json')) {
                            $time_settings = array_merge($time_settings, json_decode(file_get_contents('time_settings.json'), true));
                        }
                        ?>
                        <div class="form-group">
                            <label>Minimum Time-In Allowed</label>
                            <input type="time" name="timein_start" value="<?php echo htmlspecialchars($time_settings['timein_start']); ?>" required>
                            <small style="color: #94a3b8; font-size: 11px;">Students cannot time in before this time.</small>
                        </div>
                        <div class="form-group">
                            <label>Mark as "Late" After (Time In)</label>
                            <input type="time" name="late_time" value="<?php echo htmlspecialchars($time_settings['late_time']); ?>" required>
                            <small style="color: #94a3b8; font-size: 11px;">Students arriving after this time are marked as Late.</small>
                        </div>
                        <div class="form-group">
                            <label>Minimum Time-Out Allowed</label>
                            <input type="time" name="timeout_start" value="<?php echo htmlspecialchars($time_settings['timeout_start']); ?>" required>
                            <small style="color: #94a3b8; font-size: 11px;">Prevents accidental double-taps by rejecting Time Out before this time.</small>
                        </div>
                        <button type="submit" name="update_time_settings" class="update-btn" style="background: #10b981;">Save Time Settings</button>
                    </form>
                </div>
            </div>

            <?php if ($admin_role === 'Admin' && $pending_result): ?>
            <div class="card" style="margin-top: 25px;">
                <h3 class="section-title" style="color: #f59e0b;"><i class="fa-solid fa-user-clock"></i> Pending Approvals</h3>
                <div style="overflow-x: auto;">
                    <table style="width:100%; border-collapse: collapse;">
                        <thead>
                            <tr style="text-align:left; color:#64748b; font-size:12px; border-bottom:2px solid #f1f5f9;">
                                <th style="padding:10px;">Name</th>
                                <th style="padding:10px;">Email</th>
                                <th style="padding:10px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($pending_result->num_rows > 0): while($row = $pending_result->fetch_assoc()): ?>
                                <tr style="border-bottom: 1px solid #f1f5f9;">
                                    <td style="padding:15px;"><strong><?php echo htmlspecialchars($row['full_name']); ?></strong></td>
                                    <td style="padding:15px;"><?php echo htmlspecialchars($row['email']); ?></td>
                                    <td style="padding:15px;">
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="target_user_id" value="<?php echo $row['id']; ?>">
                                            <input type="hidden" name="action_user" value="1">
                                            <button type="submit" name="action_type" value="approve" class="btn-approve">Approve</button>
                                            <button type="submit" name="action_type" value="decline" class="btn-decline" onclick="return confirm('Are you sure you want to decline this request?')">Decline</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; else: ?>
                                <tr><td colspan="3" style="text-align:center; padding:30px; color:#94a3b8;">No pending requests.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Kini nga script mo-run lang kausa base sa session
        <?php if(!empty($alert_script)) echo $alert_script; ?>
    </script>

    <?php include 'chat_widget.php'; ?>
</body>
</html>