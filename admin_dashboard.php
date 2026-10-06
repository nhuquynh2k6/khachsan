<?php
session_start();
// GIỮ NGUYÊN KIỂM TRA ĐĂNG NHẬP CŨ CỦA BỒ
if (!isset($_SESSION['role']) || $_SESSION['role'] != 1) {
    header("Location: trangchu.php");
    exit();
}
include 'config.php';

// ==========================================
// LOGIC KÉO DỮ LIỆU TỪ DATABASE (CỦA BỒ)
// ==========================================
$doanh_thu = 0; $booking_today = 0;
$tong_phong = 0; $phong_trong = 0; $phong_luu_tru = 0; $phong_don_dep = 0; $phong_bao_tri = 0;
$recent_bookings = [];

try {
    $stmt_dt = $conn->query("SELECT SUM(tong_tien_du_kien) as total FROM dat_phong WHERE MONTH(ngay_den) = MONTH(CURRENT_DATE()) AND YEAR(ngay_den) = YEAR(CURRENT_DATE()) AND trang_thai != 'Đã hủy'");
    if($row = $stmt_dt->fetch(PDO::FETCH_ASSOC)) $doanh_thu = $row['total'] ?? 0;

    $stmt_bk = $conn->query("SELECT COUNT(*) as sl FROM dat_phong WHERE DATE(ngay_den) = CURRENT_DATE() AND trang_thai != 'Đã hủy'");
    if($row = $stmt_bk->fetch(PDO::FETCH_ASSOC)) $booking_today = $row['sl'] ?? 0;

    $stmt_phong = $conn->query("SELECT trang_thai, COUNT(*) as sl FROM phong GROUP BY trang_thai");
    while($row = $stmt_phong->fetch(PDO::FETCH_ASSOC)) {
        $tong_phong += $row['sl'];
        $tt = trim($row['trang_thai']);
        if($tt == 'Trống') $phong_trong = $row['sl'];
        elseif($tt == 'Đang lưu trú') $phong_luu_tru = $row['sl'];
        elseif($tt == 'Đang dọn dẹp') $phong_don_dep = $row['sl'];
        elseif($tt == 'Bảo trì' || $tt == 'Đang bảo trì') $phong_bao_tri = $row['sl'];
    }

    $recent_bookings = $conn->query("SELECT dp.booking_id, kh.ho_ten, lp.ten_loai, dp.ngay_den, dp.trang_thai FROM dat_phong dp JOIN khach_hang kh ON dp.khach_hang_cccd = kh.cccd_ho_chieu JOIN phong p ON dp.ma_phong = p.ma_phong LEFT JOIN loai_phong lp ON p.ma_loai = lp.ma_loai ORDER BY dp.ngay_den DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

$ty_le_lap_day = ($tong_phong > 0) ? round(($phong_luu_tru / $tong_phong) * 100) : 0;
$phong_can_xu_ly = $phong_don_dep + $phong_bao_tri;

// GỌI GIAO DIỆN CHUNG VÀO ĐÂY LÀ XONG
$page_title = "BẢNG ĐIỀU KHIỂN TỔNG QUAN";
include 'admin_header.php';
?>

<style>
    /* CSS dành riêng cho các khối thống kê */
    .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px; }
    .stat-card { background-color: #ffffff; padding: 25px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); display: flex; flex-direction: column; position: relative;}
    .stat-card.line-green { border-left: 4px solid #2ecc71; }
    .stat-card.line-blue { border-left: 4px solid #3498db; }
    .stat-card.line-yellow { border-left: 4px solid #f1c40f; }
    .stat-card.line-red { border-left: 4px solid #e74c3c; }
    .stat-title { color: #888; text-transform: uppercase; font-size: 12px; font-weight: bold; margin-bottom: 10px;}
    .stat-value { color: #333; font-size: 28px; font-weight: bold; margin-bottom: 10px; }
    .stat-meta { color: #666; font-size: 14px; }
    .progress-bar-container { width: 100%; height: 8px; background-color: #eee; border-radius: 4px; margin-top: 15px;}
    .progress-bar { height: 100%; background-color: #f1c40f; border-radius: 4px; transition: width 1s ease-in-out;}
    .dashboard-layout { display: flex; gap: 20px; }
    .left-panel { flex: 2; display: flex; flex-direction: column; gap: 20px; }
    .right-panel { flex: 1; display: flex; flex-direction: column; gap: 20px;}
    .status-tam-giu { background-color: #fcf3cf; color: #d35400; }
    .status-thanh-toan { background-color: #d4efdf; color: #2ecc71; }
    .status-huy { background-color: #fadbd8; color: #c0392b; }
    .status-default { background-color: #ebdef0; color: #8e44ad; }
    .room-status-card { background-color: #ffffff; padding: 25px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); }
    .status-list { list-style: none; padding: 0; margin: 0; }
    .status-list li { display: flex; align-items: center; justify-content: space-between; padding: 12px 0; font-size: 14px; border-bottom: 1px solid #f1f1f1;}
    .status-item { display: flex; align-items: center; gap: 10px;}
    .status-dot { width: 10px; height: 10px; border-radius: 50%; }
    .status-count { font-weight: bold; color: #333;}
    .dot-green { background-color: #2ecc71; } .dot-blue { background-color: #3498db; } .dot-yellow { background-color: #f1c40f; } .dot-red { background-color: #e74c3c; }
    .btn-update { background-color: #1a1a1a; color: #f1c40f; border: none; width: 100%; padding: 12px; border-radius: 4px; cursor: pointer; font-weight: bold; text-transform: uppercase; margin-top: 20px; font-size: 13px;}
</style>

<div class="stats-grid">
    <div class="stat-card line-green">
        <span class="stat-title">TỔNG DOANH THU</span>
        <span class="stat-value"><?= number_format($doanh_thu, 0, ',', '.') ?> đ</span>
        <span class="stat-meta">(Tháng hiện tại)</span>
    </div>
    <div class="stat-card line-blue">
        <span class="stat-title">BOOKING HÔM NAY</span>
        <span class="stat-value"><?= $booking_today ?> đơn</span>
        <span class="stat-meta">(Check-in hôm nay)</span>
    </div>
    <div class="stat-card line-yellow">
        <span class="stat-title">TỶ LỆ LẤP ĐẦY</span>
        <span class="stat-value"><?= $ty_le_lap_day ?>%</span>
        <span class="stat-meta">(Hôm nay: <?= $phong_luu_tru ?>/<?= $tong_phong ?> phòng)</span>
        <div class="progress-bar-container"><div class="progress-bar" style="width: <?= $ty_le_lap_day ?>%;"></div></div>
    </div>
    <div class="stat-card line-red">
        <span class="stat-title">PHÒNG CẦN XỬ LÝ</span>
        <span class="stat-value"><?= $phong_can_xu_ly ?> phòng</span>
        <span class="stat-meta">(Cần dọn dẹp/Bảo trì)</span>
    </div>
</div>

<div class="dashboard-layout">
    <div class="left-panel">
        <div class="table-section">
            <div class="table-header" style="display:flex; justify-content:space-between; align-items:center;">
                <h3>Đặt phòng gần đây (Từ Website)</h3>
                <a href="manage_bookings.php" style="color:#e67e22; text-decoration:none; font-size:13px;">Xem tất cả đơn ➔</a>
            </div>
            <table>
                <thead><tr><th>MÃ ĐP</th><th>KHÁCH HÀNG</th><th>LOẠI PHÒNG</th><th>NGÀY CHECK-IN</th><th>TRẠNG THÁI</th></tr></thead>
                <tbody>
                    <?php if(count($recent_bookings) > 0): ?>
                        <?php foreach($recent_bookings as $bk): 
                            $badge_class = 'status-default';
                            $tt = mb_strtolower(trim($bk['trang_thai']), 'UTF-8');
                            if (strpos($tt, 'tạm giữ') !== false) $badge_class = 'status-tam-giu';
                            elseif (strpos($tt, 'đã thanh toán') !== false) $badge_class = 'status-thanh-toan';
                            elseif (strpos($tt, 'hủy') !== false) $badge_class = 'status-huy';
                        ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($bk['booking_id']) ?></strong></td>
                            <td><?= htmlspecialchars($bk['ho_ten']) ?></td>
                            <td><?= htmlspecialchars($bk['ten_loai'] ?? 'N/A') ?></td>
                            <td><?= date('d/m/Y', strtotime($bk['ngay_den'])) ?></td>
                            <td><span class="status-badge <?= $badge_class ?>"><?= htmlspecialchars($bk['trang_thai']) ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" style="text-align: center; color: #999;">Chưa có dữ liệu đặt phòng mới.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="right-panel">
        <div class="room-status-card">
            <div class="room-status-header" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                <h3 style="margin:0;">Tình trạng phòng</h3>
                <span style="color:#999; font-size:13px; font-weight:bold;">(Tất cả)</span>
            </div>
            <ul class="status-list">
                <li><div class="status-item"><div class="status-dot dot-green"></div><span>Trống (Sẵn sàng)</span></div><span class="status-count"><?= $phong_trong ?></span></li>
                <li><div class="status-item"><div class="status-dot dot-blue"></div><span>Đang lưu trú</span></div><span class="status-count"><?= $phong_luu_tru ?></span></li>
                <li><div class="status-item"><div class="status-dot dot-yellow"></div><span>Đang dọn dẹp</span></div><span class="status-count"><?= $phong_don_dep ?></span></li>
                <li><div class="status-item"><div class="status-dot dot-red"></div><span>Đang bảo trì</span></div><span class="status-count"><?= $phong_bao_tri ?></span></li>
            </ul>
            <a href="rooms.php" style="text-decoration: none;"><button class="btn-update">XEM CHI TIẾT & CẬP NHẬT ➔</button></a>
        </div>
    </div>
</div>

<?php include 'admin_footer.php'; ?>