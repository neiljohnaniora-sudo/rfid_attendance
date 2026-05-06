<?php
session_start();
require 'connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM admins WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    echo "<!DOCTYPE html><html><head><script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script></head><body>";

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user['password'])) {
            // CHECK KUNG APPROVED BA ANG ACCOUNT
            if ($user['status'] == 'Pending') {
                echo "<script>Swal.fire({ icon: 'warning', title: 'Account Pending', text: 'Your account has not been approved by the Admin yet. Please wait.' }).then(() => { window.location.href='index.php'; });</script>";
            } else {
                // SUCCESS LOGIN
                $_SESSION['admin_id'] = $user['id'];
                $_SESSION['admin_name'] = $user['full_name'];
                $_SESSION['admin_role'] = $user['role'];
                // KINI ANG BAG-O: I-save ang assigned grade
                $_SESSION['assigned_grade'] = $user['assigned_grade']; 
                
                header("Location: dashboard.php");
                exit();
            }
        } else {
            echo "<script>Swal.fire({ icon: 'error', title: 'Login Failed', text: 'Incorrect password!' }).then(() => { window.location.href='index.php'; });</script>";
        }
    } else {
        echo "<script>Swal.fire({ icon: 'error', title: 'Login Failed', text: 'Email not found!' }).then(() => { window.location.href='index.php'; });</script>";
    }
    echo "</body></html>";
}
?>