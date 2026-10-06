<?php
// Khởi tạo session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// BẢO MẬT: Nếu chưa đăng nhập mà cố tình gõ link thì đá văng về login
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

include 'config.php';
include 'header.php';

// Lấy tên đăng nhập hoặc họ tên từ session để truy vấn
$user_identifier = $_SESSION['username']; 

$lich_su_dat = [];
if (isset($conn)) {
    try {
        // Query kết nối theo CSDL khachsan_three_beautiful_girl
        // Nối bảng dat_phong -> phong -> khach_hang
        $sql = "SELECT dp.id AS PHIEUTHUE_ID, dp.ngay_dat AS NGAYLAPPHIEU, p.ten_phong AS TENPHONG, dp.tong_tien AS DONGIA, dp.tinh_trang 
                FROM dat_phong dp
                JOIN phong p ON dp.ma_phong = p.ma_phong
                JOIN khach_hang kh ON dp.khach_hang_cccd = kh.cccd_ho_chieu
                WHERE kh.ho_ten = ? OR kh.cccd_ho_chieu = ?
                ORDER BY dp.ngay_dat DESC";
                
        $stmt = $conn->prepare($sql);
        $stmt->execute([$user_identifier, $user_identifier]);
        $lich_su_dat = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // Tạm ẩn lỗi chi tiết ra màn hình, chỉ lưu log nếu cần
        // $error_msg = "Lỗi truy xuất dữ liệu: " . $e->getMessage();
        $error_msg = "Hiện tại chưa thể tải lịch sử đặt phòng. Vui lòng thử lại sau.";
    }
}
?>
<style>
    .history-page { padding: 60px 20px; min-height: 60vh; background: #f4f7f6; }
    .history-container { max-width: 900px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
    .history-title { text-align: center; color: #2c3e50; font-size: 28px; margin-bottom: 20px; }
    
    .history-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    .history-table th { background: #34495e; color: white; padding: 15px; text-align: left; }
    .history-table td { padding: 15px; border-bottom: 1px solid #eee; color: #333; }
    .history-table tr:hover { background: #f9f9f9; }
    .status-badge { padding: 5px 10px; border-radius: 20px; font-size: 13px; font-weight: bold; background: #2ecc71; color: white; }
    
    .empty-history { text-align: center; padding: 40px; color: #7f8c8d; font-size: 18px; }
</style>

<div class="history-page">
    <div class="history-container">
        <h2 class="history-title">📅 Lịch Sử Đặt Phòng Của Bạn</h2>
        
        <?php if (isset($error_msg)): ?>
            <p style="color: red; text-align: center;"><?= $error_msg ?></p>
        <?php endif; ?>

        <?php if (count($lich_su_dat) > 0): ?>
            <table class="history-table">
                <thead>
                    <tr>
                        <th>Mã Phiếu</th>
                        <th>Ngày Lập Phiếu</th>
                        <th>Tên Phòng</th>
                        <th>Đơn Giá</th>
                        <th>Trạng Thái</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($lich_su_dat as $row): ?>
                        <tr>
                            <td><strong>#<?= htmlspecialchars($row['PHIEUTHUE_ID']) ?></strong></td>
                            <td><?= date('d/m/Y', strtotime($row['NGAYLAPPHIEU'])) ?></td>
                            <td style="color: #e67e22; font-weight: bold;"><?= htmlspecialchars($row['TENPHONG']) ?></td>
                            <td><?= number_format($row['DONGIA'], 0, ',', '.') ?> VNĐ</td>
                            <td><span class="status-badge">Đã xác nhận</span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-history">
                <span style="font-size: 50px; display: block; margin-bottom: 15px;">📭</span>
                Bạn chưa có lịch sử đặt phòng nào tại khách sạn chúng tôi.<br>
                <a href="trangchu.php" style="display: inline-block; margin-top: 15px; padding: 10px 20px; background: #f39c12; color: white; text-decoration: none; border-radius: 5px;">Đặt Phòng Ngay</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php 
if (file_exists('footer.php')) { include 'footer.php'; }
?>