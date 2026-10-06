<?php 
// 1. Khởi tạo session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'config.php'; 

// 2. XỬ LÝ LOGIC TÌM KIẾM VÀ LẤY DỮ LIỆU
$danh_sach_phong = []; 
$danh_sach_loai_phong = [];
$danh_sach_dv = [];

// Lấy dữ liệu từ URL khi khách nhấn nút Tìm Kiếm
$ngay_den = $_GET['ngay_den'] ?? '';
$ngay_di = $_GET['ngay_di'] ?? '';
$loai_phong_search = $_GET['loai_phong'] ?? '';

if (isset($conn)) {
    try {
        // --- LẤY DANH SÁCH LOẠI PHÒNG CHO THANH TÌM KIẾM ---
        $stmt_lp = $conn->prepare("SELECT ma_loai, ten_loai FROM loai_phong");
        $stmt_lp->execute();
        $danh_sach_loai_phong = $stmt_lp->fetchAll(PDO::FETCH_ASSOC);

        // --- LOGIC SQL ĐẾM SỐ PHÒNG TRỐNG CÙNG LOẠI ---
        $sql_phong = "
            SELECT p.*, lp.ten_loai,
            (
                SELECT COUNT(*) 
                FROM phong p2 
                WHERE p2.ma_loai = p.ma_loai 
                  AND p2.trang_thai = 'Trống' 
                  AND p2.so_phong_trong > 0
            ) as so_luong_trong_cung_loai
            FROM phong p 
            LEFT JOIN loai_phong lp ON p.ma_loai = lp.ma_loai 
            WHERE 1=1
        ";
        $params = [];

        // Lọc theo ngày (Bỏ phòng bị trùng lịch đặt)
        if (!empty($ngay_den) && !empty($ngay_di)) {
            $sql_phong .= " AND p.ma_phong NOT IN (
                SELECT ma_phong FROM dat_phong 
                WHERE (ngay_den < ? AND ngay_di > ?) 
                AND trang_thai != 'Đã hủy'
            )";
            $params[] = $ngay_di;
            $params[] = $ngay_den;
        }

        // Lọc theo loại phòng nếu khách có chọn
        if (!empty($loai_phong_search)) {
            $sql_phong .= " AND p.ma_loai = ?";
            $params[] = $loai_phong_search;
        }

        $sql_phong .= " ORDER BY p.ma_phong ASC";
        $stmt = $conn->prepare($sql_phong);
        $stmt->execute($params);
        $danh_sach_phong = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // --- LẤY DỮ LIỆU DỊCH VỤ ---
        $stmt_dv = $conn->prepare("SELECT * FROM dich_vu ORDER BY ma_dich_vu DESC");
        $stmt_dv->execute();
        $danh_sach_dv = $stmt_dv->fetchAll(PDO::FETCH_ASSOC);

    } catch (Exception $e) {
        // Bỏ qua lỗi hiển thị
    }
}

include 'header.php'; 
?>

