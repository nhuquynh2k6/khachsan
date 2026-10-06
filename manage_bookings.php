<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] != 1) {
    header("Location: trangchu.php");
    exit();
}
include 'config.php';

$success_msg = ""; $error_msg = "";
$current_admin_id = $_SESSION['user_id'] ?? 'admin01'; 

// ==========================================
// 1. XỬ LÝ KHÁCH ĐẶT TẠI QUẦY
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'book_at_desk') {
    try {
        $conn->beginTransaction();
        $cccd = trim($_POST['cccd']); $ho_ten = trim($_POST['ho_ten']); $sdt = trim($_POST['sdt']);
        $ma_phong = $_POST['ma_phong']; $ngay_den = $_POST['ngay_den']; $ngay_di = $_POST['ngay_di'];
        $so_khach = $_POST['so_khach'] ?? 1; $trang_thai_don = $_POST['trang_thai_don'];

        $stmt_kh = $conn->prepare("SELECT * FROM khach_hang WHERE cccd_ho_chieu = ?"); $stmt_kh->execute([$cccd]);
        if ($stmt_kh->rowCount() == 0) {
            $pass_default = password_hash('123456', PASSWORD_DEFAULT);
            $conn->prepare("INSERT INTO khach_hang (cccd_ho_chieu, ho_ten, so_dien_thoai, mat_khau, ma_khach_hang) VALUES (?, ?, ?, ?, '')")->execute([$cccd, $ho_ten, $sdt, $pass_default]);
        }

        $phong_info = $conn->prepare("SELECT ma_loai, gia_phong, so_phong_trong FROM phong WHERE ma_phong = ? FOR UPDATE");
        $phong_info->execute([$ma_phong]); $phong_info = $phong_info->fetch(PDO::FETCH_ASSOC);
        if (!$phong_info || $phong_info['so_phong_trong'] < 1) throw new Exception("Phòng không trống!");

        $tong_tien = max(1, round((strtotime($ngay_di) - strtotime($ngay_den)) / 86400)) * $phong_info['gia_phong'];
        $booking_id = 'BK-' . date('Ymd') . '-' . rand(1000, 9999);
        
        $check_nv = $conn->prepare("SELECT ma_nv FROM nhan_vien WHERE ma_nv = ?"); $check_nv->execute([$current_admin_id]);
        $nv_id_safe = ($check_nv->rowCount() > 0) ? $current_admin_id : 'admin01';

        $conn->prepare("INSERT INTO dat_phong (booking_id, khach_hang_cccd, ma_nv, ma_phong, ma_loai, ngay_den, ngay_di, so_khach, tong_tien_du_kien, trang_thai) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")->execute([$booking_id, $cccd, $nv_id_safe, $ma_phong, $phong_info['ma_loai'], $ngay_den, $ngay_di, $so_khach, $tong_tien, $trang_thai_don]);
        $dat_phong_id = $conn->lastInsertId();

        $ma_hd = 'HD' . date('Ymd') . rand(100, 999);
        $tt_hd = ($trang_thai_don == 'Đã thanh toán đầy đủ') ? 'Đã thanh toán' : 'Chưa thanh toán';
        $conn->prepare("INSERT INTO hoa_don (ma_hoa_don, dat_phong_id, ma_nv, tien_phong, tong_tien_thanh_toan, phuong_thuc_thanh_toan, trang_thai_thanh_toan) VALUES (?, ?, ?, ?, ?, 'Tiền mặt', ?)")->execute([$ma_hd, $dat_phong_id, $nv_id_safe, $tong_tien, $tong_tien, $tt_hd]);

        if ($trang_thai_don == 'Đã thanh toán đầy đủ' && date('Y-m-d', strtotime($ngay_den)) <= date('Y-m-d')) {
            $conn->prepare("UPDATE phong SET so_phong_trong = so_phong_trong - 1, trang_thai = 'Đang lưu trú' WHERE ma_phong = ?")->execute([$ma_phong]);
        } else {
            $conn->prepare("UPDATE phong SET so_phong_trong = so_phong_trong - 1 WHERE ma_phong = ?")->execute([$ma_phong]);
        }
        $conn->commit(); $success_msg = "Ghi nhận khách tại quầy thành công!";
    } catch (Exception $e) { $conn->rollBack(); $error_msg = "Lỗi: " . $e->getMessage(); }
}

