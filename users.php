<?php 
session_start();

// 1. I-check kung naka-login ba (Kini gyud dapat ang mag-una)
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

require 'connection.php';

// --- 1. DELETE LOGIC ---
if (isset($_GET['delete_id']) && $display_role === 'Admin') {
    $id = $_GET['delete_id'];
    $stmt = $conn->prepare("DELETE FROM admins WHERE id = ?");
    $stmt->bind_param("i", $id);
    echo "<!DOCTYPE html><html><head><script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script></head><body>";
    if ($stmt->execute()) {
        echo "<script>Swal.fire({ icon: 'success', title: 'Deleted!', text: 'User has been removed.', showConfirmButton: false, timer: 1500 }).then(() => { window.location.href='users.php'; });</script>";
    }
    echo "</body></html>";
    exit();
}

// --- 2. UPDATE LOGIC (EDIT) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_user'])) {
    $id = $_POST['user_id'];
    $full_name = $_POST['full_name'];
    $role = $_POST['role'];
    // Kung Teacher siya, i-save ang assigned grade. Kung Admin, NULL ra.
    $assigned_grade = ($role === 'Teacher') ? $_POST['assigned_grade'] : NULL;
    $status = $_POST['status'];
    $institutional_email = $_POST['institutional_email'] ?? '';

    // I-check kung nag-type ba ug bag-ong password ang admin
    if (!empty($_POST['password'])) {
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE admins SET full_name = ?, role = ?, assigned_grade = ?, status = ?, institutional_email = ?, password = ? WHERE id = ?");
        $stmt->bind_param("ssssssi", $full_name, $role, $assigned_grade, $status, $institutional_email, $password, $id);
    } else {
        $stmt = $conn->prepare("UPDATE admins SET full_name = ?, role = ?, assigned_grade = ?, status = ?, institutional_email = ? WHERE id = ?");
        $stmt->bind_param("sssssi", $full_name, $role, $assigned_grade, $status, $institutional_email, $id);
    }
    echo "<!DOCTYPE html><html><head><script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script></head><body>";
    if ($stmt->execute()) {
        echo "<script>Swal.fire({ icon: 'success', title: 'Updated!', text: 'User details updated.', showConfirmButton: false, timer: 1500 }).then(() => { window.location.href='users.php'; });</script>";
    }
    echo "</body></html>";
    exit();
}

// --- 3. ADD LOGIC ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_user'])) {
    $full_name = $_POST['full_name'];
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); 
    $role = $_POST['role']; 
    // Kung Teacher siya, i-save ang assigned grade. Kung Admin, NULL ra.
    $assigned_grade = ($role === 'Teacher') ? $_POST['assigned_grade'] : NULL;
    $status = 'Active';
    $institutional_email = $_POST['institutional_email'] ?? '';

    $stmt = $conn->prepare("INSERT INTO admins (full_name, username, password, role, assigned_grade, status, institutional_email) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssss", $full_name, $username, $password, $role, $assigned_grade, $status, $institutional_email);
    echo "<!DOCTYPE html><html><head><script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script></head><body>";
    if ($stmt->execute()) {
        echo "<script>Swal.fire({ icon: 'success', title: 'Account Created!', text: 'New user added successfully.', showConfirmButton: false, timer: 1500 }).then(() => { window.location.href='users.php'; });</script>";
    }
    echo "</body></html>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Accounts - Smart Attendance</title>

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
    @media (max-width: 768px) {
        .users-header {
            flex-direction: column;
            align-items: flex-start !important;
            gap: 15px;
        }
        .users-header button {
            width: 100%;
            justify-content: center;
        }
        .table-container {
            overflow-x: auto;
        }
        .modal-box {
            width: 90% !important;
            margin: 20% auto !important;
        }
    }
</style>
</head>
<body style="background-color: #f8fafc; margin: 0; font-family: 'Inter', sans-serif;">

<?php include 'sidebar.php'; ?>

