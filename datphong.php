<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] != 0) {
    header("Location: login.php");
    exit();
}

$ma_phong = $_POST['ma_phong'] ?? '';
$checkin = $_POST['checkin'] ?? '';
$checkout = $_POST['checkout'] ?? '';
$so_luong_phong = $_POST['so_luong_phong'] ?? 1;
$so_luong_khach = $_POST['so_luong_khach'] ?? 1;
$khach_hang_cccd = $_SESSION['user_id']; 

if (empty($ma_phong) || empty($checkin) || empty($checkout)) {
    header("Location: trangchu.php");
    exit();
}

$success = false;
$error_msg = "";
$booking_id = "";
$tong_tien_du_kien = 0;

try {
    $conn->beginTransaction();

    $stmt_phong = $conn->prepare("SELECT ma_loai, gia_phong, so_phong_trong, ten_phong FROM phong WHERE ma_phong = ? FOR UPDATE");
    $stmt_phong->execute([$ma_phong]);
    $phong_info = $stmt_phong->fetch(PDO::FETCH_ASSOC);

    if (!$phong_info || $phong_info['so_phong_trong'] < $so_luong_phong) {
        throw new Exception("Rất tiếc, loại phòng này hiện chỉ còn " . $phong_info['so_phong_trong'] . " phòng trống. Vui lòng giảm số lượng.");
    }

    $checkin_date = strtotime($checkin);
    $checkout_date = strtotime($checkout);
    $so_ngay = round(($checkout_date - $checkin_date) / (60 * 60 * 24));
    
    $tong_tien_du_kien = $so_ngay * $phong_info['gia_phong'] * $so_luong_phong;
    $ma_loai = $phong_info['ma_loai'];
    
    $booking_id = 'BK' . date('Ymd') . '-' . rand(1000, 9999);

    $sql_insert = "INSERT INTO dat_phong (booking_id, khach_hang_cccd, ma_phong, ma_loai, ngay_den, ngay_di, so_khach, tong_tien_du_kien, trang_thai) 
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Tạm giữ thanh toán')";
    $stmt_insert = $conn->prepare($sql_insert);
    $stmt_insert->execute([$booking_id, $khach_hang_cccd, $ma_phong, $ma_loai, $checkin, $checkout, $so_luong_khach, $tong_tien_du_kien]);

    $stmt_update = $conn->prepare("UPDATE phong SET so_phong_trong = so_phong_trong - ? WHERE ma_phong = ?");
    $stmt_update->execute([$so_luong_phong, $ma_phong]);

    $conn->commit();
    $success = true;

    $_SESSION['payment_booking_id'] = $booking_id;
    $_SESSION['payment_amount'] = $tong_tien_du_kien;

} catch (Exception $e) {
    $conn->rollBack();
    $error_msg = $e->getMessage();
}

include 'header.php';
?>

