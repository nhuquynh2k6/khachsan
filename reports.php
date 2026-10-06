<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] != 1) {
    header("Location: trangchu.php");
    exit();
}
include 'config.php';

// ==========================================
// 1. XỬ LÝ BỘ LỌC NGÀY THÁNG
// ==========================================
// Mặc định là thống kê trong tháng hiện tại (từ mùng 1 đến ngày cuối tháng)
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');

// ==========================================
// 2. KÉO DỮ LIỆU TỔNG QUAN
// ==========================================
$tong_doanh_thu = 0;
$tong_don = 0;
$don_thanh_cong = 0;
$don_huy = 0;

try {
    // Tổng doanh thu (Các đơn không bị hủy)
    $stmt_dt = $conn->prepare("SELECT SUM(tong_tien_du_kien) as total FROM dat_phong WHERE ngay_den BETWEEN ? AND ? AND trang_thai != 'Đã hủy'");
    $stmt_dt->execute([$start_date, $end_date]);
    $tong_doanh_thu = $stmt_dt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

    // Tổng số đơn
    $stmt_td = $conn->prepare("SELECT COUNT(*) as sl FROM dat_phong WHERE ngay_den BETWEEN ? AND ?");
    $stmt_td->execute([$start_date, $end_date]);
    $tong_don = $stmt_td->fetch(PDO::FETCH_ASSOC)['sl'] ?? 0;

    // Đơn thành công / Đã thanh toán / Tạm giữ (Không tính đơn hủy)
    $stmt_tc = $conn->prepare("SELECT COUNT(*) as sl FROM dat_phong WHERE ngay_den BETWEEN ? AND ? AND trang_thai != 'Đã hủy'");
    $stmt_tc->execute([$start_date, $end_date]);
    $don_thanh_cong = $stmt_tc->fetch(PDO::FETCH_ASSOC)['sl'] ?? 0;

    // Đơn bị hủy
    $stmt_h = $conn->prepare("SELECT COUNT(*) as sl FROM dat_phong WHERE ngay_den BETWEEN ? AND ? AND trang_thai = 'Đã hủy'");
    $stmt_h->execute([$start_date, $end_date]);
    $don_huy = $stmt_h->fetch(PDO::FETCH_ASSOC)['sl'] ?? 0;

} catch (Exception $e) {}

// ==========================================
// 3. KÉO DỮ LIỆU ĐỂ VẼ BIỂU ĐỒ (Doanh thu theo từng ngày)
// ==========================================
$chart_labels = [];
$chart_data = [];

