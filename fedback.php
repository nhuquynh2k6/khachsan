<?php
// Khởi tạo session và kiểm tra đăng nhập
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// BẢO MẬT BẮT BUỘC: Chưa đăng nhập thì đá về trang login ngay lập tức
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

include 'config.php';
include 'header.php'; // Gọi thanh menu vào
?>

<style>
    /* --- CSS CHO TRANG GÓP Ý --- */
    body { background-color: #f4f7f6; }
    .page-gopy { padding: 80px 20px; text-align: center; min-height: 60vh; }
    .feedback-container { max-width: 600px; margin: 0 auto; background: #fff; padding: 40px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); border-top: 5px solid #f39c12; }
    
    .section-title { color: #2c3e50; font-size: 28px; margin-bottom: 10px; text-transform: uppercase; }
    .title-line { width: 60px; height: 4px; background: #f39c12; margin: 0 auto 30px auto; border-radius: 2px; }

    /* Hiệu ứng Ngôi Sao Đánh Giá */
    .star-rating { direction: rtl; display: inline-block; font-size: 50px; margin-bottom: 20px;}
    .star-rating input[type="radio"] { display: none; }
    .star-rating label { color: #ddd; cursor: pointer; transition: 0.2s; padding: 0 5px; }
    .star-rating label:hover,
    .star-rating label:hover ~ label,
    .star-rating input[type="radio"]:checked ~ label { color: #f1c40f; text-shadow: 0 0 10px rgba(241, 196, 15, 0.5); }

    .feedback-text { width: 100%; height: 150px; padding: 15px; margin-top: 10px; border: 2px solid #eee; border-radius: 8px; font-family: Arial, sans-serif; font-size: 15px; resize: vertical; box-sizing: border-box; outline: none; transition: 0.3s;}
    .feedback-text:focus { border-color: #f39c12; }
    
    .btn-submit-feedback { background: linear-gradient(to right, #f39c12, #e67e22); color: white; border: none; padding: 15px 30px; border-radius: 8px; font-size: 18px; font-weight: bold; cursor: pointer; margin-top: 20px; transition: 0.3s; width: 100%; text-transform: uppercase; box-shadow: 0 4px 15px rgba(230, 126, 34, 0.4);}
    .btn-submit-feedback:hover { transform: translateY(-3px); box-shadow: 0 6px 20px rgba(230, 126, 34, 0.6); }
    
    .user-greeting { margin-bottom: 20px; font-size: 16px; color: #7f8c8d; }
    .user-greeting span { color: #2980b9; font-weight: bold; }
</style>

<div class="page-gopy">
    <div class="feedback-container">
        <h2 class="section-title">Gửi Góp Ý Cho Chúng Tôi</h2>
        <div class="title-line"></div>
        
        <div class="user-greeting">
            Xin chào <span><?= htmlspecialchars($_SESSION['username'] ?? 'Quý khách') ?></span>, cảm ơn bạn đã lựa chọn Three Beautiful Girl Hotel!
        </div>
        
        <form action="" method="POST">
            <h3 style="margin-top: 0; color: #34495e; font-size: 18px;">Bạn đánh giá trải nghiệm tại khách sạn mấy sao?</h3>
            
            <div class="star-rating">
                <input type="radio" id="star5" name="so_sao" value="5" required>
                <label for="star5" title="5 sao (Tuyệt vời)">★</label>
                
                <input type="radio" id="star4" name="so_sao" value="4">
                <label for="star4" title="4 sao (Rất tốt)">★</label>
                
                <input type="radio" id="star3" name="so_sao" value="3">
                <label for="star3" title="3 sao (Hài lòng)">★</label>
                
                <input type="radio" id="star2" name="so_sao" value="2">
                <label for="star2" title="2 sao (Tạm được)">★</label>
                
                <input type="radio" id="star1" name="so_sao" value="1">
                <label for="star1" title="1 sao (Tệ)">★</label>
            </div>
            
            <textarea class="feedback-text" name="noi_dung_gop_y" placeholder="Mời bạn chia sẻ thêm cảm nhận, điều gì làm bạn hài lòng hoặc chưa hài lòng để chúng tôi phục vụ tốt hơn..." required></textarea>
            
            <button type="submit" name="btn_gui_gopy" class="btn-submit-feedback">Gửi Đánh Giá Ngay 🚀</button>
        </form>
    </div>
</div>

<?php 
// Gọi footer vào (nếu bồ có file footer.php, nếu không thì xóa dòng này đi)
if (file_exists('footer.php')) {
    include 'footer.php'; 
}
?>