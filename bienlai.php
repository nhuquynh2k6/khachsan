<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] != 0) {
    header("Location: login.php");
    exit();
}

$booking_id = $_GET['booking_id'] ?? '';
$khach_hang_cccd = $_SESSION['user_id'];

if (empty($booking_id)) {
    header("Location: booking_history.php");
    exit();
}

// KHÔNG CÒN dp.ghi_chu NỮA
$sql = "SELECT 
            dp.booking_id, dp.ngay_den, dp.ngay_di, dp.so_khach, dp.tong_tien_du_kien, dp.trang_thai, dp.ngay_dat,
            kh.ho_ten, kh.so_dien_thoai, kh.email,
            p.ten_phong, p.ma_phong, p.gia_phong,
            hd.ma_hoa_don, hd.ngay_thanh_toan, hd.phuong_thuc_thanh_toan, hd.trang_thai_thanh_toan,
            lp.ten_loai
        FROM dat_phong dp
        JOIN khach_hang kh ON dp.khach_hang_cccd = kh.cccd_ho_chieu
        JOIN phong p ON dp.ma_phong = p.ma_phong
        LEFT JOIN loai_phong lp ON p.ma_loai = lp.ma_loai
        LEFT JOIN hoa_don hd ON dp.id = hd.dat_phong_id
        WHERE dp.booking_id = ? AND dp.khach_hang_cccd = ?"; 

$stmt = $conn->prepare($sql);
$stmt->execute([$booking_id, $khach_hang_cccd]);
$bill = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$bill) {
    echo "<script>alert('Không tìm thấy biên lai hoặc bạn không có quyền xem!'); window.location.href='booking_history.php';</script>";
    exit();
}

$checkin_date = strtotime($bill['ngay_den']);
$checkout_date = strtotime($bill['ngay_di']);
$so_ngay = round(($checkout_date - $checkin_date) / (60 * 60 * 24));
$so_ngay = max(1, $so_ngay); // Đảm bảo số đêm tối thiểu là 1 để tránh lỗi chia cho 0

$is_paid = ($bill['trang_thai_thanh_toan'] == 'Đã thanh toán' || $bill['trang_thai'] == 'Đã thanh toán đầy đủ');

// TÍNH TOÁN NGƯỢC SỐ LƯỢNG PHÒNG TỪ TỔNG TIỀN (Thủ thuật đỉnh cao)
// Công thức: Số lượng phòng = Tổng tiền / (Giá 1 đêm * Số đêm)
$so_luong_phong = ($bill['gia_phong'] > 0) ? round($bill['tong_tien_du_kien'] / ($bill['gia_phong'] * $so_ngay)) : 1;

include 'header.php';
?>

