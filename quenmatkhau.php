<?php 
session_start();
include 'config.php'; 

if (isset($_POST['btn_reset'])) {
    $user = trim($_POST['txt_username']);
    $new_pass = $_POST['txt_new_password'];
    $confirm_pass = $_POST['txt_confirm_password'];

    if (empty($user) || empty($new_pass)) {
        $thong_bao = "Vui lòng nhập đầy đủ thông tin!";
    } elseif ($new_pass !== $confirm_pass) {
        $thong_bao = "Mật khẩu xác nhận không khớp!";
    } else {
        // Kiểm tra xem tài khoản có tồn tại không
        $sql_check = "SELECT * FROM khach_hang WHERE ho_ten = ?";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->execute([$user]);
        if ($stmt_check->fetch()) {
            // Mã hóa mật khẩu mới
            $hashed_password = password_hash($new_pass, PASSWORD_DEFAULT);
            
            // Cập nhật vào database
            $sql_update = "UPDATE khach_hang SET mat_khau = ? WHERE ho_ten = ?";
            $stmt_update = $conn->prepare($sql_update);
            if ($stmt_update->execute([$hashed_password, $user])) {
                $_SESSION['sucess'] = "Đặt lại mật khẩu thành công! Hãy đăng nhập lại.";
                header("Location: login.php");
                exit();
            }
        } else {
            $thong_bao = "Tài khoản không tồn tại trên hệ thống!";
        }
    }
}
include 'header.php'; 
?>

<style>
    .reset-form {
        max-width: 500px;
        margin: 50px auto;
        padding: 30px;
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    .reset-form h1 {
        text-align: center;
        color: #333;
        margin-bottom: 25px;
        font-family: serif;
    }
    .form-group {
        margin-bottom: 15px;
    }
    .form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: bold;
    }
    .form-group input {
        width: 100%;
        padding: 10px;
        border: 1px solid #ccc;
        border-radius: 5px;
        box-sizing: border-box;
    }
    .btn-reset {
        width: 100%;
        background: #e67e22; /* Màu cam cho khác biệt với nút đăng nhập */
        color: white;
        padding: 12px;
        border: none;
        border-radius: 5px;
        font-weight: bold;
        cursor: pointer;
        font-size: 16px;
    }
    .btn-reset:hover {
        background: #d35400;
    }
    .back-link {
        display: block;
        text-align: center;
        margin-top: 15px;
        text-decoration: none;
        color: #3498db;
    }
</style>

<div class="reset-form">
    <h1>Quên Mật Khẩu</h1>
    
    <?php if(isset($thong_bao)) echo "<p style='color:red; text-align:center;'>$thong_bao</p>"; ?>

    <form method="POST">
        <div class="form-group">
            <label>Nhập tên đăng nhập của bạn:</label>
            <input type="text" name="txt_username" placeholder="Ví dụ: admin123" required>
        </div>
        
        <div class="form-group">
            <label>Mật khẩu mới:</label>
            <input type="password" name="txt_new_password" required>
        </div>

        <div class="form-group">
            <label>Xác nhận mật khẩu mới:</label>
            <input type="password" name="txt_confirm_password" required>
        </div>
        
        <button type="submit" name="btn_reset" class="btn-reset">Cập Nhật Mật Khẩu</button>
        <a href="login.php" class="back-link">Quay lại Đăng nhập</a>
    </form>
</div>

<?php include 'footer.php'; ?>