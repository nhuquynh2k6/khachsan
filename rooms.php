<?php 
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] != 1) {
    header("Location: trangchu.php");
    exit();
}
include 'config.php'; 

$thong_bao = ""; $loai_thong_bao = "";

if (isset($_POST['btn_them_phong'])) {
    $ma = trim($_POST['txt_maphong']); $ten = trim($_POST['txt_tenphong']); $ma_loai = trim($_POST['txt_maloai']); $gia = trim($_POST['txt_gia']); $url = trim($_POST['txt_url']); $chi_tiet = trim($_POST['txt_chitiet']); $trang_thai = $_POST['txt_trangthai'];
    try {
        $conn->prepare("INSERT INTO phong (ma_phong, ten_phong, ma_loai, gia_phong, url_phong, chi_tiet_phong, trang_thai) VALUES (?, ?, ?, ?, ?, ?, ?)")->execute([$ma, $ten, $ma_loai, $gia, $url, $chi_tiet, $trang_thai]);
        $thong_bao = "Thêm phòng mới thành công!"; $loai_thong_bao = "success";
    } catch (Exception $e) { $thong_bao = "Lỗi: Kiểm tra lại mã phòng!"; $loai_thong_bao = "error"; }
}

if (isset($_POST['btn_luu_sua'])) {
    $ma = $_POST['txt_maphong']; $ten = trim($_POST['txt_tenphong']); $ma_loai = trim($_POST['txt_maloai']); $gia = trim($_POST['txt_gia']); $url = trim($_POST['txt_url']); $chi_tiet = trim($_POST['txt_chitiet']); $trang_thai = $_POST['txt_trangthai'];
    try {
        $conn->prepare("UPDATE phong SET ten_phong=?, ma_loai=?, gia_phong=?, url_phong=?, chi_tiet_phong=?, trang_thai=? WHERE ma_phong=?")->execute([$ten, $ma_loai, $gia, $url, $chi_tiet, $trang_thai, $ma]);
        $thong_bao = "Cập nhật phòng thành công!"; $loai_thong_bao = "success";
    } catch (Exception $e) { $thong_bao = "Lỗi cập nhật!"; $loai_thong_bao = "error"; }
}

if (isset($_GET['delete'])) {
    try { 
        $conn->prepare("DELETE FROM phong WHERE ma_phong=?")->execute([$_GET['delete']]); 
        $thong_bao = "Đã xóa phòng!"; $loai_thong_bao = "success";
    } catch (Exception $e) { $thong_bao = "Không thể xóa do vướng dữ liệu!"; $loai_thong_bao = "error"; }
}

$rooms = $conn->query("SELECT * FROM phong ORDER BY ma_phong ASC")->fetchAll();
$phong_dang_sua = null;
if (isset($_GET['edit'])) {
    $stmt = $conn->prepare("SELECT * FROM phong WHERE ma_phong=?"); $stmt->execute([$_GET['edit']]); $phong_dang_sua = $stmt->fetch();
}

$page_title = "QUẢN LÝ PHÒNG VÀ LOẠI PHÒNG";
include 'admin_header.php'; 
?>

