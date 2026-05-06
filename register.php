<?php
require 'connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    // KINI ANG BAG-O: Kuhaon ang assigned grade
    $assigned_grade = $_POST['assigned_grade'];
    
    // Default role kay 'Teacher', ug default status kay 'Pending'
    $role = 'Teacher';
    $status = 'Pending';

    // Gi-apil na ang assigned_grade sa INSERT query
    $sql = "INSERT INTO admins (full_name, email, phone, address, password, role, assigned_grade, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssss", $full_name, $email, $phone, $address, $password, $role, $assigned_grade, $status);

    echo "<!DOCTYPE html><html><head>
    <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    <style>* { font-family: 'Segoe UI', sans-serif; } body { background-color: #f0f2f5; }</style>
    </head><body>";
    
    if ($stmt->execute()) {
        echo "<script>
            Swal.fire({ 
                icon: 'success', 
                title: 'Registration Sent!', 
                text: 'Your account is created. Please wait for the Admin to approve your account before you can log in.', 
                confirmButtonColor: '#3b82f6'
            }).then(() => { window.location.href='index.php'; });
        </script>";
    } else {
        echo "<script>Swal.fire({ icon: 'error', title: 'Error', text: 'Email already exists.' }).then(() => { window.location.href='index.php'; });</script>";
    }
    echo "</body></html>";
}
?>