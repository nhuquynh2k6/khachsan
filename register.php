<?php 
// 1. PHẦN LOGIC (Xử lý dữ liệu)
include 'config.php'; 

if (isset($_POST['btn_dangky'])) {
    $name = trim($_POST['txt_fullname']);
    $pass = $_POST['txt_password'];
    $cccd = trim($_POST['txt_cccd']);
    $sdt = trim($_POST['txt_tell']);
    $mail = trim($_POST['txt_mail']);

    if (empty($name) || empty($pass) || empty($cccd) || empty($sdt) || empty($mail)) {
        $thong_bao = "Vui lòng nhập đầy đủ thông tin!";
    } 
    elseif (strlen($pass) < 6) {
        $thong_bao = "Mật khẩu phải có ít nhất 6 ký tự để bảo mật!";
    }
    else {
        // Mã hóa mật khẩu
        $pass_ma_hoa = password_hash($pass, PASSWORD_DEFAULT);
        
        // CÂU LỆNH SQL
        $sql = "INSERT INTO khach_hang (cccd_ho_chieu, ho_ten, so_dien_thoai, email, mat_khau) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        
        try {
            if ($stmt->execute([$cccd, $name, $sdt, $mail, $pass_ma_hoa])) {
                $thong_bao_thanh_cong = "Đăng ký thành công! Đang chuyển sang trang đăng nhập trong 3 giây...";
                
                // --- ĐOẠN CHỈNH SỬA Ở ĐÂY ---
                // Chờ 3 giây rồi chuyển sang login.php
                header("refresh:3; url=login.php"); 
                // ----------------------------
                
            } else {
                $thong_bao = "Đăng ký thất bại, vui lòng thử lại!";
            }
        } catch(PDOException $e) {
            $thong_bao = "Lỗi: Số điện thoại, Email hoặc CCCD này đã tồn tại!";
        }
    }
}

include 'header.php'; 
?>

<style>
   .register-form {
        width: 50%;
        margin: 20px auto;
        padding: 30px; 
        background-color: #ffffff;
        border-radius: 10px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    }
    .register-form h1 {
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
        width: 160px;
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
    button[name="btn_dangky"] {
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

<div class="register-form">
    <h1>Đăng ký tài khoản</h1>
    
    <?php 
        if(isset($thong_bao)) echo "<p style='color:red; text-align:center; font-weight:bold;'>$thong_bao</p>"; 
        if(isset($thong_bao_thanh_cong)) echo "<p style='color:green; text-align:center; font-weight:bold;'>$thong_bao_thanh_cong</p>"; 
    ?>

    <form method="POST">
        <div class="form-group">
            <label>Họ và tên:</label>
            <input type="text" name="txt_fullname" placeholder="Nhập đầy đủ họ tên" required>
        </div>
        <div class="form-group">
            <label>Mật khẩu:</label>
            <input type="password" name="txt_password" placeholder="Mật khẩu tối thiểu 6 ký tự" required>
        </div>
        <div class="form-group">
            <label>Số CCCD:</label>
            <input type="text" name="txt_cccd" placeholder="Dùng làm Mã đăng nhập sau này" required>
        </div>
        <div class="form-group">
            <label>Số điện thoại:</label>
            <input type="text" name="txt_tell" required>
        </div>
        <div class="form-group">
            <label>Email:</label>
            <input type="email" name="txt_mail" placeholder="abc123@gmail.com" required>
        </div>
        <button type="submit" name="btn_dangky">Xác nhận Đăng ký</button>
        <p style="text-align:center; margin-top: 20px; font-size: 16px;">
            Bạn đã có tài khoản? <a href="login.php" style="color: #007bff; font-weight: bold; text-decoration: none;">Đăng nhập ngay</a>
        </p>
    </form>
</div>

<?php include 'footer.php'; ?>