<div class="main-content" style="padding: 30px; font-family: 'Inter', sans-serif; background-color: #f8fafc; min-height: 100vh;">
    <div class="users-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <div>
            <h2 style="color: #1e3a8a; font-weight: 800; font-size: 26px; margin: 0;">User Accounts</h2>
            <p style="color: #64748b; font-size: 14px;">Manage teacher permissions and grade assignments.</p>
        </div>
        <button onclick="openAddModal()" style="background: #2563eb; color: white; border: none; padding: 12px 24px; border-radius: 12px; cursor: pointer; font-weight: 600; display: flex; align-items: center; gap: 8px; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);">
            <i class="fa-solid fa-user-plus"></i> Add New Account
        </button>
    </div>

    <div class="table-container" style="background: white; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); overflow-x: auto; border: 1px solid #e2e8f0;">
        <table style="width: 100%; border-collapse: collapse; text-align: left; min-width: 850px;">
            <thead style="background: #f8fafc; color: #64748b; text-transform: uppercase; font-size: 11px; letter-spacing: 1px; border-bottom: 2px solid #f1f5f9; white-space: nowrap;">
                <tr>
                    <th style="padding: 20px;">Name & ID</th>
                    <th style="padding: 20px;">Username</th>
                    <th style="padding: 20px;">Account Role</th>
                    <th style="padding: 20px;">Assigned Grade</th>
                    <th style="padding: 20px;">Status</th>
                    <th style="padding: 20px; text-align: center;">Actions</th>
                </tr>
            </thead>
            <tbody style="color: #1e293b; font-size: 14px;">
                <?php
                $result = $conn->query("SELECT * FROM admins ORDER BY id DESC");
                while($row = $result->fetch_assoc()):
                    $p_pic = (!empty($row['profile_pic'])) ? $row['profile_pic'] : 'https://cdn-icons-png.flaticon.com/512/3135/3135715.png';
                ?>
                <tr style="border-bottom: 1px solid #f1f5f9; transition: 0.2s;" onmouseover="this.style.backgroundColor='#f8fafc'" onmouseout="this.style.backgroundColor='transparent'">
                    <td style="padding: 15px 20px; display: flex; align-items: center; gap: 15px;">
                        <img src="<?php echo $p_pic; ?>" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                        <div>
                            <div style="font-weight: 700; color: #1e3a8a;"><?php echo htmlspecialchars($row['full_name']); ?></div>
                            <div style="font-size: 11px; color: #94a3b8;">ID: #<?php echo str_pad($row['id'], 3, '0', STR_PAD_LEFT); ?></div>
                        </div>
                    </td>
                    <td style="padding: 15px 20px; color: #64748b; font-family: 'Courier New', monospace;"><?php echo htmlspecialchars($row['username'] ?? '---'); ?></td>
                    <td style="padding: 15px 20px;">
                        <span style="background: #eff6ff; color: #2563eb; padding: 4px 10px; border-radius: 6px; font-weight: 600; font-size: 12px;"><?php echo $row['role']; ?></span>
                    </td>
                    <td style="padding: 15px 20px; color: #64748b; font-weight: 600;">
                        <?php echo !empty($row['assigned_grade']) ? $row['assigned_grade'] : 'N/A'; ?>
                    </td>
                    <td style="padding: 15px 20px;">
                        <?php if ($row['status'] === 'Active' || $row['status'] === 'Approved'): ?>
                            <span style="background: #dcfce7; color: #166534; padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: 700;">Active</span>
                        <?php else: ?>
                            <span style="background: #fffbeb; color: #92400e; padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: 700;">Pending</span>
                        <?php endif; ?>
                    </td>
                    <td style="padding: 15px 20px; text-align: center;">
                        <button onclick='openEditModal(<?php echo json_encode($row); ?>)' style="color: #3b82f6; background: none; border: none; cursor: pointer; margin-right: 15px; font-size: 16px;"><i class="fa-solid fa-pen"></i></button>
                        <button onclick="confirmDelete(<?php echo $row['id']; ?>)" style="color: #ef4444; background: none; border: none; cursor: pointer; font-size: 16px;"><i class="fa-solid fa-trash-can"></i></button>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="addModal" style="display:none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); backdrop-filter: blur(5px);">
    <div class="modal-box" style="background: white; width: 400px; margin: 5% auto; padding: 30px; border-radius: 20px;">
        <h3 style="color: #1e3a8a; margin-top: 0;">Add New Account</h3>
        <form method="POST">
            <input type="text" name="full_name" placeholder="Full Name" style="width: 100%; padding: 12px; margin-bottom: 12px; border-radius: 10px; border: 1px solid #e2e8f0;" required>
            <input type="email" name="institutional_email" placeholder="Institutional Email" style="width: 100%; padding: 12px; margin-bottom: 12px; border-radius: 10px; border: 1px solid #e2e8f0;">
            <input type="text" name="username" placeholder="Username" style="width: 100%; padding: 12px; margin-bottom: 12px; border-radius: 10px; border: 1px solid #e2e8f0;" required>
            <input type="password" name="password" placeholder="Password" style="width: 100%; padding: 12px; margin-bottom: 12px; border-radius: 10px; border: 1px solid #e2e8f0;" required>
            
            <label style="font-size: 12px; color: #64748b; margin-bottom: 5px; display: block;">Account Role</label>
            <select name="role" id="add_role" onchange="toggleAddGrade()" style="width: 100%; padding: 12px; margin-bottom: 12px; border-radius: 10px; border: 1px solid #e2e8f0;">
                <option value="Admin">Admin</option>
                <option value="Teacher">Teacher</option>
            </select>
            
            <div id="add_grade_div" style="display: none;">
                <label style="font-size: 12px; color: #64748b; margin-bottom: 5px; display: block;">Assigned Grade</label>
                <select name="assigned_grade" style="width: 100%; padding: 12px; margin-bottom: 20px; border-radius: 10px; border: 1px solid #e2e8f0;">
                    <option value="">Select Grade...</option>
                    <option value="Grade 1">Grade 1</option>
                    <option value="Grade 2">Grade 2</option>
                    <option value="Grade 3">Grade 3</option>
                    <option value="Grade 4">Grade 4</option>
                    <option value="Grade 5">Grade 5</option>
                    <option value="Grade 6">Grade 6</option>
                </select>
            </div>

            <button type="submit" name="save_user" style="width: 100%; background: #2563eb; color: white; border: none; padding: 12px; border-radius: 10px; font-weight: 600; cursor: pointer; margin-top: 10px;">Save Account</button>
            <button type="button" onclick="closeAddModal()" style="width: 100%; margin-top: 10px; background: none; border: none; color: #64748b; cursor: pointer;">Cancel</button>
        </form>
    </div>
