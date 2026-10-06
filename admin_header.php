<?php
// Tự động nhận diện trang hiện tại để bôi vàng menu tương ứng
$current_page = basename($_SERVER['PHP_SELF']);
$page_title = $page_title ?? "Admin Dashboard"; 
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> - Three Beautiful Girl</title>
    <style>
        /* ================= TOÀN BỘ CSS CHUNG ADMIN ================= */
        body { margin: 0; font-family: Arial, sans-serif; background-color: #f4f7f6; display: flex; height: 100vh; overflow: hidden; }
        
        .sidebar { width: 260px; background-color: #1a1a1a; color: white; display: flex; flex-direction: column; box-shadow: 2px 0 10px rgba(0,0,0,0.1); z-index: 100;}
        .sidebar-header { padding: 30px 20px; text-align: center; border-bottom: 1px solid #333; }
        .sidebar-header h2 { color: #f1c40f; margin: 0; font-size: 20px; font-weight: bold; text-transform: uppercase; }
        .sidebar-menu { list-style: none; padding: 20px 0; margin: 0; overflow-y: auto; flex: 1;}
        .sidebar-menu li { margin-bottom: 5px; }
        .sidebar-menu a { display: flex; align-items: center; padding: 12px 25px; color: #999; text-decoration: none; font-size: 15px; transition: 0.3s; }
        .sidebar-menu a:hover, .sidebar-menu a.active { color: white; background-color: #333; border-left: 4px solid #f1c40f; }
        .sidebar-menu .menu-category { color: #555; padding: 15px 25px 5px 25px; text-transform: uppercase; font-size: 11px; font-weight: bold; letter-spacing: 1px;}
        .sidebar-menu .icon { margin-right: 15px; font-size: 18px; }

        .main-content { flex: 1; display: flex; flex-direction: column; overflow-y: auto; position: relative;}
        .topbar { background-color: #ffffff; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 10px rgba(0,0,0,0.05); position: sticky; top: 0; z-index: 99;}
        .topbar h1 { margin: 0; font-size: 20px; color: #333; font-weight: bold; text-transform: uppercase;}
        .topbar .user-info { display: flex; align-items: center; gap: 15px; }
        .topbar .user-text { color: #666; font-size: 14px; }
        .topbar .btn-logout { background-color: #e74c3c; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer; text-decoration: none; font-size: 13px; font-weight: bold;}
        .topbar .btn-logout:hover { background-color: #c0392b; }

        .dashboard-container { padding: 30px; }

        .table-section { background-color: #ffffff; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); margin-bottom: 30px;}
        .table-header { margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;}
        .table-header h3 { margin: 0; font-size: 18px; color: #333;}
        table { width: 100%; border-collapse: collapse; }
        table th, table td { text-align: left; padding: 15px 12px; font-size: 14px; border-bottom: 1px solid #eee; vertical-align: middle;}
        table th { color: #888; text-transform: uppercase; font-weight: bold; background: #fafafa; font-size: 12px;}
        table td { color: #555; }
        table tbody tr:hover { background-color: #fcfcfc; }

        .form-card { background: #ffffff; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); margin-bottom: 30px; border-top: 4px solid #2ecc71; position: relative;}
        .form-card.edit-mode { border-top: 4px solid #3498db; }
        .form-title { margin-top: 0; margin-bottom: 20px; font-size: 18px; color: #333; display: flex; align-items: center; gap: 10px;}
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; }
        .form-group { display: flex; flex-direction: column; margin-bottom: 5px; }
        .form-group label { font-weight: 600; color: #555; font-size: 13px; margin-bottom: 8px; text-transform: uppercase;}
        .form-group input, .form-group select, .form-group textarea { padding: 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; outline: none; transition: 0.3s; font-family: inherit;}
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-color: #f1c40f; box-shadow: 0 0 0 3px rgba(241, 196, 15, 0.1); }
        .full-width { grid-column: span 2; }

        .btn-submit { background: #1a1a1a; color: #f1c40f; padding: 14px 25px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 14px; text-transform: uppercase; transition: 0.3s;}
        .btn-submit:hover { background: #333; }
        .btn-cancel { color: #e74c3c; text-decoration: none; font-weight: bold; margin-left: 15px; font-size: 14px;}
        .btn-cancel:hover { text-decoration: underline; }
        .btn-action { padding: 6px 12px; border-radius: 4px; text-decoration: none; font-size: 12px; font-weight: bold; display: inline-block; margin-right: 5px; transition: 0.2s;}
        .btn-edit { background: #d6eaf8; color: #2980b9; }
        .btn-edit:hover { background: #3498db; color: white;}
        .btn-delete { background: #fadbd8; color: #c0392b; }
        .btn-delete:hover { background: #e74c3c; color: white;}
        
        .alert { padding: 15px; margin-bottom: 25px; border-radius: 6px; font-weight: bold; display: flex; align-items: center; gap: 10px;}
        .alert-success { background: #d4efdf; color: #27ae60; border-left: 4px solid #2ecc71;}
        .alert-error { background: #fadbd8; color: #c0392b; border-left: 4px solid #e74c3c;}
        .status-badge { padding: 5px 10px; border-radius: 4px; font-size: 12px; font-weight: bold; text-transform: uppercase;}
    </style>
</head>
<body>

    <aside class="sidebar">
        <div class="sidebar-header">
            <h2>Three Beautiful Girl</h2>
        </div>
        <ul class="sidebar-menu">
            <li><a href="admin_dashboard.php" class="<?= $current_page == 'admin_dashboard.php' ? 'active' : '' ?>"><span class="icon">📈</span> Tổng quan</a></li>
            
            <li class="menu-category">LỄ TÂN & GIAO DỊCH</li>
            <li><a href="manage_bookings.php" class="<?= $current_page == 'manage_bookings.php' ? 'active' : '' ?>"><span class="icon">🛎️</span> Quản lý Đặt phòng</a></li>
            <li><a href="manage_users.php" class="<?= $current_page == 'manage_users.php' ? 'active' : '' ?>"><span class="icon">👥</span> Hồ sơ Khách hàng</a></li>
            
            <li class="menu-category">QUẢN LÝ HỆ THỐNG</li>
            <li><a href="rooms.php" class="<?= $current_page == 'rooms.php' ? 'active' : '' ?>"><span class="icon">🛏️</span> Quản lý phòng & Loại</a></li>
            <li><a href="manage_staff.php" class="<?= $current_page == 'manage_staff.php' ? 'active' : '' ?>"><span class="icon">👷‍♂️</span> Quản lý Nhân sự</a></li>
            <li><a href="manage_services.php" class="<?= $current_page == 'manage_services.php' ? 'active' : '' ?>"><span class="icon">✨</span> Quản Lý Dịch Vụ</a></li>

            <li class="menu-category">BÁO CÁO</li>
            <li><a href="reports.php" class="<?= $current_page == 'reports.php' ? 'active' : '' ?>"><span class="icon">📊</span> Báo cáo Doanh thu</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <h1><?= htmlspecialchars($page_title) ?></h1>
            <div class="user-info">
                <span class="user-text">Nhân viên: <strong><?= htmlspecialchars($_SESSION['username'] ?? 'Quản trị') ?></strong></span>
                <a href="logout.php" class="btn-logout">Đăng xuất</a>
            </div>
        </header>

        <div class="dashboard-container">