<style>
    .st-trong { background: #d4efdf; color: #27ae60; } .st-luutru { background: #d6eaf8; color: #2980b9; } .st-dondep { background: #fcf3cf; color: #f39c12; } .st-baotri { background: #fadbd8; color: #c0392b; }
    .img-preview { width: 80px; height: 50px; object-fit: cover; border-radius: 4px; border: 1px solid #ddd;}
</style>

<?php if($thong_bao != ""): ?>
    <div class="alert <?= $loai_thong_bao == 'success' ? 'alert-success' : 'alert-error' ?>">
        <?= $loai_thong_bao == 'success' ? '✔️' : '❌' ?> <?= $thong_bao ?>
    </div>
<?php endif; ?>

<?php if ($phong_dang_sua): ?>
<div class="form-card edit-mode">
    <h3 class="form-title">✏️ CẬP NHẬT PHÒNG: <span style="color:#3498db;"><?= htmlspecialchars($phong_dang_sua['ma_phong']) ?></span></h3>
    <form method="POST" action="rooms.php" class="form-grid">
        <input type="hidden" name="txt_maphong" value="<?= htmlspecialchars($phong_dang_sua['ma_phong']) ?>">
        <div class="form-group"><label>Tên Phòng</label><input type="text" name="txt_tenphong" value="<?= htmlspecialchars($phong_dang_sua['ten_phong'] ?? '') ?>" required></div>
        <div class="form-group"><label>Mã Loại</label><input type="text" name="txt_maloai" value="<?= htmlspecialchars($phong_dang_sua['ma_loai'] ?? '') ?>" required></div>
        <div class="form-group"><label>Giá Phòng</label><input type="number" name="txt_gia" value="<?= htmlspecialchars($phong_dang_sua['gia_phong'] ?? '') ?>" required></div>
        <div class="form-group"><label>Trạng Thái</label>
            <select name="txt_trangthai">
                <option value="Trống" <?= ($phong_dang_sua['trang_thai'] == 'Trống')?'selected':'' ?>>Trống</option>
                <option value="Đang lưu trú" <?= ($phong_dang_sua['trang_thai'] == 'Đang lưu trú')?'selected':'' ?>>Đang lưu trú</option>
                <option value="Đang dọn dẹp" <?= ($phong_dang_sua['trang_thai'] == 'Đang dọn dẹp')?'selected':'' ?>>Đang dọn dẹp</option>
                <option value="Bảo trì" <?= ($phong_dang_sua['trang_thai'] == 'Bảo trì')?'selected':'' ?>>Bảo trì</option>
            </select>
        </div>
        <div class="form-group full-width"><label>URL Hình Ảnh</label><input type="text" name="txt_url" value="<?= htmlspecialchars($phong_dang_sua['url_phong'] ?? '') ?>"></div>
        <div class="form-group full-width"><label>Chi Tiết</label><textarea name="txt_chitiet" rows="3"><?= htmlspecialchars($phong_dang_sua['chi_tiet_phong'] ?? '') ?></textarea></div>
        <div class="full-width" style="margin-top: 10px;">
            <button type="submit" name="btn_luu_sua" class="btn-submit">💾 Lưu Thay Đổi</button>
            <a href="rooms.php" class="btn-cancel">Hủy bỏ</a>
        </div>
    </form>
</div>
<?php else: ?>
<div class="form-card">
    <h3 class="form-title">➕ THÊM PHÒNG MỚI</h3>
    <form method="POST" action="rooms.php" class="form-grid">
        <div class="form-group"><label>Mã Phòng</label><input type="text" name="txt_maphong" required></div>
        <div class="form-group"><label>Tên Phòng</label><input type="text" name="txt_tenphong" required></div>
        <div class="form-group"><label>Mã Loại</label><input type="text" name="txt_maloai" required></div>
        <div class="form-group"><label>Giá Phòng</label><input type="number" name="txt_gia" required></div>
        <div class="form-group full-width"><label>Trạng Thái</label>
            <select name="txt_trangthai"><option value="Trống">Trống</option><option value="Đang lưu trú">Đang lưu trú</option><option value="Đang dọn dẹp">Đang dọn dẹp</option><option value="Bảo trì">Bảo trì</option></select>
        </div>
        <div class="form-group full-width"><label>URL Hình Ảnh</label><input type="text" name="txt_url"></div>
        <div class="form-group full-width"><label>Chi Tiết Phòng</label><textarea name="txt_chitiet" rows="3"></textarea></div>
        <div class="full-width" style="margin-top: 10px;"><button type="submit" name="btn_them_phong" class="btn-submit">+ Xác nhận thêm</button></div>
    </form>
</div>
<?php endif; ?>

<div class="table-section">
    <div class="table-header"><h3>🛏️ DANH SÁCH PHÒNG CHI TIẾT</h3></div>
    <table>
        <thead>
            <tr><th>Mã</th><th>Ảnh</th><th>Tên Phòng</th><th>Mã Loại</th><th>Giá (VNĐ)</th><th>Trạng Thái</th><th style="min-width: 130px;">Hành Động</th></tr>
        </thead>
        <tbody>
            <?php foreach ($rooms as $phong): 
                $tt = trim($phong['trang_thai']);
                $badge = ($tt=='Bảo trì')?'st-baotri':(($tt=='Đang dọn dẹp')?'st-dondep':(($tt=='Đang lưu trú')?'st-luutru':'st-trong'));
            ?>
            <tr style="cursor:pointer;" onclick="window.location.href='room.php?ma_phong=<?= urlencode($phong['ma_phong']) ?>'">
                <td><strong><?= htmlspecialchars($phong['ma_phong']) ?></strong></td>
                <td><img src="<?= htmlspecialchars($phong['url_phong'] ?? '') ?>" class="img-preview" alt="Ảnh"></td>
                <td><?= htmlspecialchars($phong['ten_phong'] ?? '') ?></td>
                <td><?= htmlspecialchars($phong['ma_loai'] ?? '') ?></td>
                <td style="color: #d35400; font-weight: bold;"><?= number_format($phong['gia_phong'] ?? 0, 0, ',', '.') ?>đ</td>
                <td><span class="status-badge <?= $badge ?>"><?= htmlspecialchars($phong['trang_thai']) ?></span></td>
                <td onclick="event.stopPropagation();"> 
                    <a href="rooms.php?edit=<?= urlencode($phong['ma_phong']) ?>" class="btn-action btn-edit">Sửa</a>
                    <a href="rooms.php?delete=<?= urlencode($phong['ma_phong']) ?>" class="btn-action btn-delete" onclick="return confirm('Xóa phòng này?')">Xóa</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include 'admin_footer.php'; ?>