// ==========================================
// 2. XỬ LÝ CẬP NHẬT TRẠNG THÁI & GHI LỊCH SỬ HỦY
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update_status') {
    $bk_id = $_POST['booking_id']; 
    $new_status = $_POST['trang_thai'];
    
    try {
        $conn->beginTransaction();
        
        // A. Lấy thông tin đơn hiện tại
        $stmt_old = $conn->prepare("SELECT id, trang_thai, ma_phong, tong_tien_du_kien FROM dat_phong WHERE booking_id = ?");
        $stmt_old->execute([$bk_id]);
        $dp_info = $stmt_old->fetch(PDO::FETCH_ASSOC);
        
        if ($dp_info && $dp_info['trang_thai'] != $new_status) {
            $old_status = $dp_info['trang_thai'];
            $ma_phong = $dp_info['ma_phong'];
            $dp_id = $dp_info['id'];

            // Cập nhật trạng thái
            $conn->prepare("UPDATE dat_phong SET trang_thai = ? WHERE booking_id = ?")->execute([$new_status, $bk_id]);
            
            // B. XỬ LÝ NẾU CHUYỂN SANG TRẠNG THÁI "ĐÃ HỦY"
            if ($new_status == 'Đã hủy' && $old_status != 'Đã hủy') {
                // 1. Trả phòng về kho
                $conn->prepare("UPDATE phong SET so_phong_trong = so_phong_trong + 1, trang_thai = 'Trống' WHERE ma_phong = ?")->execute([$ma_phong]);
                // 2. Đổi trạng thái hóa đơn
                $conn->prepare("UPDATE hoa_don SET trang_thai_thanh_toan = 'Đã hủy' WHERE dat_phong_id = ?")->execute([$dp_id]);
                
                // 3. GHI NHẬN VÀO BẢNG LỊCH SỬ HỦY
                $ly_do = "Hủy bởi Lễ tân/Quản lý";
                $phi_huy = 0;
                $tien_hoan = 0;
                $sql_ls = "INSERT INTO lich_su_huy (dat_phong_id, thoi_diem_huy, phi_huy, tien_hoan_tra, ly_do) VALUES (?, CURRENT_TIMESTAMP, ?, ?, ?)";
                $conn->prepare($sql_ls)->execute([$dp_id, $phi_huy, $tien_hoan, $ly_do]);
            } 
            elseif ($old_status == 'Đã hủy' && $new_status != 'Đã hủy') {
                // Khách đổi ý khôi phục đơn -> trừ kho lại (và xóa lịch sử hủy để tránh rác DB)
                $conn->prepare("UPDATE phong SET so_phong_trong = so_phong_trong - 1 WHERE ma_phong = ?")->execute([$ma_phong]);
                $conn->prepare("DELETE FROM lich_su_huy WHERE dat_phong_id = ?")->execute([$dp_id]);
            }
            
            // C. XỬ LÝ HÓA ĐƠN KHI THANH TOÁN
            if (in_array($new_status, ['Đã thanh toán đầy đủ', 'Hoàn tất trả phòng', 'Đã đặt cọc'])) {
                $tt_hd = ($new_status == 'Đã đặt cọc') ? 'Chưa thanh toán' : 'Đã thanh toán';
                $stmt_check_hd = $conn->prepare("SELECT ma_hoa_don FROM hoa_don WHERE dat_phong_id = ?"); 
                $stmt_check_hd->execute([$dp_id]);
                
                if ($stmt_check_hd->rowCount() > 0) {
                    $conn->prepare("UPDATE hoa_don SET trang_thai_thanh_toan = ? WHERE dat_phong_id = ?")->execute([$tt_hd, $dp_id]);
                } else {
                    $check_nv = $conn->prepare("SELECT ma_nv FROM nhan_vien WHERE ma_nv = ?"); 
                    $check_nv->execute([$current_admin_id]);
                    $nv_id_safe = ($check_nv->rowCount() > 0) ? $current_admin_id : 'admin01';
                    
                    $conn->prepare("INSERT INTO hoa_don (ma_hoa_don, dat_phong_id, ma_nv, tien_phong, tong_tien_thanh_toan, phuong_thuc_thanh_toan, trang_thai_thanh_toan) VALUES (?, ?, ?, ?, ?, 'Tiền mặt', ?)")->execute(['HD'.date('Ymd').rand(100,999), $dp_id, $nv_id_safe, $dp_info['tong_tien_du_kien'], $dp_info['tong_tien_du_kien'], $tt_hd]);
                }
            }
        }

        $conn->commit(); 
        $success_msg = "Cập nhật thành công!";
    } catch(Exception $e) { 
        $conn->rollBack(); 
        $error_msg = "Lỗi cập nhật: " . $e->getMessage(); 
    }
}

