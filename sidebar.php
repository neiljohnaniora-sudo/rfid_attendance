<?php 
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require 'connection.php'; 

$current_user_id = isset($_SESSION['admin_id']) ? $_SESSION['admin_id'] : 1;
$pic_sql = "SELECT profile_pic, full_name, role FROM admins WHERE id = '$current_user_id'";
$pic_res = $conn->query($pic_sql);
$pic_row = $pic_res ? $pic_res->fetch_assoc() : null;

$profile_pic = (!empty($pic_row['profile_pic'])) ? $pic_row['profile_pic'] : 'https://cdn-icons-png.flaticon.com/512/3135/3135715.png';
$display_name = (!empty($pic_row['full_name'])) ? $pic_row['full_name'] : 'Admin Account';
$display_role = (!empty($pic_row['role'])) ? $pic_row['role'] : 'Staff';

$current_page = basename($_SERVER['PHP_SELF']);

// --- NOTIFICATION LOGIC ---
// Mo-ihap ra ta sa Pending kung 'Admin' ang nag login
$pending_count = 0;
if ($display_role === 'Admin') {
    $pending_sql = "SELECT COUNT(*) as count FROM admins WHERE status = 'Pending'";
    $pending_res = $conn->query($pending_sql);
    if ($pending_res && $row = $pending_res->fetch_assoc()) {
        $pending_count = $row['count'];
    }
}
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
    /* 1. RESET & BASE STYLES */
    * { 
        margin: 0; 
        padding: 0; 
        box-sizing: border-box; 
        font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; 
    }
    
    body { background: #f8fafc; }

    /* 2. SIDEBAR STYLES */
    .sidebar { 
        width: 260px; 
        background: linear-gradient(180deg, #1e3a8a 0%, #0f172a 100%); 
        color: #ffffff; 
        display: flex; 
        flex-direction: column; 
        box-shadow: 4px 0 15px rgba(0,0,0,0.3); 
        height: 100vh; 
        position: fixed; 
        left: 0; 
        top: 0; 
        z-index: 1000;
    }

    /* Header Section */
    .sidebar-header { 
        padding: 35px 20px; 
        text-align: center; 
        border-bottom: 1px solid rgba(255,255,255,0.08); 
    }
    .sidebar-header h2 { 
        font-size: 18px; 
        font-weight: 700;
        letter-spacing: 1.5px; 
        color: white; 
        display: flex; 
        align-items: center; 
        justify-content: center; 
        gap: 12px; 
        text-transform: uppercase;
    }
    
    /* Profile Section */
    .sidebar-profile { 
        text-align: center; 
        padding: 30px 10px; 
        border-bottom: 1px solid rgba(255,255,255,0.08); 
    }
    .profile-img-container { 
        width: 75px; 
        height: 75px; 
        margin: 0 auto 15px; 
        border-radius: 50%; 
        overflow: hidden; 
        border: 3px solid #3b82f6; 
        background: #fff;
        box-shadow: 0 0 15px rgba(59, 130, 246, 0.3);
    }
    .profile-img { width: 100%; height: 100%; object-fit: cover; }
    .sidebar-profile h3 { font-size: 16px; font-weight: 600; color: #fff; margin-bottom: 4px; }
    .sidebar-profile p { font-size: 12px; color: #94a3b8; font-weight: 500; }

    /* Menu Section */
    .sidebar-menu { 
        list-style: none; 
        padding: 20px 0; 
        flex: 1; 
        overflow-y: auto; 
    }
    
    .sidebar-menu li { 
        padding: 15px 30px; 
        cursor: pointer; 
        display: flex; 
        align-items: center; 
        color: #cbd5e1; 
        font-size: 15px; 
        font-weight: 500;
        border-left: 5px solid transparent;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        text-decoration: none;
        white-space: nowrap; 
    }
    
    .sidebar-menu li i { 
        margin-right: 18px; 
        width: 25px; 
        text-align: center; 
        font-size: 1.2rem;
        transition: transform 0.3s ease;
    }

    /* Notification Badge Style */
    .notif-badge {
        background: #ef4444; 
        color: white; 
        font-size: 11px; 
        font-weight: 800; 
        padding: 3px 8px; 
        border-radius: 12px; 
        margin-left: auto; /* Para modikit siya sa right side */
        box-shadow: 0 0 8px rgba(239, 68, 68, 0.6);
    }

    /* Hover State */
    .sidebar-menu li:hover { 
        background: rgba(59, 130, 246, 0.12); 
        color: #ffffff; 
        padding-left: 35px; 
    }
    .sidebar-menu li:hover i { transform: scale(1.1); }

    /* Active State */
    .sidebar-menu li.active { 
        background: rgba(59, 130, 246, 0.2); 
        color: #ffffff; 
        border-left: 5px solid #3b82f6; 
        font-weight: 600;
    }
    .sidebar-menu li.active i { color: #60a5fa; }

    /* Logout Section */
    .logout-btn { 
        padding: 25px; 
        border-top: 1px solid rgba(255,255,255,0.08); 
    }
    .logout-btn button { 
        width: 100%; 
        padding: 12px; 
        background: rgba(239, 68, 68, 0.05); 
        border: 1px solid rgba(239, 68, 68, 0.4); 
        color: #ef4444; 
        border-radius: 10px; 
        cursor: pointer; 
        font-weight: 700; 
        display: flex; 
        align-items: center; 
        justify-content: center; 
        gap: 12px; 
        transition: 0.3s;
        text-transform: uppercase;
        font-size: 13px;
        letter-spacing: 0.5px;
    }
    .logout-btn button:hover { 
        background: #ef4444; 
        color: white; 
        box-shadow: 0 5px 15px rgba(239, 68, 68, 0.3);
    }

    /* 3. MAIN CONTENT WRAPPER */
    .main-content { 
        margin-left: 260px; 
        padding: 40px; 
        min-height: 100vh;
        background: #f8fafc;
        animation: fadeIn 0.5s ease-out;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(8px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>

<div class="sidebar">
    <div class="sidebar-header">
        <h2><i class="fa-solid fa-graduation-cap" style="color: #60a5fa;"></i> SMART ATTENDANCE</h2>
    </div>
    
    <div class="sidebar-profile">
        <div class="profile-img-container"><img src="<?php echo $profile_pic; ?>" class="profile-img"></div>
        <h3><?php echo $display_name; ?></h3>
        <p><?php echo $display_role; ?></p>
    </div>
    
    <ul class="sidebar-menu">
        <li class="<?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>" onclick="location.href='dashboard.php'">
            <i class="fa-solid fa-chart-pie"></i> <span>Dashboard</span>
        </li>

        <?php if ($display_role === 'Admin'): ?>
        <li class="<?php echo ($current_page == 'users.php') ? 'active' : ''; ?>" onclick="location.href='users.php'">
            <i class="fa-solid fa-user-shield"></i> <span>User Accounts</span>
        </li>
        <?php endif; ?>

        <li class="<?php echo ($current_page == 'records.php') ? 'active' : ''; ?>" onclick="location.href='records.php'">
            <i class="fa-solid fa-users"></i> <span>Records</span>
        </li>
        
        <li class="<?php echo ($current_page == 'logs.php') ? 'active' : ''; ?>" onclick="location.href='logs.php'">
            <i class="fa-solid fa-clock-rotate-left"></i> <span>History Logs</span>
        </li>

        <li class="<?php echo ($current_page == 'settings.php') ? 'active' : ''; ?>" onclick="location.href='settings.php'">
            <i class="fa-solid fa-gear"></i> <span>Settings</span>
            <?php if ($display_role === 'Admin' && $pending_count > 0): ?>
                <span class="notif-badge"><?php echo $pending_count; ?></span>
            <?php endif; ?>
        </li>
    </ul>
    
    <div class="logout-btn">
        <button onclick="location.href='logout.php'">
            <i class="fa-solid fa-power-off"></i> Log Out
        </button>
    </div>
</div>