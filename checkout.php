<?php
session_start();
include 'config.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 1) {
    header("Location: trangchu.php");
    exit();
}

if (!isset($_GET['booking_id'])) {
    die("<h2 style='text-align:center; padding: 50px;'>⚠️ Không tìm thấy mã đặt phòng! Vui lòng quay lại.</h2>");
}

$booking_id = $_GET['booking_id'];
$success_msg = "";
$error_msg = "";

// ==========================================
// 1. LẤY DỮ LIỆU ĐƠN ĐẶT PHÒNG
// ==========================================
$stmt = $conn->prepare("
    SELECT dp.*, kh.ho_ten, kh.so_dien_thoai, p.ten_phong, p.ma_phong 
    FROM dat_phong dp 
    JOIN khach_hang kh ON dp.khach_hang_cccd = kh.cccd_ho_chieu 
    JOIN phong p ON dp.ma_phong = p.ma_phong 
    WHERE dp.booking_id = ?
");
$stmt->execute([$booking_id]);
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$booking) {
    die("<h2 style='text-align:center; padding: 50px;'>⚠️ Đơn đặt phòng không tồn tại!</h2>");
}

$dp_id = $booking['id'];
$ma_phong = $booking['ma_phong'];
$ngay_den_str = date('d/m/Y', strtotime($booking['ngay_den']));
$ngay_di_str = date('d/m/Y', strtotime($booking['ngay_di']));
$is_checked_out = ($booking['trang_thai'] == 'Hoàn tất trả phòng');

// ==========================================
// 2. ĐẢM BẢO ĐÃ CÓ HÓA ĐƠN
// ==========================================
$stmt_hd = $conn->prepare("SELECT * FROM hoa_don WHERE dat_phong_id = ?");
$stmt_hd->execute([$dp_id]);
$hoadon = $stmt_hd->fetch(PDO::FETCH_ASSOC);

if (!$hoadon) {
    $ma_hd = 'HD' . date('Ymd') . rand(100, 999);
    $conn->prepare("INSERT INTO hoa_don (ma_hoa_don, dat_phong_id, ma_nv, tien_phong, tong_tien_thanh_toan) VALUES (?, ?, ?, ?, ?)")
         ->execute([$ma_hd, $dp_id, $_SESSION['user_id'] ?? 'admin01', $booking['tong_tien_du_kien'], $booking['tong_tien_du_kien']]);
    $stmt_hd->execute([$dp_id]);
    $hoadon = $stmt_hd->fetch(PDO::FETCH_ASSOC);
}

// ==========================================
// 3. XỬ LÝ THÊM DỊCH VỤ PHÁT SINH
// ==========================================
if (isset($_POST['btn_add_service']) && !$is_checked_out) {
    $ma_dv = $_POST['ma_dich_vu'];
    $so_luong = $_POST['so_luong'];

    $stmt_dv = $conn->prepare("SELECT ten_dich_vu, don_gia FROM dich_vu WHERE ma_dich_vu = ?");
    $stmt_dv->execute([$ma_dv]);
    $dv_info = $stmt_dv->fetch(PDO::FETCH_ASSOC);
    $gia_dv = $dv_info['don_gia'] ?? 0;

    $thanh_tien = $gia_dv * $so_luong;

    try {
        $conn->beginTransaction();
        $conn->prepare("INSERT INTO chi_tiet_dich_vu (dat_phong_id, ma_dich_vu, so_luong, thanh_tien) VALUES (?, ?, ?, ?)")
             ->execute([$dp_id, $ma_dv, $so_luong, $thanh_tien]);
             
        $conn->prepare("UPDATE hoa_don SET tien_dich_vu = tien_dich_vu + ?, tong_tien_thanh_toan = tong_tien_thanh_toan + ? WHERE dat_phong_id = ?")
             ->execute([$thanh_tien, $thanh_tien, $dp_id]);
        
        $conn->commit();
        $success_msg = "Đã thêm dịch vụ '{$dv_info['ten_dich_vu']}' vào hóa đơn!";
        
        // Refresh hóa đơn
        $stmt_hd->execute([$dp_id]);
        $hoadon = $stmt_hd->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $conn->rollBack();
        $error_msg = "Lỗi thêm dịch vụ: " . $e->getMessage();
    }
}