</div>

<div id="editModal" style="display:none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); backdrop-filter: blur(5px);">
    <div class="modal-box" style="background: white; width: 400px; margin: 10% auto; padding: 30px; border-radius: 20px;">
        <h3 style="color: #1e3a8a; margin-top: 0;">Edit User Details</h3>
        <form method="POST">
            <input type="hidden" name="user_id" id="edit_id">
            
            <label style="font-size: 12px; color: #64748b; margin-bottom: 5px; display: block;">Full Name</label>
            <input type="text" name="full_name" id="edit_name" style="width: 100%; padding: 12px; margin-bottom: 12px; border-radius: 10px; border: 1px solid #e2e8f0;" required>
            
            <label style="font-size: 12px; color: #64748b; margin-bottom: 5px; display: block;">Institutional Email</label>
            <input type="email" name="institutional_email" id="edit_inst_email" style="width: 100%; padding: 12px; margin-bottom: 12px; border-radius: 10px; border: 1px solid #e2e8f0;">
            
            <label style="font-size: 12px; color: #64748b; margin-bottom: 5px; display: block;">Account Role</label>
            <select name="role" id="edit_role" onchange="toggleEditGrade()" style="width: 100%; padding: 12px; margin-bottom: 12px; border-radius: 10px; border: 1px solid #e2e8f0;">
                <option value="Admin">Admin</option>
                <option value="Teacher">Teacher</option>
            </select>
            
            <div id="edit_grade_div" style="display: none;">
                <label style="font-size: 12px; color: #64748b; margin-bottom: 5px; display: block;">Assigned Grade</label>
                <select name="assigned_grade" id="edit_grade" style="width: 100%; padding: 12px; margin-bottom: 12px; border-radius: 10px; border: 1px solid #e2e8f0;">
                    <option value="">Select Grade...</option>
                    <option value="Grade 1">Grade 1</option>
                    <option value="Grade 2">Grade 2</option>
                    <option value="Grade 3">Grade 3</option>
                    <option value="Grade 4">Grade 4</option>
                    <option value="Grade 5">Grade 5</option>
                    <option value="Grade 6">Grade 6</option>
                </select>
            </div>
            
            <label style="font-size: 12px; color: #64748b; margin-bottom: 5px; display: block;">New Password</label>
            <input type="password" name="password" id="edit_password" placeholder="Leave blank to keep current password" style="width: 100%; padding: 12px; margin-bottom: 12px; border-radius: 10px; border: 1px solid #e2e8f0;">
            
            <label style="font-size: 12px; color: #64748b; margin-bottom: 5px; display: block;">Status</label>
            <select name="status" id="edit_status" style="width: 100%; padding: 12px; margin-bottom: 20px; border-radius: 10px; border: 1px solid #e2e8f0;">
                <option value="Active">Active</option>
                <option value="Pending">Pending</option>
            </select>
            
            <button type="submit" name="update_user" style="width: 100%; background: #2563eb; color: white; border: none; padding: 12px; border-radius: 10px; font-weight: 600; cursor: pointer;">Update Account</button>
            <button type="button" onclick="closeEditModal()" style="width: 100%; margin-top: 10px; background: none; border: none; color: #64748b; cursor: pointer;">Cancel</button>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// Logic para mo-tago/pakita sa Assigned Grade dropdown kung Teacher i-select
