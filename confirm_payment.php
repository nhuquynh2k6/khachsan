<?php
session_start();
include 'config.php';

// 1. KIỂM TRA ĐĂNG NHẬP
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] != 0) {
    header("Location: login.php");
    exit();
}

// 2. KIỂM TRA SESSION THANH TOÁN
if (!isset($_SESSION['payment_booking_id'])) {
    // Nếu không có session, đẩy về lịch sử đặt phòng
    header("Location: booking_history.php");
    exit();
}

$booking_id = $_SESSION['payment_booking_id'];
$success = false;
$error_msg = "";

try {
    $conn->beginTransaction();

    // 3. LẤY THÔNG TIN ĐƠN ĐẶT PHÒNG
    $stmt_dp = $conn->prepare("SELECT id, tong_tien_du_kien, trang_thai FROM dat_phong WHERE booking_id = ? FOR UPDATE");
    $stmt_dp->execute([$booking_id]);
    $dat_phong = $stmt_dp->fetch(PDO::FETCH_ASSOC);

    if (!$dat_phong) {
        throw new Exception("Không tìm thấy thông tin đơn đặt phòng!");
    }

    // Nếu đơn này chưa được thanh toán thì mới xử lý
    if ($dat_phong['trang_thai'] !== 'Đã thanh toán đầy đủ') {
        
        // 4. CẬP NHẬT TRẠNG THÁI BẢNG dat_phong
        $stmt_update_dp = $conn->prepare("UPDATE dat_phong SET trang_thai = 'Đã thanh toán đầy đủ' WHERE id = ?");
        $stmt_update_dp->execute([$dat_phong['id']]);

        // 5. TẠO HÓA ĐƠN TRONG BẢNG hoa_don
        $ma_hoa_don = 'HD' . time(); // Mã hóa đơn ngẫu nhiên theo timestamp
        $tien_phong = $dat_phong['tong_tien_du_kien'];
        $ma_nv = 'admin01'; // Dùng tài khoản admin01 làm hệ thống thu ngân tự động
        
        $sql_hd = "INSERT INTO hoa_don (ma_hoa_don, dat_phong_id, ma_nv, tien_phong, tong_tien_thanh_toan, phuong_thuc_thanh_toan, trang_thai_thanh_toan) 
                   VALUES (?, ?, ?, ?, ?, 'Chuyển khoản', 'Đã thanh toán')";
        $stmt_hd = $conn->prepare($sql_hd);
        $stmt_hd->execute([$ma_hoa_don, $dat_phong['id'], $ma_nv, $tien_phong, $tien_phong]);

    }

    $conn->commit();
    $success = true;

    // Xóa session thanh toán để tránh F5 gửi lại
    unset($_SESSION['payment_booking_id']);
    unset($_SESSION['payment_amount']);

} catch (Exception $e) {
    $conn->rollBack();
    $error_msg = $e->getMessage();
}

include 'header.php';
?>

<style>
    .confirm-wrapper { max-width: 600px; margin: 80px auto; padding: 40px; background: #fff; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); text-align: center; border-top: 5px solid #2ecc71;}
    .confirm-wrapper.error { border-top: 5px solid #e74c3c; }
    
    .check-icon { font-size: 80px; color: #2ecc71; margin-bottom: 20px; animation: popIn 0.5s ease; }
    @keyframes popIn { 0% { transform: scale(0); } 80% { transform: scale(1.2); } 100% { transform: scale(1); } }
    
    h1 { color: #2c3e50; font-size: 28px; margin-bottom: 10px; text-transform: uppercase;}
    p.desc { color: #7f8c8d; font-size: 16px; margin-bottom: 30px; line-height: 1.6;}
    
    .booking-code { display: inline-block; background: #eaf2f8; color: #2980b9; padding: 12px 30px; border-radius: 30px; font-weight: bold; font-size: 22px; letter-spacing: 2px; margin-bottom: 30px; border: 2px dashed #3498db;}
    
    .btn-receipt { display: block; background: #f39c12; color: white; text-decoration: none; padding: 18px; border-radius: 8px; font-weight: bold; font-size: 18px; text-transform: uppercase; transition: 0.3s; margin-bottom: 15px; box-shadow: 0 4px 15px rgba(243, 156, 18, 0.3);}
    .btn-receipt:hover { background: #d35400; transform: translateY(-2px); box-shadow: 0 6px 20px rgba(243, 156, 18, 0.4);}
    
    .btn-home { display: block; background: #ecf0f1; color: #34495e; text-decoration: none; padding: 15px; border-radius: 8px; font-weight: bold; font-size: 16px; transition: 0.3s; }
    .btn-home:hover { background: #bdc3c7; color: #2c3e50;}
</style>

<div class="confirm-wrapper <?= $success ? '' : 'error' ?>">
    <?php if ($success): ?>
        <div class="check-icon">✅</div>
        <h1>GIAO DỊCH THÀNH CÔNG!</h1>
        <p class="desc">Hệ thống đã nhận được thanh toán của bạn. Phòng của bạn đã được xác nhận giữ chỗ thành công 100%.</p>
        
        <div class="booking-code">MÃ ĐƠN: <?= htmlspecialchars($booking_id) ?></div>
        
        <a href="bienlai.php?booking_id=<?= urlencode($booking_id) ?>" class="btn-receipt">📄 XEM & IN BIÊN LAI</a>
        
        <a href="trangchu.php" class="btn-home">Về Trang Chủ</a>

    <?php else: ?>
        <div class="check-icon" style="color: #e74c3c;">❌</div>
        <h1>CÓ LỖI XẢY RA</h1>
        <p class="desc" style="color: #e74c3c; font-weight: bold;"><?= htmlspecialchars($error_msg) ?></p>
        
        <a href="booking_history.php" class="btn-home">Quay lại lịch sử đặt phòng</a>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>