try {
    $stmt_chart = $conn->prepare("
        SELECT DATE(ngay_den) as ngay, SUM(tong_tien_du_kien) as doanh_thu 
        FROM dat_phong 
        WHERE ngay_den BETWEEN ? AND ? AND trang_thai != 'Đã hủy'
        GROUP BY DATE(ngay_den)
        ORDER BY DATE(ngay_den) ASC
    ");
    $stmt_chart->execute([$start_date, $end_date]);
    $result_chart = $stmt_chart->fetchAll(PDO::FETCH_ASSOC);

    foreach ($result_chart as $row) {
        $chart_labels[] = date('d/m', strtotime($row['ngay']));
        $chart_data[] = $row['doanh_thu'];
    }
} catch (Exception $e) {}

// ==========================================
// 4. LẤY CHI TIẾT GIAO DỊCH
// ==========================================
$transactions = [];
try {
    $stmt_trans = $conn->prepare("
        SELECT dp.booking_id, kh.ho_ten, p.ten_phong, dp.ngay_den, dp.tong_tien_du_kien, dp.trang_thai 
        FROM dat_phong dp 
        JOIN khach_hang kh ON dp.khach_hang_cccd = kh.cccd_ho_chieu 
        JOIN phong p ON dp.ma_phong = p.ma_phong 
        WHERE dp.ngay_den BETWEEN ? AND ?
        ORDER BY dp.ngay_den DESC
    ");
    $stmt_trans->execute([$start_date, $end_date]);
    $transactions = $stmt_trans->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Báo Cáo Doanh Thu - Admin</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* --- CHUNG & SIDEBAR (LUXURY THEME) --- */
        body { margin: 0; font-family:Arial, sans-serif; background-color: #f4f7f6; display: flex; height: 100vh; overflow: hidden; }
        
        .sidebar { width: 260px; background-color: #1a1a1a; color: white; display: flex; flex-direction: column; box-shadow: 2px 0 10px rgba(0,0,0,0.1); z-index: 10;}
        .sidebar-header { padding: 30px 20px; text-align: center; border-bottom: 1px solid #333; }
        .sidebar-header h2 { color: #f1c40f; margin: 0; font-size: 20px; font-weight: bold; text-transform: uppercase; }
        
        .sidebar-menu { list-style: none; padding: 20px 0; margin: 0; overflow-y: auto;}
        .sidebar-menu li { margin-bottom: 5px; }
        .sidebar-menu a { display: flex; align-items: center; padding: 12px 25px; color: #999; text-decoration: none; font-size: 15px; transition: 0.3s; }
        .sidebar-menu a:hover, .sidebar-menu a.active { color: white; background-color: #333; border-left: 4px solid #f1c40f; }
        .sidebar-menu .menu-category { color: #555; padding: 15px 25px 5px 25px; text-transform: uppercase; font-size: 11px; font-weight: bold; letter-spacing: 1px;}
        .sidebar-menu .icon { margin-right: 15px; font-size: 18px; }

        /* --- NỘI DUNG CHÍNH --- */
        .main-content { flex: 1; display: flex; flex-direction: column; overflow-y: auto; }
        .topbar { background-color: #ffffff; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .topbar h1 { margin: 0; font-size: 20px; color: #333; font-weight: bold; text-transform: uppercase;}
        .topbar .user-info { display: flex; align-items: center; gap: 15px; }
        .topbar .user-text { color: #666; font-size: 14px; }
        .topbar .btn-logout { background-color: #e74c3c; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer; text-decoration: none; font-size: 13px; }

        .dashboard-container { padding: 30px; }

        /* --- BỘ LỌC --- */
        .filter-section { background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); margin-bottom: 30px; display: flex; align-items: center; justify-content: space-between; border-left: 5px solid #1a1a1a;}
        .filter-form { display: flex; align-items: center; gap: 15px; }
        .filter-group { display: flex; flex-direction: column; }
        .filter-group label { font-size: 12px; color: #888; font-weight: bold; text-transform: uppercase; margin-bottom: 5px;}
        .filter-group input { padding: 8px 15px; border: 1px solid #ccc; border-radius: 5px; outline: none; font-family: inherit;}
        .btn-filter { background: #f1c40f; color: #1a1a1a; border: none; padding: 10px 20px; border-radius: 5px; font-weight: bold; cursor: pointer; transition: 0.3s;}
        .btn-filter:hover { background: #d4ac0d; }

        /* --- THẺ THỐNG KÊ --- */
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px; }
        .stat-card { background-color: #ffffff; padding: 25px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); display: flex; flex-direction: column;}
        .stat-card.c-gold { border-top: 4px solid #f1c40f; }
        .stat-card.c-blue { border-top: 4px solid #3498db; }
        .stat-card.c-green { border-top: 4px solid #2ecc71; }
        .stat-card.c-red { border-top: 4px solid #e74c3c; }
        
        .stat-title { color: #888; text-transform: uppercase; font-size: 12px; font-weight: bold; margin-bottom: 10px;}
        .stat-value { color: #333; font-size: 26px; font-weight: bold; margin-bottom: 5px; }

        /* --- KHU VỰC BIỂU ĐỒ --- */
        .chart-section { background: #fff; padding: 25px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); margin-bottom: 30px;}
        .section-title { margin-top: 0; margin-bottom: 20px; font-size: 18px; color: #333;}

        /* --- BẢNG DỮ LIỆU --- */
        .table-section { background-color: #ffffff; padding: 25px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); }
        table { width: 100%; border-collapse: collapse; }
        table th, table td { text-align: left; padding: 15px 12px; font-size: 14px; border-bottom: 1px solid #eee; vertical-align: middle;}
        table th { color: #888; text-transform: uppercase; font-weight: bold; background: #fafafa; font-size: 12px;}
        table td { color: #555; }
        
        .status-badge { padding: 5px 10px; border-radius: 4px; font-size: 12px; font-weight: bold; text-transform: uppercase;}
        .st-ok { background: #d4efdf; color: #27ae60; }
        .st-huy { background: #fadbd8; color: #c0392b; }
        .st-wait { background: #fcf3cf; color: #d35400; }
    </style>
</head>
<body>

    <aside class="sidebar">
        <div class="sidebar-header">
            <h2>Three Beautiful Girl</h2>
        </div>
        <ul class="sidebar-menu">
            <li><a href="admin_dashboard.php"><span class="icon">📈</span> Tổng quan</a></li>
            <li class="menu-category">LỄ TÂN & GIAO DỊCH</li>
            <li><a href="manage_bookings.php"><span class="icon">🛎️</span> Quản lý Đặt phòng</a></li>
            <li><a href="manage_users.php"><span class="icon">👥</span> Hồ sơ Khách hàng</a></li>
            
            <li class="menu-category">QUẢN LÝ HỆ THỐNG</li>
            <li><a href="rooms.php"><span class="icon">🛏️</span> Quản lý phòng và loại phòng</a></li>
            <li><a href="manage_staff.php"><span class="icon">👷‍♂️</span> Quản lý Nhân sự</a></li>
            <li><a href="manage_services.php"><span class="icon">✨</span> Quản Lý Dịch Vụ</a></li>

            <li class="menu-category">BÁO CÁO</li>
            <li><a href="reports.php" class="active"><span class="icon">📊</span> Báo cáo Doanh thu</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <h1>BÁO CÁO & THỐNG KÊ DOANH THU</h1>
            <div class="user-info">
                <span class="user-text">Quản lý: <strong>Quản trị hệ thống</strong></span>
                <a href="logout.php" class="btn-logout">Đăng xuất</a>
            </div>
        </header>

        <div class="dashboard-container">
            
            <div class="filter-section">
                <div>
                    <h3 style="margin: 0; color: #333;">Dữ liệu từ: <span style="color: #d35400;"><?= date('d/m/Y', strtotime($start_date)) ?></span> đến <span style="color: #d35400;"><?= date('d/m/Y', strtotime($end_date)) ?></span></h3>
                </div>
                <form method="GET" class="filter-form">
                    <div class="filter-group">
                        <label>Từ ngày</label>
                        <input type="date" name="start_date" value="<?= $start_date ?>" required>
                    </div>
                    <div class="filter-group">
                        <label>Đến ngày</label>
                        <input type="date" name="end_date" value="<?= $end_date ?>" required>
                    </div>
                    <button type="submit" class="btn-filter">LỌC DỮ LIỆU ➔</button>
                </form>
            </div>

            <div class="stats-grid">
                <div class="stat-card c-gold">
                    <span class="stat-title">TỔNG DOANH THU</span>
                    <span class="stat-value" style="color: #d35400;"><?= number_format($tong_doanh_thu, 0, ',', '.') ?> đ</span>
                </div>
                <div class="stat-card c-blue">
                    <span class="stat-title">TỔNG SỐ ĐƠN ĐẶT</span>
                    <span class="stat-value"><?= number_format($tong_don) ?></span>
                </div>
                <div class="stat-card c-green">
                    <span class="stat-title">ĐƠN THÀNH CÔNG</span>
                    <span class="stat-value" style="color: #27ae60;"><?= number_format($don_thanh_cong) ?></span>
                </div>
                <div class="stat-card c-red">
                    <span class="stat-title">ĐƠN BỊ HỦY</span>
                    <span class="stat-value" style="color: #e74c3c;"><?= number_format($don_huy) ?></span>
                </div>
            </div>

            <div class="chart-section">
                <h3 class="section-title">📈 Biểu đồ biến động doanh thu</h3>
                <?php if (count($chart_data) > 0): ?>
                    <canvas id="revenueChart" height="80"></canvas>
                <?php else: ?>
                    <p style="text-align: center; color: #999; padding: 40px 0;">Không có dữ liệu doanh thu trong khoảng thời gian này.</p>
                <?php endif; ?>
            </div>

            <div class="table-section">
                <h3 class="section-title">🧾 Bảng kê chi tiết giao dịch</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Mã Đơn</th>
                            <th>Khách Hàng</th>
                            <th>Phòng</th>
                            <th>Ngày Check-in</th>
                            <th>Thành Tiền</th>
                            <th>Trạng Thái</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($transactions) > 0): ?>
                            <?php foreach ($transactions as $tr): 
                                $tt = trim($tr['trang_thai']);
                                $badge = 'st-wait';
                                if ($tt == 'Đã hủy') $badge = 'st-huy';
                                elseif ($tt == 'Đã thanh toán đầy đủ' || $tt == 'Hoàn tất trả phòng') $badge = 'st-ok';
                            ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($tr['booking_id']) ?></strong></td>
                                <td><?= htmlspecialchars($tr['ho_ten']) ?></td>
                                <td><?= htmlspecialchars($tr['ten_phong']) ?></td>
                                <td><?= date('d/m/Y', strtotime($tr['ngay_den'])) ?></td>
                                <td style="color: #d35400; font-weight: bold;"><?= number_format($tr['tong_tien_du_kien'], 0, ',', '.') ?> đ</td>
                                <td><span class="status-badge <?= $badge ?>"><?= htmlspecialchars($tt) ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6" style="text-align: center; color: #999; padding: 20px;">Không có giao dịch nào.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </main>

    <script>
        <?php if (count($chart_data) > 0): ?>
        const ctx = document.getElementById('revenueChart').getContext('2d');
        const revenueChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($chart_labels) ?>,
                datasets: [{
                    label: 'Doanh thu (VNĐ)',
                    data: <?= json_encode($chart_data) ?>,
                    backgroundColor: 'rgba(241, 196, 15, 0.7)',
                    borderColor: '#f39c12',
                    borderWidth: 1,
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value.toLocaleString('vi-VN') + ' đ';
                            }
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.raw.toLocaleString('vi-VN') + ' VNĐ';
                            }
                        }
                    }
                }
            }
        });
        <?php endif; ?>
    </script>

</body>
</html>