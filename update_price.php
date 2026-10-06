<?php
session_start();
include 'config.php';

if(isset($_SESSION['role']) && $_SESSION['role'] == 1) {
    $id = $_GET['id'];
    $price = $_GET['price'];
    
    // Giả sử bảng của bồ tên là 'phong'
    $sql = "UPDATE phong SET gia = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if($stmt->execute([$price, $id])) {
        echo "<script>alert('Cập nhật giá thành công!'); window.location.href='trangchu.php';</script>";
    }
} else {
    echo "Bồ không có quyền làm việc này!";
}
?>