// ==========================================
// XỬ LÝ XÓA DỊCH VỤ (NẾU LỠ TAY THÊM NHẦM)
// ==========================================
if (isset($_GET['delete_service']) && !$is_checked_out) {
    $id_xoa = $_GET['delete_service'];
    try {
        $conn->beginTransaction();
        
        // Lấy số tiền của dịch vụ chuẩn bị xóa
        $stmt_get_tien = $conn->prepare("SELECT thanh_tien FROM chi_tiet_dich_vu WHERE id = ? AND dat_phong_id = ?");
        $stmt_get_tien->execute([$id_xoa, $dp_id]);
        $tien_tru = $stmt_get_tien->fetchColumn();
        
        if ($tien_tru !== false) {
            // Xóa khỏi chi tiết
            $conn->prepare("DELETE FROM chi_tiet_dich_vu WHERE id = ?")->execute([$id_xoa]);
            // Trừ tiền tổng hóa đơn
            $conn->prepare("UPDATE hoa_don SET tien_dich_vu = tien_dich_vu - ?, tong_tien_thanh_toan = tong_tien_thanh_toan - ? WHERE dat_phong_id = ?")
                 ->execute([$tien_tru, $tien_tru, $dp_id]);
                 
            $conn->commit();
            $success_msg = "🗑️ Đã xóa dịch vụ và tính lại tiền hóa đơn!";
            
            // Refresh hóa đơn
            $stmt_hd->execute([$dp_id]);
            $hoadon = $stmt_hd->fetch(PDO::FETCH_ASSOC);
        } else {
            $conn->rollBack();
        }
    } catch(Exception $e) {
        $conn->rollBack();
        $error_msg = "Lỗi xóa dịch vụ: " . $e->getMessage();
    }
}

// ==========================================
// 4. XỬ LÝ CHỐT THANH TOÁN & TRẢ PHÒNG
// ==========================================
if (isset($_POST['btn_checkout'])) {
    $phuong_thuc = $_POST['phuong_thuc_thanh_toan'];
    try {
        $conn->beginTransaction();
        $conn->prepare("UPDATE hoa_don SET phuong_thuc_thanh_toan = ?, trang_thai_thanh_toan = 'Đã thanh toán', ngay_thanh_toan = CURRENT_TIMESTAMP WHERE dat_phong_id = ?")
             ->execute([$phuong_thuc, $dp_id]);
             
        $conn->prepare("UPDATE dat_phong SET trang_thai = 'Hoàn tất trả phòng' WHERE id = ?")
             ->execute([$dp_id]);
             
        $conn->prepare("UPDATE phong SET trang_thai = 'Đang dọn dẹp', so_phong_trong = so_phong_trong + 1 WHERE ma_phong = ?")
             ->execute([$ma_phong]);
        
        $conn->commit();
        $success_msg = "✅ Thanh toán thành công! Khách đã trả phòng.";
        
        $stmt->execute([$booking_id]);
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt_hd->execute([$dp_id]);
        $hoadon = $stmt_hd->fetch(PDO::FETCH_ASSOC);
        $is_checked_out = true;

    } catch (Exception $e) {
        $conn->rollBack();
        $error_msg = "Lỗi thanh toán: " . $e->getMessage();
    }
}

// ==========================================
// 5. LẤY DANH SÁCH DỊCH VỤ ĐÃ DÙNG
// ==========================================
$services_list = $conn->query("SELECT * FROM dich_vu")->fetchAll(PDO::FETCH_ASSOC);
$stmt_ctdv = $conn->prepare("SELECT c.*, d.ten_dich_vu, d.don_gia FROM chi_tiet_dich_vu c JOIN dich_vu d ON c.ma_dich_vu = d.ma_dich_vu WHERE c.dat_phong_id = ?");
$stmt_ctdv->execute([$dp_id]);
$services_used = $stmt_ctdv->fetchAll(PDO::FETCH_ASSOC);

