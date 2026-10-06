<?php 
session_start();
include 'config.php'; 

if (isset($_POST['btn_dangnhap'])) {
    $name = trim($_POST['txt_fullname']);
    $cccd = trim($_POST['txt_cccd']);
    $sdt = trim($_POST['txt_tell']);
    $mail = trim($_POST['txt_mail']);
    $pass = $_POST['txt_password'];

    if (empty($name) || empty($cccd) || empty($sdt) || empty($mail) || empty($pass)) {
        $thong_bao = "Vui lòng nhập đầy đủ tất cả thông tin để đăng nhập!";
    } else {
        // 1. KIỂM TRA BẢNG NHÂN VIÊN TRƯỚC (Ưu tiên Admin)
        // Quét đúng các cột mà bồ đã thêm trong CSDL
        $sql_nv = "SELECT * FROM nhan_vien WHERE ho_ten = ? AND cccd_ho_chieu = ? AND so_dien_thoai = ? AND email = ?";
        $stmt_nv = $conn->prepare($sql_nv);
        $stmt_nv->execute([$name, $cccd, $sdt, $mail]);
        $nhanvien = $stmt_nv->fetch();

        if ($nhanvien) {
            // Đúng là nhân viên rồi, giờ check mật khẩu
            if ($pass == $nhanvien['mat_khau'] || password_verify($pass, $nhanvien['mat_khau'])) {
                $_SESSION['user_id'] = $nhanvien['ma_nv']; 
                $_SESSION['username'] = $nhanvien['ho_ten']; 
                $_SESSION['role'] = 1; // 1 = Admin -> Cấp quyền quản trị
                header("Location: admin_dashboard.php");
                exit();
            } else {
                $thong_bao = "Sai mật khẩu của Admin/Nhân viên!";
            }
        } 
        else {
            // 2. KHÔNG PHẢI NHÂN VIÊN THÌ TÌM TRONG BẢNG KHÁCH HÀNG
            $sql_kh = "SELECT * FROM khach_hang WHERE ho_ten = ? AND cccd_ho_chieu = ? AND so_dien_thoai = ? AND email = ?";
            $stmt_kh = $conn->prepare($sql_kh);
            $stmt_kh->execute([$name, $cccd, $sdt, $mail]);
            $khach = $stmt_kh->fetch();

            if ($khach) {
                if ($pass == $khach['mat_khau'] || password_verify($pass, $khach['mat_khau'])) {
                    $_SESSION['user_id'] = $khach['cccd_ho_chieu']; 
                    $_SESSION['username'] = $khach['ho_ten']; 
                    $_SESSION['role'] = 0; // 0 = Khách hàng
                    header("Location: trangchu.php");
                    exit();
                } else {
                    $thong_bao = "Sai mật khẩu của Khách hàng!";
                }
            } else {
                $thong_bao = "Tài khoản không tồn tại hoặc thông tin nhập chưa chính xác!";
            }
        }
    }
}

include 'header.php'; 
?>
<style>
    .login-form {
        width: 50%; /* Chỉnh lại độ rộng cho giống form đăng ký */
        margin: 20px auto;
        padding: 30px; 
        background-color: #ffffff;
        border-radius: 10px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    }
    .login-form h1 {
        font-size: 28px !important;
        text-align: center;
        color: #1c5980; 
        margin-bottom: 30px;
        text-transform: uppercase;
    }
    .form-group {
        display: flex;         
        align-items: center;   
        margin-bottom: 20px;   
    }
    .form-group label {
        width: 160px; /* Chỉnh lại cho bằng bên đăng ký */           
        font-size: 18px;
        font-weight: bold;
        color: #2c3e50;
    }
    .form-group input {
        flex: 1;                
        font-size: 16px !important;
        padding: 10px !important;
        border: 1px solid #ccc;
        border-radius: 5px;
    }
    button[name="btn_dangnhap"] {
        background-color: #007bff !important; 
        color: white !important;
        padding: 12px 40px !important;
        border: none !important;
        border-radius: 5px !important;
        font-weight: bold !important;
        font-size: 18px !important;
        cursor: pointer !important;
        display: block;
        margin: 20px auto 0 auto;
    }
</style>

<div class="login-form">
    <h1>Đăng Nhập Hệ Thống</h1>
    
    <?php if(isset($thong_bao)) echo "<p style='color:red; text-align:center; font-weight:bold;'>$thong_bao</p>"; ?>
    
    <form method="POST">
        <div class="form-group">
            <label>Họ và tên:</label>
            <input type="text" name="txt_fullname" placeholder="Nhập đầy đủ họ tên" required>
        </div>
        <div class="form-group">
            <label>Mật khẩu:</label>
            <input type="password" name="txt_password" placeholder="Mật khẩu của bạn" required>
        </div>
        <div class="form-group">
            <label>Số CCCD / Mã NV:</label>
            <input type="text" name="txt_cccd" placeholder="CCCD (Khách) hoặc Mã NV (Admin)" required>
        </div>
        <div class="form-group">
            <label>Số điện thoại:</label>
            <input type="text" name="txt_tell" required>
        </div>
        <div class="form-group">
            <label>Email:</label>
            <input type="email" name="txt_mail" placeholder="abc123@gmail.com" required>
        </div>
        
        <button type="submit" name="btn_dangnhap">Vào Hệ Thống</button>
        <div style="text-align: right; margin-top: 15px;">
            <a href="quenmatkhau.php" style="color: #007bff; text-decoration: none;">Quên mật khẩu?</a>
        </div>
    </form>
</div>

<?php include 'footer.php'; ?>