<style>
    /* CSS Giữ nguyên */
    .receipt-container { max-width: 800px; margin: 40px auto; padding: 40px; background: #fff; border-radius: 5px; box-shadow: 0 0 15px rgba(0,0,0,0.1); font-family: 'Courier New', Courier, monospace; color: #333;}
    .receipt-header { text-align: center; border-bottom: 2px dashed #ccc; padding-bottom: 20px; margin-bottom: 20px; }
    .receipt-header h1 { margin: 0; font-size: 28px; color: #2c3e50; font-family: Arial, sans-serif; text-transform: uppercase;}
    .receipt-header p { margin: 5px 0; font-size: 14px; color: #555; }
    .receipt-title { text-align: center; font-size: 24px; font-weight: bold; margin: 20px 0; text-transform: uppercase; letter-spacing: 2px;}
    .info-section { display: flex; justify-content: space-between; margin-bottom: 30px; font-size: 15px; line-height: 1.6;}
    .info-box { width: 48%; }
    .receipt-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
    .receipt-table th, .receipt-table td { padding: 12px; border-bottom: 1px solid #eee; text-align: left; }
    .receipt-table th { background-color: #f8f9fa; color: #2c3e50; font-family: Arial, sans-serif;}
    .receipt-table .text-right { text-align: right; }
    .total-section { display: flex; justify-content: flex-end; margin-top: 20px; }
    .total-box { width: 50%; background: #fdfefe; padding: 20px; border: 1px solid #eaeded; border-radius: 5px; }
    .total-line { display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 16px; }
    .total-line.grand-total { font-size: 22px; font-weight: bold; color: #e74c3c; border-top: 2px solid #ccc; padding-top: 10px; margin-top: 10px; }
    .status-stamp { display: inline-block; padding: 5px 15px; border: 2px solid #2ecc71; color: #2ecc71; font-weight: bold; font-size: 20px; text-transform: uppercase; transform: rotate(-5deg); margin-top: 15px; font-family: Arial, sans-serif;}
    .status-stamp.unpaid { border-color: #e74c3c; color: #e74c3c; }
    .receipt-footer { text-align: center; margin-top: 50px; font-size: 14px; color: #7f8c8d; font-family: Arial, sans-serif; border-top: 1px dashed #ccc; padding-top: 20px;}
    .action-buttons { text-align: center; margin-top: 30px; }
    .btn-print { background: #34495e; color: white; border: none; padding: 12px 25px; border-radius: 5px; font-size: 16px; cursor: pointer; margin-right: 10px; font-family: Arial, sans-serif;}
    .btn-print:hover { background: #2c3e50; }
    .btn-back { background: #bdc3c7; color: #2c3e50; text-decoration: none; padding: 12px 25px; border-radius: 5px; font-size: 16px; font-family: Arial, sans-serif;}
    
    @media print {
        body * { visibility: hidden; }
        .receipt-container, .receipt-container * { visibility: visible; }
        .receipt-container { position: absolute; left: 0; top: 0; width: 100%; box-shadow: none; margin: 0; padding: 0; }
        .action-buttons, nav, footer { display: none !important; }
    }
</style>

<div class="receipt-container" id="printableArea">
    <div class="receipt-header">
        <h1>THREE BEAUTIFUL GIRL HOTEL</h1>
        <p>123 Đường ABC, Phường Ninh Kiều, Cần Thơ</p>
        <p>Hotline: 0123 456 789 | Email: contact@threebeautifulgirl.com</p>
    </div>
    
    <div class="receipt-title">BIÊN LAI THANH TOÁN</div>
    
    <div class="info-section">
        <div class="info-box">
            <strong>THÔNG TIN KHÁCH HÀNG:</strong><br>
            Họ tên: <?= htmlspecialchars($bill['ho_ten']) ?><br>
            SĐT: <?= htmlspecialchars($bill['so_dien_thoai']) ?><br>
            Email: <?= htmlspecialchars($bill['email']) ?? 'Không có' ?>
        </div>
        <div class="info-box">
            <strong>THÔNG TIN HÓA ĐƠN:</strong><br>
            Mã Booking: <strong><?= htmlspecialchars($bill['booking_id']) ?></strong><br>
            Mã Hóa đơn: <?= htmlspecialchars($bill['ma_hoa_don'] ?? 'Chưa xuất') ?><br>
            Ngày in: <?= date('d/m/Y H:i:s') ?>
        </div>
    </div>
    
    <table class="receipt-table">
        <thead>
            <tr>
                <th>Nội dung / Dịch vụ</th>
                <th>SL</th>
                <th class="text-right">Đơn giá</th>
                <th class="text-right">Thành tiền</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <?php if ($is_paid): ?>
                        <strong>Phòng: <?= htmlspecialchars($bill['ten_phong']) ?> (Mã: <?= htmlspecialchars($bill['ma_phong']) ?>)</strong><br>
                    <?php else: ?>
                        <strong>Hạng phòng: <?= htmlspecialchars($bill['ten_loai'] ?? 'Chưa xác định') ?></strong><br>
                        <span style="color: #e74c3c; font-size: 13px; font-family: Arial;">(Số phòng sẽ được cấp sau khi thanh toán)</span><br>
                    <?php endif; ?>
                    
                    Check-in: <?= date('d/m/Y', strtotime($bill['ngay_den'])) ?><br>
                    Check-out: <?= date('d/m/Y', strtotime($bill['ngay_di'])) ?><br>
                    Khách: <?= htmlspecialchars($bill['so_khach']) ?> người
                </td>
                <td><?= $so_ngay ?> đêm<br>x <?= $so_luong_phong ?> phòng</td>
                <td class="text-right"><?= number_format($bill['gia_phong'], 0, ',', '.') ?></td>
                <td class="text-right"><?= number_format($bill['tong_tien_du_kien'], 0, ',', '.') ?></td>
            </tr>
        </tbody>
    </table>
    
    <div class="total-section">
        <div class="total-box">
            <div class="total-line">
                <span>Tạm tính:</span>
                <span><?= number_format($bill['tong_tien_du_kien'], 0, ',', '.') ?> VNĐ</span>
            </div>
            <div class="total-line grand-total">
                <span>TỔNG CỘNG:</span>
                <span><?= number_format($bill['tong_tien_du_kien'], 0, ',', '.') ?> VNĐ</span>
            </div>
            
            <div style="text-align: center;">
                <?php if ($is_paid): ?>
                    <div class="status-stamp">ĐÃ THANH TOÁN</div>
                    <p style="font-size: 12px; margin-top: 10px; font-family: Arial;">Phương thức: <?= htmlspecialchars($bill['phuong_thuc_thanh_toan'] ?? 'Chuyển khoản') ?></p>
                <?php else: ?>
                    <div class="status-stamp unpaid">CHƯA THANH TOÁN</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="receipt-footer">
        Cảm ơn quý khách đã sử dụng dịch vụ của Three Beautiful Girl Hotel!<br>
        Hẹn gặp lại quý khách trong những kỳ nghỉ tiếp theo.
    </div>
</div>

<div class="action-buttons">
    <button class="btn-print" onclick="window.print()">🖨️ In Biên Lai</button>
    <a href="booking_history.php" class="btn-back">Quản lý đặt phòng</a>
</div>

<?php include 'footer.php'; ?>