$tong_tien_num = $hoadon['tong_tien_thanh_toan'];
$tong_tien_vnd = number_format($tong_tien_num, 0, ',', '.');

// THÔNG TIN TÀI KHOẢN NGÂN HÀNG (Sửa ở đây)
$bank_id = "MB"; // Mã ngân hàng
$account_no = "0123456789"; // STK
$account_name = "THREE BEAUTIFUL GIRL"; // Tên
$qr_content = urlencode("Thanh toan don " . $booking_id);
$qr_url = "https://img.vietqr.io/image/{$bank_id}-{$account_no}-compact2.png?amount={$tong_tien_num}&addInfo={$qr_content}&accountName=" . urlencode($account_name);

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Thanh Toán & Hóa Đơn - Admin</title>
    <style>
        body { margin: 0; font-family: Arial, sans-serif; background-color: #f4f7f6; display: flex; height: 100vh; overflow: hidden; }
        .sidebar { width: 260px; background-color: #1a1a1a; color: white; display: flex; flex-direction: column; box-shadow: 2px 0 10px rgba(0,0,0,0.1); z-index: 100; position: fixed; height: 100vh;}
        .sidebar-header { padding: 30px 20px; text-align: center; border-bottom: 1px solid #333; }
        .sidebar-header h2 { color: #f1c40f; margin: 0; font-size: 20px; font-weight: bold; text-transform: uppercase; }
        .sidebar-menu { list-style: none; padding: 20px 0; margin: 0; overflow-y: auto; flex: 1;}
        .sidebar-menu li { margin-bottom: 5px; }
        .sidebar-menu a { display: flex; align-items: center; padding: 12px 25px; color: #999; text-decoration: none; font-size: 15px; transition: 0.3s; }
        .sidebar-menu a:hover, .sidebar-menu a.active { color: white; background-color: #333; border-left: 4px solid #f1c40f; }
        .sidebar-menu .menu-category { color: #555; padding: 15px 25px 5px 25px; text-transform: uppercase; font-size: 11px; font-weight: bold; }
        .sidebar-menu .icon { margin-right: 15px; font-size: 18px; }

        .main-content { margin-left: 260px; flex: 1; display: flex; flex-direction: column; overflow-y: auto; }
        .topbar { background-color: #ffffff; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 10px rgba(0,0,0,0.05); position: sticky; top: 0; z-index: 99;}
        .topbar h1 { margin: 0; font-size: 20px; color: #333; font-weight: bold; text-transform: uppercase;}
        .dashboard-container { padding: 30px; }
        
        .layout-grid { display: grid; grid-template-columns: 1fr 1.2fr; gap: 30px; align-items: start;}
        .card { background: #fff; padding: 25px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); }
        .card-header { margin-bottom: 20px; border-bottom: 2px solid #f1f1f1; padding-bottom: 10px; font-size: 18px; font-weight: bold; color: #333;}
        
        .info-row { display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 14px; border-bottom: 1px dashed #eee; padding-bottom: 8px;}
        .info-row strong { color: #555; }
        
        .total-box { background: #fdfefe; border: 2px solid #2ecc71; border-radius: 8px; padding: 20px; text-align: center; margin-top: 20px;}
        .total-price { font-size: 32px; color: #e74c3c; font-weight: bold; margin: 10px 0;}
        
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-size: 12px; font-weight: bold; color: #555; margin-bottom: 5px; text-transform: uppercase;}
        .form-group select, .form-group input { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px; font-family: Arial; box-sizing: border-box;}
        
        .btn-green { background: #27ae60; color: white; border: none; padding: 15px; border-radius: 6px; cursor: pointer; font-weight: bold; width: 100%; font-size: 16px; text-transform: uppercase; transition: 0.3s;}
        .btn-green:hover { background: #219150; }
        .btn-blue { background: #2980b9; color: white; border: none; padding: 15px; border-radius: 6px; cursor: pointer; font-weight: bold; width: 100%; font-size: 16px; text-transform: uppercase; transition: 0.3s; margin-top: 10px; display: block; text-align: center; text-decoration: none;}
        .btn-blue:hover { background: #1c5980; }
        .btn-gold { background: #f1c40f; color: #1a1a1a; border: none; padding: 10px; border-radius: 4px; cursor: pointer; font-weight: bold; width: 100%; font-size: 13px;}
        
        table { width: 100%; border-collapse: collapse; margin-top: 15px;}
        th, td { text-align: left; padding: 10px; font-size: 13px; border-bottom: 1px solid #eee; vertical-align: middle;}
        th { color: #888; text-transform: uppercase; }
        
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 6px; font-weight: bold;}
        .alert-success { background: #d4efdf; color: #27ae60; border-left: 4px solid #2ecc71;}
        .alert-error { background: #fadbd8; color: #c0392b; border-left: 4px solid #e74c3c;}
        
        .badge-done { display: inline-block; padding: 8px 15px; background: #27ae60; color: white; font-weight: bold; border-radius: 20px; font-size: 14px;}
        
        .payment-box { margin-top: 15px; border: 1px dashed #3498db; padding: 15px; border-radius: 8px; background: #ebf5fb; }
        .qr-img { max-width: 200px; border-radius: 8px; border: 3px solid #fff; box-shadow: 0 4px 10px rgba(0,0,0,0.1); margin-top: 10px;}

        /* ================= CSS IN HÓA ĐƠN ================= */
        #print-invoice { display: none; }
        @media print {
            body * { visibility: hidden; }
            #print-invoice, #print-invoice * { visibility: visible; }
            #print-invoice { display: block; position: absolute; left: 0; top: 0; width: 100%; font-family: monospace; color: #000; padding: 20px; }
            .sidebar, .topbar, .dashboard-container { display: none !important; }
            
            .bill-header { text-align: center; margin-bottom: 20px; border-bottom: 2px dashed #000; padding-bottom: 10px;}
            .bill-header h1 { font-size: 24px; margin: 0;}
            .bill-header p { font-size: 12px; margin: 2px 0;}
            .bill-info { margin-bottom: 20px; font-size: 14px; }
            .bill-info p { margin: 5px 0;}
            .bill-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 14px;}
            .bill-table th, .bill-table td { border-bottom: 1px solid #000; padding: 8px 5px; text-align: left;}
            .bill-total { text-align: right; font-size: 18px; font-weight: bold; border-top: 2px dashed #000; padding-top: 10px;}
            .bill-footer { text-align: center; margin-top: 30px; font-size: 12px; font-style: italic;}
        }
    </style>
    
    <script>
        const tongTienPhaiTra = <?= $tong_tien_num ?>;

        function togglePaymentFlow() {
            var pttt = document.getElementById("phuong_thuc").value;
            var qrBox = document.getElementById("qr-container");
            var cashBox = document.getElementById("cash-container");
            var btnCheckout = document.getElementById("btn-checkout-confirm");
            
            if (pttt === "Chuyển khoản") {
                qrBox.style.display = "block";
                cashBox.style.display = "none";
                btnCheckout.disabled = false;
                btnCheckout.style.opacity = "1";
            } else {
                qrBox.style.display = "none";
                cashBox.style.display = "block";
                calculateChange(); 
            }
        }
        
        function calculateChange() {
            var inputEle = document.getElementById("tien_khach_dua");
            var rawValue = inputEle.value.replace(/\D/g,''); 
            if(rawValue === '') rawValue = 0;
            
            var tienKhachDua = parseInt(rawValue);
            var change = tienKhachDua - tongTienPhaiTra;
            
            var displayEle = document.getElementById("tien_thua_display");
            var btnCheckout = document.getElementById("btn-checkout-confirm");
            
            if(change < 0) {
                displayEle.innerHTML = "Khách đưa chưa đủ tiền!";
                displayEle.style.color = "#e74c3c";
                btnCheckout.disabled = true;
                btnCheckout.style.opacity = "0.5";
                btnCheckout.title = "Vui lòng nhập đủ tiền khách đưa trước khi chốt đơn";
            } else {
                displayEle.innerHTML = change.toLocaleString('vi-VN') + " đ";
                displayEle.style.color = "#27ae60";
                btnCheckout.disabled = false;
                btnCheckout.style.opacity = "1";
                btnCheckout.title = "";
            }
            
            if(tienKhachDua > 0) {
                inputEle.value = tienKhachDua.toLocaleString('vi-VN');
            } else {
                inputEle.value = '';
            }
        }
        
        function printInvoice() {
            window.print();
        }

        window.onload = function() {
            if(!<?= $is_checked_out ? 'true' : 'false' ?>) {
                togglePaymentFlow();
            }
        };
    </script>
</head>
<body>

    <aside class="sidebar">
        <div class="sidebar-header"><h2>Three Beautiful Girl</h2></div>
        <ul class="sidebar-menu">
            <li><a href="admin_dashboard.php"><span class="icon">📈</span> Tổng quan</a></li>
            <li class="menu-category">LỄ TÂN & GIAO DỊCH</li>
            <li><a href="manage_bookings.php" class="active"><span class="icon">🛎️</span> Quản lý Đặt phòng</a></li>
            <li><a href="manage_users.php"><span class="icon">👥</span> Hồ sơ Khách hàng</a></li>
            <li><a href="invoices.php"><span class="icon">🧾</span> Hóa đơn & Thu ngân</a></li>
            <li class="menu-category">QUẢN LÝ HỆ THỐNG</li>
            <li><a href="rooms.php"><span class="icon">🛏️</span> Quản lý phòng</a></li>
            <li><a href="manage_staff.php"><span class="icon">👷‍♂️</span> Quản lý Nhân sự</a></li>
            <li><a href="manage_services.php"><span class="icon">✨</span> Quản Lý Dịch Vụ</a></li>
            <li class="menu-category">BÁO CÁO</li>
            <li><a href="reports.php"><span class="icon">📊</span> Báo cáo Doanh thu</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <h1>THANH TOÁN & TRẢ PHÒNG</h1>
            <div>
                <a href="manage_bookings.php" style="color: #666; text-decoration: none; font-weight: bold; margin-right: 20px;">⬅ Quay lại danh sách</a>
                <a href="logout.php" style="color: #e74c3c; text-decoration: none; font-weight: bold;">Đăng xuất</a>
            </div>
        </header>

        <div class="dashboard-container">
            <?php if($success_msg): ?> <div class="alert alert-success"><?= $success_msg ?></div> <?php endif; ?>
            <?php if($error_msg): ?> <div class="alert alert-error"><?= $error_msg ?></div> <?php endif; ?>

            <div class="layout-grid">
                
                <div class="card" style="border-top: 4px solid #f1c40f;">
                    <div class="card-header">✨ Quản lý Dịch vụ phát sinh</div>
                    
                    <?php if (!$is_checked_out): ?>
                    <form method="POST" style="background: #fdfefe; padding: 15px; border: 1px solid #eee; border-radius: 6px; margin-bottom: 20px;">
                        <div style="display: flex; gap: 10px;">
                            <div class="form-group" style="flex: 2; margin: 0;">
                                <label>Thêm dịch vụ khách đã dùng</label>
                                <select name="ma_dich_vu" required>
                                    <?php foreach ($services_list as $sv): ?>
                                        <option value="<?= $sv['ma_dich_vu'] ?>"><?= $sv['ten_dich_vu'] ?> - <?= number_format($sv['don_gia'],0,',','.') ?>đ</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group" style="flex: 1; margin: 0;">
                                <label>Số lượng</label>
                                <input type="number" name="so_luong" value="1" min="1" required>
                            </div>
                        </div>
                        <button type="submit" name="btn_add_service" class="btn-gold" style="margin-top: 10px;">+ Cộng vào hóa đơn</button>
                    </form>
                    <?php endif; ?>

                    <table>
                        <thead>
                            <tr>
                                <th>Dịch Vụ</th>
                                <th>SL</th>
                                <th>Thành Tiền</th>
                                <?php if (!$is_checked_out): ?>
                                <th style="text-align:center;">Hủy</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($services_used) > 0): ?>
                                <?php foreach($services_used as $u): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($u['ten_dich_vu']) ?></strong></td>
                                    <td>x<?= $u['so_luong'] ?></td>
                                    <td style="color: #d35400; font-weight: bold;"><?= number_format($u['thanh_tien'], 0, ',', '.') ?>đ</td>
                                    <?php if (!$is_checked_out): ?>
                                    <td style="text-align:center;">
                                        <a href="checkout.php?booking_id=<?= urlencode($booking_id) ?>&delete_service=<?= $u['id'] ?>" onclick="return confirm('Bạn có chắc chắn muốn XÓA món này khỏi hóa đơn?')" style="color: #e74c3c; text-decoration: none; font-size: 16px;" title="Xóa bỏ">🗑️</a>
                                    </td>
                                    <?php endif; ?>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="<?= $is_checked_out ? '3' : '4' ?>" style="text-align: center; color: #999;">Khách chưa sử dụng dịch vụ phát sinh nào.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="card" style="border-top: 4px solid #3498db;">
                    <div class="card-header">🧾 CHI TIẾT HÓA ĐƠN: <?= htmlspecialchars($hoadon['ma_hoa_don']) ?></div>
                    
                    <div class="info-row"><strong>Phòng:</strong> <span style="color:#2980b9; font-weight:bold; font-size:16px;"><?= htmlspecialchars($booking['ten_phong']) ?></span></div>
                    <div class="info-row"><strong>Khách hàng:</strong> <span><?= htmlspecialchars($booking['ho_ten']) ?> (<?= htmlspecialchars($booking['so_dien_thoai']) ?>)</span></div>
                    <div class="info-row"><strong>Thời gian:</strong> <span><?= $ngay_den_str ?> ➔ <?= $ngay_di_str ?></span></div>

                    <div class="total-box">
                        <div class="info-row" style="border:none;"><strong>Tiền phòng:</strong> <span><?= number_format($hoadon['tien_phong'], 0, ',', '.') ?> đ</span></div>
                        <div class="info-row" style="border:none;"><strong>Tiền dịch vụ:</strong> <span><?= number_format($hoadon['tien_dich_vu'], 0, ',', '.') ?> đ</span></div>
                        
                        <div style="border-top: 2px solid #eee; margin: 15px 0;"></div>
                        <span style="color: #888; font-weight: bold; text-transform: uppercase;">KHÁCH CẦN THANH TOÁN</span>
                        <div class="total-price" id="total_price_vnd"><?= $tong_tien_vnd ?> đ</div>
                        
                        <?php if (!$is_checked_out): ?>
                            <form method="POST" style="margin-top: 20px;">
                                <div class="form-group" style="text-align: left;">
                                    <label>Khách thanh toán bằng:</label>
                                    <select name="phuong_thuc_thanh_toan" id="phuong_thuc" onchange="togglePaymentFlow()">
                                        <option value="Tiền mặt" selected>Tiền mặt</option>
                                        <option value="Chuyển khoản">Chuyển khoản (Quét mã QR)</option>
                                    </select>
                                </div>
                                
                                <div id="cash-container" class="payment-box">
                                    <div class="form-group" style="text-align: left; margin-bottom: 15px;">
                                        <label style="color: #2980b9;">Tiền khách đưa (VNĐ):</label>
                                        <input type="text" id="tien_khach_dua" placeholder="Nhập số tiền khách đưa..." oninput="calculateChange()" autocomplete="off" style="font-size: 16px; font-weight: bold; color: #2980b9;">
                                    </div>
                                    <div style="display: flex; justify-content: space-between; align-items: center; background: #fff; padding: 12px; border-radius: 6px; border: 1px solid #ccc;">
                                        <span style="font-size: 13px; font-weight: bold; color: #555;">Tiền thừa trả khách:</span>
                                        <span id="tien_thua_display" style="font-size: 18px; font-weight: bold; color: #e74c3c;">0 đ</span>
                                    </div>
                                </div>

                                <div id="qr-container" class="payment-box" style="display: none; text-align: center;">
                                    <p style="margin-top:0; font-size:14px; font-weight:bold; color:#2980b9;">Mời khách quét mã VietQR</p>
                                    <img src="<?= $qr_url ?>" alt="QR Code" class="qr-img">
                                    <p style="margin-bottom:0; margin-top:10px; font-size:13px; color:#555;">Tự động điền số tiền: <strong style="color: #e74c3c;"><?= $tong_tien_vnd ?>đ</strong></p>
                                </div>

                                <button type="submit" name="btn_checkout" id="btn-checkout-confirm" class="btn-green" style="margin-top: 15px;" onclick="return confirm('Xác nhận thu tiền và check-out cho khách?')">
                                    ✔️ CHỐT THANH TOÁN & TRẢ PHÒNG
                                </button>
                            </form>
                        <?php else: ?>
                            <div style="margin-top: 20px;">
                                <span class="badge-done">✔️ ĐÃ TRẢ PHÒNG & XUẤT HÓA ĐƠN</span>
                                <button type="button" class="btn-blue" onclick="printInvoice()">🖨️ IN BIÊN LAI (GỬI KHÁCH)</button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>
    </main>

    <div id="print-invoice">
        <div class="bill-header">
            <h1>HOTEL THREE BEAUTIFUL GIRL</h1>
            <p>Địa chỉ: 123 Đường Tình Yêu, Thành phố Mộng Mơ</p>
            <p>Hotline: 0988.888.888 - Website: threegirls.vn</p>
            <h2 style="margin-top: 15px;">HÓA ĐƠN THANH TOÁN</h2>
        </div>
        
        <div class="bill-info">
            <p><strong>Mã HĐ:</strong> <?= htmlspecialchars($hoadon['ma_hoa_don']) ?> (ĐP: <?= htmlspecialchars($booking['booking_id']) ?>)</p>
            <p><strong>Ngày in:</strong> <?= date('d/m/Y H:i:s') ?></p>
            <p><strong>Khách hàng:</strong> <?= htmlspecialchars($booking['ho_ten']) ?></p>
            <p><strong>Phòng:</strong> <?= htmlspecialchars($booking['ten_phong']) ?> (Vào: <?= $ngay_den_str ?> - Ra: <?= $ngay_di_str ?>)</p>
            <p><strong>Thu ngân:</strong> <?= htmlspecialchars($_SESSION['user_id'] ?? 'Admin') ?></p>
        </div>
        
        <table class="bill-table">
            <thead>
                <tr>
                    <th>Sản phẩm / Dịch vụ</th>
                    <th style="text-align: center;">SL</th>
                    <th style="text-align: right;">Thành tiền</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Tiền thuê phòng</td>
                    <td style="text-align: center;">-</td>
                    <td style="text-align: right;"><?= number_format($hoadon['tien_phong'], 0, ',', '.') ?></td>
                </tr>
                <?php foreach($services_used as $u): ?>
                <tr>
                    <td><?= htmlspecialchars($u['ten_dich_vu']) ?></td>
                    <td style="text-align: center;"><?= $u['so_luong'] ?></td>
                    <td style="text-align: right;"><?= number_format($u['thanh_tien'], 0, ',', '.') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div class="bill-total">
            TỔNG CỘNG: <?= $tong_tien_vnd ?> VNĐ
        </div>
        <p style="text-align: right; font-size: 14px; margin-top: 5px;">(Đã thanh toán bằng: <?= htmlspecialchars($hoadon['phuong_thuc_thanh_toan']) ?>)</p>
        
        <div class="bill-footer">
            <p>Cảm ơn quý khách và hẹn gặp lại!</p>
            <p>Thank you & See you again!</p>
            <p style="margin-top: 30px;">.........................................</p>
            <p>(Chữ ký khách hàng)</p>
        </div>
    </div>

</body>
</html>