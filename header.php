<?php
// Khởi tạo session an toàn
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Three Beautiful Girl Hotel</title>
    <style>
        body { margin: 0; font-family: Arial, sans-serif; background-color: #f4f7f6; }
        
        /* --- ĐỒNG BỘ PHONG CÁCH LUXURY --- */
        nav { 
            background-color: #1a1a1a !important; /* Tone đen quyền lực */
            padding: 15px 30px !important;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3) !important;
            display: flex !important;
            align-items: center;
            justify-content: space-between !important; /* Đẩy 2 khối ra 2 bìa */
            flex-wrap: wrap;
            border-bottom: 3px solid #f1c40f; /* Viền vàng chốt form */
        }
        
        .brand-logo {
            font-size: 18px !important; 
            color: #f1c40f !important; 
            letter-spacing: 1px; 
            font-weight: bold;
            text-decoration: none;
            white-space: nowrap;
        }

        /* Gói toàn bộ menu và user vào một khối để đẩy sang phải */
        .nav-right-wrapper {
            display: flex;
            align-items: center;
            gap: 30px; /* Khoảng cách giữa cụm menu và cụm tài khoản */
            margin-left: auto; /* Lực đẩy mạnh nhất sang góc phải */
        }

        .nav-links { display: flex; align-items: center; gap: 20px; flex-wrap: wrap;}
        
        nav a { 
            color: #ecf0f1 !important; 
            text-decoration: none !important; 
            font-weight: bold !important;
            transition: 0.3s;
            font-size: 13px; 
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        nav a:hover:not(.brand-logo) { color: #f1c40f !important; transform: translateY(-2px); display: inline-block; }
        
        .user-section { display: flex; align-items: center; gap: 15px; }
        
        .admin-label { 
            background: #e74c3c; 
            color: white; 
            padding: 3px 8px; 
            border-radius: 4px; 
            font-size: 11px; 
            text-transform: uppercase; 
            margin-right: 8px; 
            font-weight: bold; 
            letter-spacing: 1px;
        }
        
        .btn-logout { 
            background-color: #e74c3c !important; 
            color: white !important; 
            padding: 8px 15px !important; 
            border-radius: 4px !important; 
            text-decoration: none !important; 
            font-size: 12px !important; 
            font-weight: bold;
            transition: 0.3s; 
            text-transform: uppercase;
        }
        .btn-logout:hover { background-color: #c0392b !important; transform: scale(1.05); }
    </style>
</head>
<body>
    <nav>
        <a href="trangchu.php" class="brand-logo">🏨 THREE BEAUTIFUL GIRL</a>
        
        <div class="nav-right-wrapper">
            <div class="nav-links">
                <a href="trangchu.php">Đặt Phòng</a>
                
                <?php 
                // 1. MENU CHO KHÁCH (Chỉ hiện khi chưa đăng nhập hoặc không phải admin)
                if (!isset($_SESSION['role']) || $_SESSION['role'] != 1): 
                ?>
                    <a href="trangchu.php#gioithieu">Giới Thiệu</a>
                    <a href="trangchu.php#dichvu">Dịch Vụ</a>
                    <a href="#">Ưu Đãi</a>
                    <a href="trangchu.php#tintuc">Tin Tức</a>
                    <a href="<?php echo isset($_SESSION['username']) ? 'fedback.php' : 'login.php'; ?>">Góp Ý</a>
                    <a href="trangchu.php#lienhe">Liên Hệ</a>
                <?php endif; ?>
                
                <?php 
                // 2. MENU CHO ADMIN (Hiện nút Dashboard và Báo cáo trực tiếp)
                if (isset($_SESSION['role']) && $_SESSION['role'] == 1): 
                ?>
                    <a href="admin_dashboard.php" style="color: #f1c40f !important;">📊 Bảng Điều Khiển</a>
                    <a href="reports.php" >📈 Báo Cáo</a>
                    <a href="rooms.php">🛠 Phòng</a>
                    <a href="manage_users.php">👪 Khách</a>
                    <a href="manage_services.php">🛎 Dịch Vụ</a>
                <?php endif; ?>

                <?php 
                // 3. MENU CHO USER THƯỜNG
                if (isset($_SESSION['role']) && $_SESSION['role'] == 0): 
                ?>
                    <a href= "<?php echo isset($_SESSION['username']) ? 'booking_history.php' : 'login.php'; ?>">📅 Lịch Sử Đặt</a>
                <?php endif; ?>

                <?php 
                // 4. NÚT ĐĂNG KÝ/ĐĂNG NHẬP (Nếu chưa có session)
                if(!isset($_SESSION['username'])): 
                ?>
                    <a href="register.php">Đăng Ký</a>
                    <a href="login.php">Đăng Nhập</a>
                <?php endif; ?>
            </div>
            
            <div class="user-section">
                <?php if (isset($_SESSION['username'])): ?>
                    <span style="color: #f1c40f; font-weight: bold; display: flex; align-items: center; font-size: 13px;">
                        <?php if(isset($_SESSION['role']) && $_SESSION['role'] == 1): ?>
                            <span class="admin-label">Admin</span>
                        <?php endif; ?>
                        CHÀO, <?= htmlspecialchars(mb_strtoupper($_SESSION['username'], 'UTF-8')); ?>!
                    </span>
                    <a href="logout.php" class="btn-logout">Đăng xuất ➔</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>