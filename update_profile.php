<?php
require 'connection.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SESSION['admin_id'])) {
    $id = $_SESSION['admin_id'];
    $name = $_POST['full_name'];
    $email = $_POST['email'];
    $institutional_email = $_POST['institutional_email'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];

    // I-update ang database
    $sql = "UPDATE admins SET full_name='$name', email='$email', institutional_email='$institutional_email', phone='$phone', address='$address' WHERE id=$id";
    
    if ($conn->query($sql) === TRUE) {
        // I-update usab ang Session para mo-bag-o diretso sa screen
        $_SESSION['admin_name'] = $name;
        $_SESSION['admin_email'] = $email;
        $_SESSION['admin_institutional_email'] = $institutional_email;
        $_SESSION['admin_phone'] = $phone;
        $_SESSION['admin_address'] = $address;
        
        echo "<script>alert('Profile Updated Successfully!'); window.location.href='settings.php';</script>";
    } else {
        echo "<script>alert('Error updating profile.'); window.location.href='settings.php';</script>";
    }
} else {
    header("Location: index.php");
}
$conn->close();
?>