<style>
    /* THIẾT KẾ MỚI: HIỆN ĐẠI, SANG TRỌNG, CHUẨN UI/UX */
    body { background-color: #f4f7f6; }
    
    .booking-status-card {
        background: #ffffff;
        border-radius: 24px;
        padding: 50px 40px;
        box-shadow: 0 20px 40px rgba(0,0,0,0.06);
        max-width: 550px;
        margin: 60px auto;
        text-align: center;
        border: 1px solid #f1f2f6;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    /* Icon chờ thanh toán (Đồng hồ) */
    .icon-wrapper {
        width: 90px;
        height: 90px;
        background: #fff3cd;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 25px;
        color: #f39c12;
        box-shadow: 0 0 0 10px rgba(243, 156, 18, 0.1);
        animation: pulse 2s infinite;
    }
    
    .icon-wrapper.error {
        background: #fdeaea;
        color: #e74c3c;
        box-shadow: 0 0 0 10px rgba(231, 76, 60, 0.1);
        animation: none;
    }

    @keyframes pulse {
        0% { box-shadow: 0 0 0 0 rgba(243, 156, 18, 0.4); }
        70% { box-shadow: 0 0 0 20px rgba(243, 156, 18, 0); }
        100% { box-shadow: 0 0 0 0 rgba(243, 156, 18, 0); }
    }

    .status-title { font-size: 26px; color: #2d3436; font-weight: 800; margin-bottom: 12px; text-transform: uppercase; letter-spacing: 0.5px;}
    .status-desc { color: #636e72; font-size: 16px; margin-bottom: 35px; line-height: 1.5;}

    /* Khung mã đơn hàng */
    .booking-code-box {
        background: #f8f9fa;
        border: 2px dashed #cbd5e1;
        padding: 20px;
        border-radius: 16px;
        margin-bottom: 30px;
        position: relative;
    }
    .booking-code-label { font-size: 12px; color: #94a3b8; text-transform: uppercase; font-weight: 700; letter-spacing: 1.5px;}
    .booking-code-value { font-size: 32px; color: #0984e3; font-weight: 900; letter-spacing: 2px; margin-top: 5px; }

    /* Hộp cảnh báo thời gian */
    .warning-box {
        background: #fff8e1;
        border-left: 4px solid #f1c40f;
        padding: 18px 20px;
        border-radius: 0 12px 12px 0;
        text-align: left;
        display: flex;
        align-items: flex-start;
        gap: 15px;
        margin-bottom: 35px;
    }
    .warning-icon { font-size: 24px; line-height: 1; }
    .warning-text { font-size: 14.5px; color: #5c4f1c; line-height: 1.6; margin: 0;}
    .warning-text strong { color: #d35400; }

    /* Nút bấm xịn xò */
    .btn-pay {
        background: linear-gradient(135deg, #0984e3, #74b9ff);
        color: white;
        padding: 18px 24px;
        border-radius: 50px;
        text-decoration: none;
        font-weight: bold;
        display: block;
        font-size: 18px;
        transition: all 0.3s ease;
        box-shadow: 0 10px 20px rgba(9, 132, 227, 0.25);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .btn-pay:hover { 
        transform: translateY(-3px); 
        box-shadow: 0 15px 25px rgba(9, 132, 227, 0.4); 
    }
    
    .btn-later {
        display: inline-block;
        margin-top: 25px;
        color: #95a5a6;
        text-decoration: none;
        font-weight: 600;
        font-size: 15px;
        transition: 0.3s;
        border-bottom: 1px solid transparent;
    }
    .btn-later:hover { color: #e74c3c; border-bottom: 1px solid #e74c3c; }

</style>

<div class="booking-status-card">
    <?php if ($success): ?>
        
        <div class="icon-wrapper">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 45px; height: 45px;">
                <circle cx="12" cy="12" r="10"></circle>
                <polyline points="12 6 12 12 16 14"></polyline>
            </svg>
        </div>
        
        <div class="status-title">Đã tạo đơn đặt phòng</div>
        <div class="status-desc">Hệ thống đã ghi nhận yêu cầu của bạn. Vui lòng hoàn tất thanh toán để khách sạn giữ phòng.</div>
        
        <div class="booking-code-box">
            <div class="booking-code-label">Mã đặt phòng của bạn</div>
            <div class="booking-code-value"><?= htmlspecialchars($booking_id) ?></div>
        </div>
        
        <div class="warning-box">
            <div class="warning-icon">⏳</div>
            <div class="warning-text">
                Đơn đặt phòng đang ở trạng thái <strong>Tạm giữ</strong>. Bạn có <strong>30 phút</strong> để thực hiện thanh toán. Quá thời gian này, hệ thống sẽ tự động hủy đơn để nhường phòng cho khách khác.
            </div>
        </div>

        <a href="booking_payment.php" class="btn-pay">Thanh Toán Ngay ➔</a>
        <a href="booking_history.php" class="btn-later">Thanh toán sau (Lưu vào lịch sử)</a>

    <?php else: ?>
        
        <div class="icon-wrapper error">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width: 45px; height: 45px;">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="15" y1="9" x2="9" y2="15"></line>
                <line x1="9" y1="9" x2="15" y2="15"></line>
            </svg>
        </div>
        <div class="status-title" style="color: #e74c3c;">ĐẶT PHÒNG THẤT BẠI</div>
        <div class="status-desc" style="color: #d63031; font-weight: 500;"><?= $error_msg ?></div>
        
        <a href="trangchu.php" class="btn-later" style="margin-top: 30px;">← Quay lại Trang chủ</a>
        
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>