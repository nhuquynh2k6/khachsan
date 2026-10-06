<?php
session_start();      // Bắt buộc phải có để PHP biết session nào cần xóa
session_unset();      // Xóa tất cả các biến session
session_destroy();    // Hủy bỏ session hoàn toàn

// Đẩy về trang chủ - Đảm bảo tên file là trangchu.php đúng như bồ đặt
header("Location: trangchu.php"); 
exit();
?>