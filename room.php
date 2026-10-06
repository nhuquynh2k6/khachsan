<?php
session_start();
include 'config.php';

// 1. Lấy mã phòng từ URL
$ma_phong = isset($_GET['ma_phong']) ? trim($_GET['ma_phong']) : '';

if (empty($ma_phong)) {
    // Nếu không có mã phòng, đá về trang chủ
    header("Location: trangchu.php");
    exit();
}

// 2. Lấy thông tin chi tiết phòng (Join với bảng loai_phong để lấy thông tin sức chứa, diện tích)
$sql = "SELECT p.*, lp.ten_loai, lp.suc_chua, lp.dien_tich 
        FROM phong p 
        LEFT JOIN loai_phong lp ON p.ma_loai = lp.ma_loai 
        WHERE p.ma_phong = ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$ma_phong]);
$phong = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$phong) {
    echo "<script>alert('Phòng không tồn tại!'); window.location.href='trangchu.php';</script>";
    exit();
}

include 'header.php';
?>

<style>
    .room-detail-container { max-width: 1200px; margin: 50px auto; padding: 20px; display: flex; gap: 40px; font-family: Arial, sans-serif; }
    
    /* Cột trái: Hình ảnh và mô tả */
    .room-image-section { flex: 1.2; }
    .room-image-section img { width: 100%; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); object-fit: cover; height: 450px; }
    .room-desc { color: #555; line-height: 1.8; font-size: 16px; margin: 20px 0; text-align: justify;}
    .room-attributes { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 30px; }
    .attr-box { background: #f8f9fa; padding: 15px; border-radius: 8px; border: 1px solid #eee; font-size: 15px;}
    .attr-box strong { color: #2c3e50; display: block; margin-bottom: 5px; font-size: 13px; text-transform: uppercase; letter-spacing: 1px;}
    
    /* Cột phải: Form đặt phòng */
    .room-info-section { flex: 0.8; display: flex; flex-direction: column; }
    .room-title { font-size: 32px; color: #2c3e50; margin-bottom: 10px; margin-top: 0; }
    .room-price { font-size: 30px; color: #e74c3c; font-weight: bold; margin-bottom: 25px; }
    
    .booking-form { background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); border-top: 5px solid #2ecc71; }
    .booking-form h3 { margin-top: 0; color: #2ecc71; text-align: center; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 20px;}
    
    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; font-weight: bold; margin-bottom: 8px; color: #34495e; font-size: 14px;}
    .form-group input { width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 5px; box-sizing: border-box; font-size: 15px; transition: 0.3s;}
    .form-group input:focus { border-color: #2ecc71; outline: none; box-shadow: 0 0 5px rgba(46, 204, 113, 0.3);}
    
    .form-row { display: flex; gap: 15px; }
    .form-row .form-group { flex: 1; }
    
    .btn-submit { background: #e67e22; color: white; border: none; padding: 16px; width: 100%; border-radius: 8px; font-size: 18px; font-weight: bold; cursor: pointer; transition: 0.3s; margin-top: 10px; text-transform: uppercase; box-shadow: 0 4px 10px rgba(230, 126, 34, 0.3);}
    .btn-submit:hover { background: #d35400; transform: translateY(-2px); box-shadow: 0 6px 15px rgba(230, 126, 34, 0.4);}
    
    /* STYLE CHO NÚT QUAY LẠI */
    .btn-back-link { display: block; text-align: center; margin-top: 15px; color: #7f8c8d; text-decoration: none; font-size: 15px; font-weight: bold; transition: 0.3s; }
    .btn-back-link:hover { color: #e74c3c; text-decoration: underline; }

    .badge-status { display: inline-block; padding: 6px 15px; border-radius: 20px; font-weight: bold; color: white; margin-bottom: 15px; font-size: 14px;}
    .status-trong { background: #2ecc71; }
    .status-het { background: #e74c3c; }
    .status-khac { background: #f39c12; }
</style>

<div class="room-detail-container">
    <div class="room-image-section">
        <?php if(!empty($phong['url_phong'])): ?>
            <img src="<?= htmlspecialchars($phong['url_phong']) ?>" alt="<?= htmlspecialchars($phong['ten_phong']) ?>">
        <?php else: ?>
            <div style="width:100%; height:450px; background:#eee; display:flex; align-items:center; justify-content:center; border-radius:12px;">Đang cập nhật ảnh</div>
        <?php endif; ?>
        
        <h3 style="color: #2c3e50; margin-top: 30px; text-transform: uppercase; border-bottom: 2px solid #f1c40f; padding-bottom: 10px; display: inline-block;">Thông tin chi tiết</h3>
        <p class="room-desc"><?= nl2br(htmlspecialchars($phong['chi_tiet_phong'])) ?></p>
        
        <div class="room-attributes">
            <div class="attr-box">
                <strong>Hạng Phòng</strong> <?= htmlspecialchars($phong['ten_loai'] ?? $phong['ma_loai']) ?>
            </div>
            <div class="attr-box">
                <strong>Tình trạng kho</strong> 
                <span style="color: #2ecc71; font-weight: bold;">Còn <?= htmlspecialchars($phong['so_phong_trong']) ?> phòng trống</span>
            </div>
            <div class="attr-box">
                <strong>Diện tích</strong> <?= !empty($phong['dien_tich']) ? $phong['dien_tich'] . ' m²' : 'Đang cập nhật' ?>
            </div>
            <div class="attr-box">
                <strong>Sức chứa tối đa</strong> <?= htmlspecialchars($phong['suc_chua'] ?? '2') ?> Người / phòng
            </div>
        </div>
    </div>

    <div class="room-info-section">
        <?php 
            $trang_thai = $phong['trang_thai'];
            $is_available = ($trang_thai == 'Trống' && $phong['so_phong_trong'] > 0);
            
            $badge_class = 'status-khac';
            if ($is_available) $badge_class = 'status-trong';
            elseif ($trang_thai == 'Đang bảo trì' || $trang_thai == 'Đang lưu trú' || $phong['so_phong_trong'] <= 0) $badge_class = 'status-het';
        ?>
        
        <div>
            <span class="badge-status <?= $badge_class ?>">
                Trạng thái: <?= $is_available ? 'Đang trống' : 'Hết phòng/Bảo trì' ?>
            </span>
        </div>
        
        <h1 class="room-title"><?= htmlspecialchars($phong['ten_phong']) ?></h1>
        <div class="room-price">
            <?= number_format($phong['gia_phong'], 0, ',', '.') ?> VNĐ 
            <span style="font-size: 16px; color: #7f8c8d; font-weight: normal;">/ đêm</span>
        </div>

        <?php if ($is_available): ?>
            <div class="booking-form">
                <h3>Chọn Ngày & Số Lượng</h3>
                
                <form action="booking_confirm.php" method="POST">
                    <input type="hidden" name="ma_phong" value="<?= htmlspecialchars($phong['ma_phong']) ?>">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Check-in:</label>
                            <input type="date" name="checkin" required min="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="form-group">
                            <label>Check-out:</label>
                            <input type="date" name="checkout" required min="<?= date('Y-m-d', strtotime('+1 day')) ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Số lượng phòng:</label>
                            <input type="number" name="so_luong_phong" min="1" max="<?= htmlspecialchars($phong['so_phong_trong'] ?? 5) ?>" value="1" required>
                        </div>
                        <div class="form-group">
                            <label>Số lượng khách:</label>
                            <input type="number" name="so_luong_khach" min="1" max="20" value="2" required>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-submit">Tiếp tục: Xác nhận đặt phòng ➔</button>
                    
                    <a href="trangchu.php" class="btn-back-link">← Quay lại</a>
                </form>
            </div>
        <?php else: ?>
            <div style="background: #fadbd8; padding: 25px; border-radius: 12px; border: 1px solid #e74c3c; text-align: center; color: #c0392b; margin-top: 20px;">
                <h3 style="margin-top:0;">🚫 Tạm Thời Hết Phòng</h3>
                <p style="font-size: 16px; margin-bottom: 0;">Rất tiếc, loại phòng này hiện không còn trống hoặc đang được bảo trì. Vui lòng quay lại trang chủ và chọn một loại phòng khác nhé!</p>
                <a href="trangchu.php" style="display: inline-block; margin-top: 15px; background: #e74c3c; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold;">← Quay lại chọn phòng</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'footer.php'; ?>