<style>
    /* --- BỐ CỤC CHUNG --- */
    body { background-color: #f8f9fa; }
    .hero-section { position: relative; background-image: url('https://grandhoabinhhotel.com/pic/New/Phoi-canh_636380426831654370.jpg'); background-size: cover; background-position: center; height: 500px; display: flex; align-items: center; justify-content: center; }
    .hero-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); }
    .hero-content { position: relative; z-index: 1; text-align: center; color: white; }
    .hero-content h1 { font-size: 3.5rem; margin-bottom: 10px; text-shadow: 2px 2px 4px rgba(0,0,0,0.5); letter-spacing: 2px; text-transform: uppercase;}
    .hero-content p { font-size: 1.2rem; text-shadow: 1px 1px 3px rgba(0,0,0,0.5); font-style: italic; }

    /* THANH TÌM KIẾM */
    .search-bar { position: absolute; bottom: -45px; left: 50%; transform: translateX(-50%); background: #fff; padding: 25px 40px; border-radius: 12px; box-shadow: 0 15px 30px rgba(0,0,0,0.1); display: flex; gap: 25px; z-index: 2; width: 85%; max-width: 1000px; align-items: center; border-top: 4px solid #f1c40f;}
    .search-group { display: flex; flex-direction: column; text-align: left; flex: 1; }
    .search-group label { font-weight: bold; color: #1a1a1a; margin-bottom: 8px; font-size: 13px; text-transform: uppercase; letter-spacing: 1px;}
    .search-group input, .search-group select { padding: 12px 15px; border: 1px solid #e0e0e0; border-radius: 6px; font-size: 15px; outline: none; background: #f9f9f9; transition: 0.3s;}
    .search-group input:focus, .search-group select:focus { border-color: #f1c40f; background: #fff; box-shadow: 0 0 0 3px rgba(241,196,15,0.1);}
    .btn-search { background: #1a1a1a; color: #f1c40f; font-weight: bold; border: none; padding: 14px 30px; border-radius: 6px; cursor: pointer; transition: 0.3s; font-size: 15px; margin-top: 22px; text-transform: uppercase; letter-spacing: 1px;}
    .btn-search:hover { background: #333; transform: translateY(-2px); }

    .rooms-container { max-width: 1200px; margin: 120px auto 50px auto; padding: 0 20px; font-family: Arial, sans-serif; }
    .section-title { text-align: center; color: #1a1a1a; text-transform: uppercase; font-size: 32px; margin-bottom: 10px; font-weight: bold; letter-spacing: 1px;}
    .title-line { width: 80px; height: 4px; background-color: #f1c40f; margin: 0 auto 50px auto; border-radius: 2px;}
    
    /* --- BỐ CỤC GIỚI THIỆU MỚI (LUXURY) --- */
    .intro { display: flex; align-items: stretch; background: #fff; border-radius: 16px; box-shadow: 0 10px 40px rgba(0,0,0,0.06); margin: 40px auto 100px auto; overflow: hidden; max-width: 1100px;}
    .intro-img-wrap { width: 50%; position: relative; }
    .intro-img-wrap img { width: 100%; height: 100%; object-fit: cover; }
    .intro-text { width: 50%; padding: 60px 50px; display: flex; flex-direction: column; justify-content: center; }
    .intro-text h2 { color: #1a1a1a; font-size: 32px; margin-bottom: 15px; text-transform: uppercase; font-weight: bold; letter-spacing: 2px; margin-top: 0;}
    .intro-line { width: 60px; height: 4px; background: #f1c40f; margin-bottom: 25px; border-radius: 2px;}
    .intro-text p { font-size: 16px; color: #555; line-height: 1.8; margin-bottom: 30px; text-align: justify;}
    .btn-intro { display: inline-block; padding: 12px 35px; border: 2px solid #1a1a1a; color: #1a1a1a; text-decoration: none; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; transition: 0.4s; align-self: flex-start; border-radius: 6px;}
    .btn-intro:hover { background: #1a1a1a; color: #f1c40f; }

    /* DANH SÁCH PHÒNG */
    .room-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 35px; }
    .room-card { background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 5px 20px rgba(0,0,0,0.05); transition: 0.4s ease; border: none; display: flex; flex-direction: column; position: relative;}
    .room-card:hover { transform: translateY(-10px); box-shadow: 0 15px 35px rgba(0,0,0,0.1); }
    
    .room-img-wrapper { position: relative; height: 240px; width: 100%; overflow: hidden;}
    .room-img { width: 100%; height: 100%; object-fit: cover; transition: 0.5s; }
    .room-card:hover .room-img { transform: scale(1.05); }
    .room-badge { position: absolute; top: 15px; right: 15px; padding: 6px 15px; border-radius: 20px; font-size: 12px; font-weight: bold; color: white; z-index: 5; text-transform: uppercase; letter-spacing: 1px; box-shadow: 0 2px 10px rgba(0,0,0,0.2);}
    
    .badge-trong { background-color: #2ecc71; }
    .badge-baotri { background-color: #e74c3c; }
    .badge-luutru { background-color: #3498db; }
    .sold-out-badge { position: absolute; top: 25px; right: -40px; background: #e74c3c; color: white; padding: 7px 45px; font-weight: bold; font-size: 12px; transform: rotate(45deg); box-shadow: 0 2px 10px rgba(0,0,0,0.3); z-index: 10; letter-spacing: 2px; text-transform: uppercase; }

    .room-info { padding: 25px; display: flex; flex-direction: column; flex-grow: 1; text-align: left;}
    .room-type { font-size: 12px; color: #f1c40f; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px; font-weight: bold;}
    .room-title { font-size: 24px; color: #1a1a1a; margin: 0 0 10px 0; font-weight: bold;}
    .room-price { color: #1a1a1a; font-size: 22px; font-weight: bold; margin-bottom: 15px; border-bottom: 1px dashed #eee; padding-bottom: 15px;}
    .room-price span { font-size: 14px; color: #888; font-weight: normal; }
    .room-desc { color: #666; font-size: 14px; line-height: 1.6; margin-bottom: 25px; flex-grow: 1; }
    
    .btn-book { display: block; text-align: center; background: #1a1a1a; color: #f1c40f; padding: 14px; text-decoration: none; border-radius: 6px; font-weight: bold; transition: 0.3s; font-size: 14px; text-transform: uppercase; letter-spacing: 1px;}
    .btn-book:hover { background: #333; }
    .btn-disabled { display: block; text-align: center; background: #bdc3c7; color: white; padding: 14px; border-radius: 6px; font-weight: bold; cursor: not-allowed; font-size: 14px; letter-spacing: 1px;}
	
    /* --- BỐ CỤC DỊCH VỤ MỚI --- */
    .service-card { background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 5px 20px rgba(0,0,0,0.05); transition: 0.4s ease; text-align: center; display: flex; flex-direction: column; border: none;}
    .service-card:hover { transform: translateY(-10px); box-shadow: 0 15px 35px rgba(0,0,0,0.1); }
    .service-img { width: 100%; height: 220px; object-fit: cover; }
    .service-info { padding: 25px; flex-grow: 1; display: flex; flex-direction: column; }
    .service-info h3 { color: #1a1a1a; font-size: 22px; text-transform: uppercase; margin-top: 0; margin-bottom: 15px;}
    .srv-price { background: #1a1a1a; color: #f1c40f; font-weight: bold; padding: 8px 20px; border-radius: 30px; display: inline-block; align-self: center; margin-bottom: 15px; font-size: 16px;}
    .service-info p { color: #666; font-size: 14px; line-height: 1.6; margin: 0;}
	
    /* --- BỐ CỤC TIN TỨC MỚI --- */
    .news { margin: 100px auto; max-width: 1200px; padding: 0 20px;}
    .news-list { display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 40px; }
    .news-item { background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 5px 20px rgba(0,0,0,0.05); transition: 0.4s; border: none; text-align: left;}
    .news-item:hover { transform: translateY(-10px); box-shadow: 0 15px 35px rgba(0,0,0,0.1); }
    .news-img-wrap { height: 250px; overflow: hidden; }
    .news-img-wrap img { width: 100%; height: 100%; object-fit: cover; transition: 0.5s; }
    .news-item:hover .news-img-wrap img { transform: scale(1.05); }
    
    .news-content { padding: 30px; position: relative; }
    .news-content .date { position: absolute; top: -20px; left: 30px; background: #f1c40f; color: #1a1a1a; padding: 8px 20px; font-weight: bold; font-size: 13px; border-radius: 4px; box-shadow: 0 4px 10px rgba(241,196,15,0.3); text-transform: uppercase; letter-spacing: 1px;}
    .news-content h3 { color: #1a1a1a; font-size: 22px; margin: 15px 0 15px 0; line-height: 1.4; transition: 0.3s;}
    .news-item:hover .news-content h3 { color: #f1c40f; }
    .news-content .desc { font-size: 15px; color: #666; line-height: 1.6; margin-bottom: 20px;}
    .read-more { color: #1a1a1a; font-weight: bold; text-decoration: none; text-transform: uppercase; font-size: 13px; letter-spacing: 1px; display: inline-flex; align-items: center; gap: 5px; transition: 0.3s; border-bottom: 2px solid transparent; padding-bottom: 2px;}
    .read-more:hover { color: #f1c40f; border-bottom: 2px solid #f1c40f;}
</style>

<div class="hero-section">
    <div class="hero-overlay"></div>
    <div class="hero-content">
        <h1>Welcome to Three Beautiful Girl</h1>
        <p>Đẳng cấp nghỉ dưỡng – Nâng tầm trải nghiệm</p>
    </div>
    
    <form action="trangchu.php#xemphong" method="GET" class="search-bar">
        <div class="search-group">
            <label>Ngày nhận phòng</label>
            <input type="date" name="ngay_den" value="<?= htmlspecialchars($ngay_den) ?>" required min="<?= date('Y-m-d') ?>">
        </div>
        <div class="search-group">
            <label>Ngày trả phòng</label>
            <input type="date" name="ngay_di" value="<?= htmlspecialchars($ngay_di) ?>" required min="<?= date('Y-m-d', strtotime('+1 day')) ?>">
        </div>
        <div class="search-group">
            <label>Hạng phòng</label>
            <select name="loai_phong">
                <option value="">-- Tất cả hạng phòng --</option>
                <?php foreach ($danh_sach_loai_phong as $lp): ?>
                    <option value="<?= htmlspecialchars($lp['ma_loai']) ?>" 
                        <?= ($loai_phong_search == $lp['ma_loai']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($lp['ten_loai']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn-search">TÌM KIẾM ➔</button>
    </form>
</div>

<div class="rooms-container">
    
    <section class="intro" id="gioithieu">
        <div class="intro-img-wrap">
            <img src="https://acihome.vn/uploads/15/thiet-ke-khach-san-hien-dai-co-cac-ban-cong-view-bien-sieu-dep-seaside-mirage-hotel-1.JPG" alt="Giới thiệu">
        </div>
        <div class="intro-text">
            <h2>Về chúng tôi</h2>
            <div class="intro-line"></div>
            <p>Three Beautiful Girl Hotel tự hào mang đến cho bạn trải nghiệm nghỉ dưỡng xa hoa, tiện nghi và tinh tế nhất. Với vị trí đắc địa ngay trung tâm thành phố, hệ thống phòng ốc đạt chuẩn quốc tế cùng đội ngũ phục vụ chuyên nghiệp, chúng tôi chính là điểm dừng chân hoàn hảo để bạn tận hưởng những khoảnh khắc đáng nhớ trong mỗi chuyến đi.</p>
            <a href="#xemphong" class="btn-intro">Khám phá ngay</a>
        </div>
    </section>

    <h2 class="section-title" id="xemphong">KHÁM PHÁ PHÒNG NGHỈ CỦA CHÚNG TÔI</h2>
    <div class="title-line"></div>

    <?php if (count($danh_sach_phong) > 0): ?>
        <div class="room-grid">
            <?php foreach ($danh_sach_phong as $phong): ?>
                <?php
                    $trang_thai = trim($phong['trang_thai'] ?? 'Trống');
                    $so_trong_cung_loai = (int)($phong['so_luong_trong_cung_loai'] ?? 0);
                    
                    $is_het_phong = false;
                    $badge_class = 'badge-trong';
                    $badge_text = 'Sẵn sàng';

                    if ($trang_thai != 'Trống' || $phong['so_phong_trong'] <= 0) {
                        if ($so_trong_cung_loai == 0) {
                            $is_het_phong = true;
                            $badge_class = 'badge-baotri';
                            $badge_text = 'Hết phòng';
                        } else {
                            $badge_class = 'badge-luutru';
                            $badge_text = 'Còn phòng cùng loại';
                        }
                    } else {
                        $badge_class = 'badge-trong';
                        $badge_text = 'Sẵn sàng';
                    }
                ?>
                
                <div class="room-card" <?= !$is_het_phong ? "onclick=\"window.location.href='room.php?ma_phong=".urlencode($phong['ma_phong'])."'\" style=\"cursor:pointer;\"" : "style=\"opacity: 0.85;\"" ?>>
                    <div class="room-img-wrapper">
                        <img src="<?= htmlspecialchars($phong['url_phong']) ?>" alt="Ảnh phòng" class="room-img">
                        <span class="room-badge <?= $badge_class ?>"><?= htmlspecialchars($badge_text) ?></span>
                        
                        <?php if ($is_het_phong): ?>
                            <div class="sold-out-badge">HẾT PHÒNG</div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="room-info">
                        <div class="room-type">♦ <?= htmlspecialchars($phong['ten_loai'] ?? $phong['ma_loai']) ?></div>
                        <h3 class="room-title"><?= htmlspecialchars($phong['ten_phong']) ?></h3>
                        
                        <div class="room-price"><?= number_format($phong['gia_phong'], 0, ',', '.') ?> đ <span>/ đêm</span></div>
                        <p class="room-desc"><?= htmlspecialchars(mb_strimwidth($phong['chi_tiet_phong'], 0, 110, '...')) ?></p>

                        <?php if (!$is_het_phong): ?>
                            <a href="room.php?ma_phong=<?= urlencode($phong['ma_phong']) ?>" class="btn-book">ĐẶT PHÒNG NGAY</a>
                        <?php else: ?>
                            <div class="btn-disabled">🚫 ĐÃ KÍN CHỖ</div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p style="text-align: center; color: #e74c3c; font-weight: bold; padding: 40px; background: #fff; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.05);">
            Rất tiếc! Không tìm thấy phòng trống phù hợp với yêu cầu của bạn.
        </p>
    <?php endif; ?>

    <div id="dichvu" style="margin-top: 120px;">
        <h2 class="section-title">DỊCH VỤ ĐI KÈM ĐẲNG CẤP</h2>
        <div class="title-line"></div>
        <div class="room-grid">
            <?php foreach ($danh_sach_dv as $dv): ?>
                <div class="service-card">
                    <img src="<?= htmlspecialchars($dv['url_anh'] ?? 'https://via.placeholder.com/300') ?>" class="service-img">
                    <div class="service-info">
                        <h3><?= htmlspecialchars($dv['ten_dich_vu']) ?></h3>
                        <div class="srv-price"><?= number_format($dv['don_gia'], 0, ',', '.') ?> VNĐ</div>
                        <p><?= htmlspecialchars($dv['mo_ta'] ?? '') ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

</div> <section class="news" id="tintuc">
    <h2 class="section-title">TIN TỨC & SỰ KIỆN</h2>
    <div class="title-line"></div>
    <div class="news-list">
        <div class="news-item">
            <div class="news-img-wrap">
                <img src="https://cdn3.ivivu.com/2024/09/cho-noi-cai-rang-iVIVU-e1725530721962.jpg" alt="Tin tức">
            </div>
            <div class="news-content">
                <span class="date">07 Thg 01, 2026</span>
                <h3>Cần Thơ – Điểm đến sông nước hấp dẫn 2026</h3>
                <p class="desc">Năm 2026, Cần Thơ tiếp tục thu hút du khách với những trải nghiệm đặc trưng miền Tây, kết hợp du lịch sinh thái và văn hóa bản địa độc đáo...</p>
                <a href="#" class="read-more">Đọc tiếp ➔</a>
            </div>
        </div>
        <div class="news-item">
            <div class="news-img-wrap">
                <img src="https://media.istockphoto.com/id/1300135335/vi/anh/n%E1%BB%99i-th%E1%BA%A5t-ph%C3%B2ng-ng%E1%BB%A7-sang-tr%E1%BB%8Dng-t%E1%BA%A1i-nigh-v%E1%BB%9Bi-gi%C6%B0%E1%BB%9Dng-l%E1%BB%99n-x%E1%BB%99n-gh%E1%BA%BF-b%C3%A0nh-da-t%E1%BB%A7-qu%E1%BA%A7n-%C3%A1o-v%C3%A0-t%E1%BA%A7m-nh%C3%ACn-ra.jpg?s=612x612&w=0&k=20&c=28lofzl06lY49tFWvnb-pVwu2tBbn_nHEoU6RF1rrfI=" alt="Tin tức">
            </div>
            <div class="news-content">
                <span class="date">10 Thg 02, 2026</span>
                <h3>Ra mắt tổ hợp Dịch vụ Spa & Hội nghị 5 sao</h3>
                <p class="desc">Three Beautiful Girl Hotel chính thức giới thiệu không gian spa trị liệu chuẩn quốc tế cùng phòng hội nghị thông minh bậc nhất khu vực...</p>
                <a href="#" class="read-more">Đọc tiếp ➔</a>
            </div>
        </div>
    </div>
</section>

<?php include 'footer.php'; ?>