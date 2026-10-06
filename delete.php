<?php
session_start();
// Chặn ngay những ai không phải Admin (role != 1)
if(!isset($_SESSION['role']) || $_SESSION['role'] != 1) {
	header("Location: ../login.php");
	exit();
}

include 'config.php';

if(isset($_GET['id'])){
    $id = $_GET['id'];
	
    // Xóa từ bảng khach_hang, tìm theo cccd_ho_chieu (Vì lúc này id của khách là cccd)
    $sql = "DELETE FROM khach_hang WHERE cccd_ho_chieu = ?";
    $stmt = $conn->prepare($sql);
    
    if($stmt->execute([$id])){
        header("Location: index.php"); // Xóa xong tự quay về trang chủ Admin
		exit();
    }
}
?>