$bookings = $conn->query("SELECT dp.*, kh.ho_ten, kh.so_dien_thoai, p.ten_phong FROM dat_phong dp JOIN khach_hang kh ON dp.khach_hang_cccd = kh.cccd_ho_chieu JOIN phong p ON dp.ma_phong = p.ma_phong ORDER BY dp.ngay_dat DESC")->fetchAll(PDO::FETCH_ASSOC);
$available_rooms = $conn->query("SELECT p.ma_phong, p.ten_phong, lp.ten_loai, p.gia_phong FROM phong p JOIN loai_phong lp ON p.ma_loai = lp.ma_loai WHERE p.so_phong_trong > 0 AND p.trang_thai != 'Bảo trì'")->fetchAll(PDO::FETCH_ASSOC);

// GỌI GIAO DIỆN CHUNG
$page_title = "QUẢN LÝ ĐẶT PHÒNG";
include 'admin_header.php';
?>

<style>
    .bg-pending { background-color: #fcf3cf; color: #d35400; } .bg-success { background-color: #d4efdf; color: #2ecc71; } .bg-danger { background-color: #fadbd8; color: #c0392b; } .bg-info { background-color: #d6eaf8; color: #2980b9; }
    .select-status { padding: 6px; border: 1px solid #ccc; border-radius: 4px; outline: none; font-size: 13px;}
    .btn-save-sm { background: #34495e; color: white; border: none; padding: 6px 10px; border-radius: 4px; cursor: pointer; margin-left: 5px; font-size: 12px;}
    .origin-web { color: #3498db; font-weight: bold; font-size: 12px;} .origin-desk { color: #9b59b6; font-weight: bold; font-size: 12px;}
    .modal-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 1000; align-items: center; justify-content: center; }
    .modal-box { background: white; padding: 30px; border-radius: 12px; width: 600px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
    .modal-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #f1c40f; padding-bottom: 15px; margin-bottom: 20px;}
    .btn-close { background: none; border: none; font-size: 24px; cursor: pointer; color: #888; }
</style>

<?php if($success_msg): ?> <div class="alert alert-success">✔️ <?= $success_msg ?></div> <?php endif; ?>
<?php if($error_msg): ?> <div class="alert alert-error">❌ <?= $error_msg ?></div> <?php endif; ?>

<div class="table-section">
    <div class="table-header">
        <h3>🛎️ Danh sách Giao dịch Đặt phòng</h3>
        <button class="btn-submit" onclick="document.getElementById('modal-offline-booking').style.display='flex'">+ Ghi nhận khách tại quầy</button>
    </div>

    <table>
        <thead><tr><th>Mã Đơn</th><th>Nguồn</th><th>Khách Hàng (SĐT)</th><th>Phòng</th><th>Check-in / Out</th><th>Tổng tiền</th><th>Trạng Thái</th><th>Thao Tác</th></tr></thead>
        <tbody>
            <?php foreach($bookings as $bk): 
                $is_web = strpos($bk['booking_id'], 'BK') === 0;
                $origin_html = $is_web ? "<span class='origin-web'>🌐 WEBSITE</span>" : "<span class='origin-desk'>🛎️ QUẦY</span>";
                $tt = mb_strtolower(trim($bk['trang_thai']), 'UTF-8');
                $badge = 'bg-info'; if (strpos($tt, 'tạm giữ') !== false) $badge = 'bg-pending'; elseif (strpos($tt, 'đã thanh toán') !== false || strpos($tt, 'hoàn tất') !== false) $badge = 'bg-success'; elseif (strpos($tt, 'hủy') !== false) $badge = 'bg-danger';
            ?>
            <tr>
                <td><strong><?= htmlspecialchars($bk['booking_id']) ?></strong></td>
                <td><?= $origin_html ?></td>
                <td><?= htmlspecialchars($bk['ho_ten']) ?><br><small><?= htmlspecialchars($bk['so_dien_thoai']) ?></small></td>
                <td><strong style="color: #2c3e50;"><?= htmlspecialchars($bk['ten_phong']) ?></strong></td>
                <td style="font-size: 12px;"><strong style="color:#2ecc71;">IN:</strong> <?= date('d/m/Y', strtotime($bk['ngay_den'])) ?><br><strong style="color:#e74c3c;">OUT:</strong> <?= date('d/m/Y', strtotime($bk['ngay_di'])) ?></td>
                <td style="color:#d35400; font-weight:bold;"><?= number_format($bk['tong_tien_du_kien'], 0, ',', '.') ?>đ</td>
                <td><span class="status-badge <?= $badge ?>"><?= htmlspecialchars($bk['trang_thai']) ?></span></td>
                <td>
                    <form method="POST" style="display: flex; align-items: center;">
                        <input type="hidden" name="action" value="update_status"><input type="hidden" name="booking_id" value="<?= htmlspecialchars($bk['booking_id']) ?>">
                        <select name="trang_thai" class="select-status">
                            <option value="Tạm giữ thanh toán" <?= $bk['trang_thai']=='Tạm giữ thanh toán'?'selected':'' ?>>Tạm giữ</option>
                            <option value="Đã đặt cọc" <?= $bk['trang_thai']=='Đã đặt cọc'?'selected':'' ?>>Đã đặt cọc</option>
                            <option value="Đã thanh toán đầy đủ" <?= $bk['trang_thai']=='Đã thanh toán đầy đủ'?'selected':'' ?>>Đã thanh toán/Check-in</option>
                            <option value="Hoàn tất trả phòng" <?= $bk['trang_thai']=='Hoàn tất trả phòng'?'selected':'' ?>>Hoàn tất trả phòng</option>
                            <option value="Đã hủy" <?= $bk['trang_thai']=='Đã hủy'?'selected':'' ?>>Hủy đơn</option>
                        </select>
                        <button type="submit" class="btn-save-sm">Lưu</button>
                    </form>
                    <a href="checkout.php?booking_id=<?= urlencode($bk['booking_id']) ?>" style="display:inline-block; margin-top:8px; padding: 4px 8px; background: #27ae60; color: white; text-decoration: none; font-size: 11px; font-weight: bold; border-radius: 4px;">💳 Thanh toán / Trả phòng</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="modal-overlay" id="modal-offline-booking">
    <div class="modal-box">
        <div class="modal-header"><h2>🛎️ GHI NHẬN KHÁCH TẠI QUẦY</h2><button class="btn-close" onclick="document.getElementById('modal-offline-booking').style.display='none'">&times;</button></div>
        <form method="POST">
            <input type="hidden" name="action" value="book_at_desk">
            <div class="form-grid">
                <div class="form-group"><label>CCCD/Hộ chiếu *</label><input type="text" name="cccd" required></div>
                <div class="form-group"><label>Họ và Tên *</label><input type="text" name="ho_ten" required></div>
                <div class="form-group"><label>SĐT *</label><input type="text" name="sdt" required></div>
                <div class="form-group"><label>Số lượng khách</label><input type="number" name="so_khach" value="1"></div>
                <div class="form-group full-width"><label>Chọn Phòng (Trống) *</label>
                    <select name="ma_phong" required><option value="">-- Chọn phòng --</option><?php foreach($available_rooms as $rm): ?><option value="<?= htmlspecialchars($rm['ma_phong']) ?>"><?= htmlspecialchars($rm['ten_phong']) ?> - <?= number_format($rm['gia_phong'],0,',','.') ?>đ</option><?php endforeach; ?></select>
                </div>
                <div class="form-group full-width"><label>Thu tiền *</label><select name="trang_thai_don" required><option value="Tạm giữ thanh toán">Chưa thu tiền (Tạm giữ)</option><option value="Đã thanh toán đầy đủ">Đã thu tiền & Giao phòng</option></select></div>
                <div class="form-group"><label>Ngày Vào (IN) *</label><input type="date" name="ngay_den" required value="<?= date('Y-m-d') ?>"></div>
                <div class="form-group"><label>Ngày Ra (OUT) *</label><input type="date" name="ngay_di" required value="<?= date('Y-m-d', strtotime('+1 day')) ?>"></div>
            </div>
            <button type="submit" class="btn-submit full-width" style="margin-top: 15px; width:100%;">TẠO ĐƠN & XUẤT HÓA ĐƠN</button>
        </form>
    </div>
</div>

<?php include 'admin_footer.php'; ?>