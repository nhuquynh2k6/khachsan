<?php
session_start();

// 1. KIỂM TRA ĐĂNG NHẬP
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] != 0) {
    header("Location: login.php");
    exit();
}

// 2. KIỂM TRA SESSION THANH TOÁN
// Nếu không có thông tin đơn hàng trong session (do khách tự gõ link), đá về trang lịch sử
if (!isset($_SESSION['payment_booking_id']) || !isset($_SESSION['payment_amount'])) {
    header("Location: booking_history.php");
    exit();
}

$booking_id = $_SESSION['payment_booking_id'];
$tong_tien = $_SESSION['payment_amount'];

include 'header.php';
?>

<style>
    .payment-wrapper { max-width: 700px; margin: 50px auto; padding: 30px; background: #fff; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); font-family: Arial, sans-serif; text-align: center; border-top: 5px solid #3498db; }
    
    .payment-title { color: #2c3e50; font-size: 26px; margin-top: 0; margin-bottom: 10px; text-transform: uppercase;}
    .payment-subtitle { color: #7f8c8d; font-size: 15px; margin-bottom: 30px; }
    
    .order-info { background: #fdf2e9; border: 1px dashed #e67e22; padding: 20px; border-radius: 8px; margin-bottom: 30px; }
    .order-info h3 { margin: 0 0 10px 0; color: #d35400; font-size: 20px; }
    .order-info .amount { font-size: 32px; font-weight: bold; color: #e74c3c; margin: 10px 0; }
    .order-info .content { font-size: 16px; color: #2c3e50; font-weight: bold; }

    .qr-section { margin-bottom: 30px; }
    .qr-section img { width: 250px; height: 250px; object-fit: contain; border: 2px solid #eee; border-radius: 10px; padding: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
    
    .bank-info { background: #f8f9fa; padding: 20px; border-radius: 8px; text-align: left; max-width: 400px; margin: 0 auto 30px auto; border-left: 4px solid #3498db; }
    .bank-info p { margin: 8px 0; font-size: 15px; color: #34495e; }
    .bank-info strong { color: #2c3e50; display: inline-block; width: 130px; }

    .btn-confirm-transfer { background: #2ecc71; color: white; border: none; padding: 16px 30px; border-radius: 8px; font-size: 18px; font-weight: bold; cursor: pointer; transition: 0.3s; text-decoration: none; display: inline-block; text-transform: uppercase; box-shadow: 0 4px 15px rgba(46, 204, 113, 0.4); width: 100%; box-sizing: border-box;}
    .btn-confirm-transfer:hover { background: #27ae60; transform: translateY(-2px); box-shadow: 0 6px 20px rgba(46, 204, 113, 0.6);}
    
    .btn-cancel { display: inline-block; margin-top: 20px; color: #95a5a6; text-decoration: none; font-size: 15px; transition: 0.3s; }
    .btn-cancel:hover { color: #e74c3c; }
</style>

<div class="payment-wrapper">
    <h1 class="payment-title">Thanh Toán Chuyển Khoản</h1>
    <p class="payment-subtitle">Quý khách vui lòng quét mã QR hoặc chuyển khoản theo thông tin bên dưới</p>

    <div class="order-info">
        <h3>MÃ ĐƠN: <?= htmlspecialchars($booking_id) ?></h3>
        <div class="amount"><?= number_format($tong_tien, 0, ',', '.') ?> VNĐ</div>
        <div class="content">Nội dung CK: <span style="background: #fff; padding: 5px 10px; border-radius: 4px; border: 1px solid #ccc; letter-spacing: 1px;"><?= htmlspecialchars($booking_id) ?></span></div>
    </div>

    <div class="qr-section">
        <img src="https://vietqr.net/portal/v1/qr/logo/qrib.png" alt="Mã QR Thanh Toán">
        <p style="color: #7f8c8d; font-size: 14px; margin-top: 10px;">(Dùng App Ngân hàng hoặc Momo để quét mã)</p>
    </div>

    <div class="bank-info">
        <p><strong>Ngân hàng:</strong> Vietcombank (VCB)</p>
        <p><strong>Chủ tài khoản:</strong> THREE BEAUTIFUL GIRL</p>
        <p><strong>Số tài khoản:</strong> 0123456789</p>
        <p><strong>Chi nhánh:</strong> Cần Thơ</p>
    </div>

    <a href="confirm_payment.php" class="btn-confirm-transfer">Tôi Đã Chuyển Khoản Thành Công</a>
    
    <br>
    <a href="booking_history.php" class="btn-cancel">Thanh toán sau (Lưu vào lịch sử)</a>
</div>

<?php include 'footer.php'; ?>