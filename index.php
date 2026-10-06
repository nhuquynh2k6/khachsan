<?php 
session_start(); // Viết đúng chính tả để máy nhận diện bồ là ai

// Nếu chưa đăng nhập, tự động đẩy về trang login
if (!isset($_SESSION['vai_tro']) || $_SESSION['vai_tro'] !== 'Quản lý') {
    header("Location: ../login.php"); // Chú ý dấu ../ để lùi ra khỏi thư mục admin
    exit();
}
include 'config.php'; // Kết nối đến database khachsan bồ vừa t
include 'header.php'; 
?>

<div style="text-align: center; padding: 50px;">
    <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 1): ?>
        <h1 style="color: red;">Chào mừng ADMIN: <?php echo $_SESSION['username']; ?>!</h1>
        <div style="border: 2px solid red; padding: 20px; display: inline-block;">
            <h3>Bảng điều khiển quản trị</h3>
            <button onclick="location.href='quanly_phong.php'">Quản lý phòng khách sạn</button>
            <button onclick="location.href='quanly_user.php'">Quản lý người dùng</button>
        </div>

    <?php else: ?>
        <h1>Chào bồ, <?php echo $_SESSION['username']; ?> đã quay lại!</h1>
        <p>Chúc bồ tìm được phòng khách sạn ưng ý nhé.</p>
        <button onclick="location.href='datphong.php'">Đặt phòng ngay</button>
    <?php endif; ?>

    <br><br>
	<a href="../logout.php">Đăng xuất</a>
</div>

<?php include 'footer.php'; ?>