function toggleAddGrade() {
    var role = document.getElementById('add_role').value;
    document.getElementById('add_grade_div').style.display = (role === 'Teacher') ? 'block' : 'none';
}

function toggleEditGrade() {
    var role = document.getElementById('edit_role').value;
    document.getElementById('edit_grade_div').style.display = (role === 'Teacher') ? 'block' : 'none';
}

function openAddModal() { 
    document.getElementById('addModal').style.display = 'block'; 
    toggleAddGrade(); // Initialize state
}

function closeAddModal() { 
    document.getElementById('addModal').style.display = 'none'; 
}

function openEditModal(user) {
    document.getElementById('edit_id').value = user.id;
    document.getElementById('edit_name').value = user.full_name;
    document.getElementById('edit_inst_email').value = user.institutional_email ? user.institutional_email : '';
    document.getElementById('edit_role').value = user.role;
    
    // I-set ang grade kung naa
    if (user.assigned_grade) {
        document.getElementById('edit_grade').value = user.assigned_grade;
    } else {
        document.getElementById('edit_grade').value = "";
    }
    
    document.getElementById('edit_password').value = ""; // Clear ang password field kada open
    document.getElementById('edit_status').value = user.status;
    document.getElementById('editModal').style.display = 'block';
    
    toggleEditGrade(); // Ipakita ang dropdown kung teacher siya
}

function closeEditModal() { 
    document.getElementById('editModal').style.display = 'none'; 
}

function confirmDelete(id) {
    Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) { window.location.href = 'users.php?delete_id=' + id; }
    });
}
</script>

<?php include 'chat_widget.php'; ?>
</body>
</html>