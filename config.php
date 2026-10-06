<?php
$host = "127.0.0.1"; 
$port = "3316"; // Cổng MySQL của bồ đang là 3307 theo như ảnh
$username = "root"; // Tên đăng nhập mặc định của XAMPP
$password = ""; // Mật khẩu mặc định thường để trống
$dbname = "khachsan"; // Tên database bồ đã tạo

try {
    // Kết nối bằng PDO
    $conn = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password);
    // Thiết lập chế độ báo lỗi
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Lỗi kết nối CSDL: " . $e->getMessage());
}
?>