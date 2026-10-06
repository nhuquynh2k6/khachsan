<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] != 0) {
    echo "<script>alert('Vui lòng đăng nhập tài khoản Khách hàng!'); window.location.href='login.php';</script>";
    exit();
}

$ma_phong = $_POST['ma_phong'] ?? '';
$checkin = $_POST['checkin'] ?? '';
$checkout = $_POST['checkout'] ?? '';
// NHẬN 2 BIẾN MỚI
$so_luong_phong = $_POST['so_luong_phong'] ?? 1;
$so_luong_khach = $_POST['so_luong_khach'] ?? 1;

if (empty($ma_phong) || empty($checkin) || empty($checkout)) {
    header("Location: trangchu.php");
    exit();
}

$checkin_date = strtotime($checkin);
$checkout_date = strtotime($checkout);
$so_ngay = round(($checkout_date - $checkin_date) / (60 * 60 * 24));

if ($so_ngay <= 0) {
    echo "<script>alert('❌ Lỗi: Ngày trả phòng phải sau ngày nhận!'); window.location.href='room.php?ma_phong=" . urlencode($ma_phong) . "';</script>";
    exit();
}

$stmt = $conn->prepare("SELECT p.*, lp.ten_loai FROM phong p LEFT JOIN loai_phong lp ON p.ma_loai = lp.ma_loai WHERE p.ma_phong = ?");
$stmt->execute([$ma_phong]);
$phong = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$phong) {
    echo "<script>alert('Không tìm thấy thông tin phòng!'); window.location.href='trangchu.php';</script>";
    exit();
}

// THUẬT TOÁN MỚI: Số đêm x Giá phòng x Số lượng phòng
$tong_tien_du_kien = $so_ngay * $phong['gia_phong'] * $so_luong_phong;

include 'header.php';
?>

<style>
    /* CSS giữ nguyên như bản cũ */
    .booking-confirm-wrapper { max-width: 800px; margin: 50px auto; font-family: Arial, sans-serif; }
    .step-indicator { text-align: center; color: #7f8c8d; font-size: 14px; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 10px; font-weight: bold;}
    .page-title { text-align: center; color: #2c3e50; font-size: 28px; margin-top: 0; margin-bottom: 30px; }
    .invoice-card { background: #fff; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); overflow: hidden; border-top: 5px solid #3498db; }
    .invoice-header { background: #f8f9fa; padding: 25px; border-bottom: 1px solid #eee; display: flex; align-items: center; gap: 20px;}
    .invoice-header img { width: 120px; height: 80px; object-fit: cover; border-radius: 8px; filter: grayscale(20%); }
    .invoice-room-info h2 { margin: 0 0 5px 0; color: #2c3e50; font-size: 22px; }
    .invoice-room-info p { margin: 0; color: #7f8c8d; font-size: 15px; }
    .invoice-body { padding: 30px; }
    .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px; }
    .info-item { background: #fdfefe; padding: 15px; border: 1px solid #eaeded; border-radius: 8px; }
    .info-item label { display: block; font-size: 13px; color: #95a5a6; text-transform: uppercase; margin-bottom: 5px; font-weight: bold;}
    .info-item span { font-size: 16px; color: #2c3e50; font-weight: bold; }
    .invoice-total { background: #eaf2f8; padding: 20px; border-radius: 8px; text-align: right; margin-bottom: 30px; border: 1px dashed #3498db;}
    .invoice-total p { margin: 5px 0; font-size: 16px; color: #34495e; }
    .invoice-total h3 { margin: 10px 0 0 0; color: #e74c3c; font-size: 28px; }
    .user-info { margin-bottom: 30px; padding: 15px; background: #fcf3cf; border-left: 4px solid #f1c40f; border-radius: 4px;}
    .user-info p { margin: 5px 0; color: #8a6d3b; font-size: 15px;}
    .btn-confirm-booking { background: #2ecc71; color: white; border: none; padding: 18px; width: 100%; border-radius: 8px; font-size: 20px; font-weight: bold; cursor: pointer; transition: 0.3s; text-transform: uppercase; box-shadow: 0 4px 15px rgba(46, 204, 113, 0.4);}
    .btn-confirm-booking:hover { background: #27ae60; transform: translateY(-2px); box-shadow: 0 6px 20px rgba(46, 204, 113, 0.6);}
</style>

<div class="booking-confirm-wrapper">
    <h1 class="page-title">XÁC NHẬN ĐẶT PHÒNG</h1>

    <div class="invoice-card">
        <div class="invoice-header">
            <?php if(!empty($phong['url_phong'])): ?>
                <img src="<?= htmlspecialchars($phong['url_phong']) ?>" alt="Phòng">
            <?php else: ?>
                <div style="width:120px; height:80px; background:#ddd; border-radius:8px;"></div>
            <?php endif; ?>
            <div class="invoice-room-info">
                <h2>Hạng phòng: <?= htmlspecialchars($phong['ten_loai'] ?? $phong['ma_loai']) ?></h2>
            </div>
        </div>

        <div class="invoice-body">
            <div class="user-info">
                <strong>👤 Người đặt:</strong> <?= htmlspecialchars($_SESSION['username'] ?? '') ?> 
                | <strong>CCCD/Hộ chiếu:</strong> <?= htmlspecialchars($_SESSION['user_id'] ?? '') ?>
            </div>

            <div class="info-grid">
                <div class="info-item">
                    <label>Check-in</label>
                    <span><?= date('d/m/Y', strtotime($checkin)) ?> (14:00)</span>
                </div>
                <div class="info-item">
                    <label>Check-out</label>
                    <span><?= date('d/m/Y', strtotime($checkout)) ?> (12:00)</span>
                </div>
                <div class="info-item">
                    <label>Số Lượng Phòng</label>
                    <span style="color: #e67e22;"><?= htmlspecialchars($so_luong_phong) ?> Phòng</span>
                </div>
                <div class="info-item">
                    <label>Số Lượng Khách</label>
                    <span><?= htmlspecialchars($so_luong_khach) ?> Người</span>
                </div>
            </div>

            <div class="invoice-total">
                <p>Giá 1 đêm: <?= number_format($phong['gia_phong'], 0, ',', '.') ?> VNĐ</p>
                <p>Số lượng: <?= $so_ngay ?> đêm x <?= $so_luong_phong ?> phòng</p>
                <h3>Tổng Cộng: <?= number_format($tong_tien_du_kien, 0, ',', '.') ?> VNĐ</h3>
            </div>

            <form action="datphong.php" method="POST">
                <input type="hidden" name="ma_phong" value="<?= htmlspecialchars($ma_phong) ?>">
                <input type="hidden" name="checkin" value="<?= htmlspecialchars($checkin) ?>">
                <input type="hidden" name="checkout" value="<?= htmlspecialchars($checkout) ?>">
                <input type="hidden" name="so_luong_phong" value="<?= htmlspecialchars($so_luong_phong) ?>">
                <input type="hidden" name="so_luong_khach" value="<?= htmlspecialchars($so_luong_khach) ?>">
                
                <button type="submit" class="btn-confirm-booking">Xác Nhận Đặt & Chọn Thanh Toán ➔</button>
            </form>
            
            <div style="text-align: center; margin-top: 15px;">
                <a href="room.php?ma_phong=<?= urlencode($ma_phong) ?>" style="color: #7f8c8d; text-decoration: none; font-size: 14px; font-weight: bold;">← Quay lại</a>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>