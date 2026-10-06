<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] != 1) {
    header("Location: trangchu.php");
    exit();
}
include 'config.php';

function getPriceColumn($conn) { $cols = $conn->query("DESCRIBE dich_vu")->fetchAll(PDO::FETCH_COLUMN); return in_array('don_gia', $cols) ? 'don_gia' : 'gia_dich_vu'; }
$price_col = getPriceColumn($conn);

if (isset($_POST['add_service'])) {
    $conn->prepare("INSERT INTO dich_vu (ma_dich_vu, ten_dich_vu, $price_col, mo_ta, url_anh) VALUES (?, ?, ?, ?, ?)")->execute(['DV'.rand(1000,9999), $_POST['ten_dich_vu'], $_POST['gia_dich_vu'], $_POST['mo_ta'], $_POST['url_anh']]);
    header("Location: manage_services.php"); exit();
}
if (isset($_POST['update_service'])) {
    $conn->prepare("UPDATE dich_vu SET ten_dich_vu=?, $price_col=?, mo_ta=?, url_anh=? WHERE ma_dich_vu=?")->execute([$_POST['ten_dich_vu'], $_POST['gia_dich_vu'], $_POST['mo_ta'], $_POST['url_anh'], $_POST['ma_dich_vu']]);
    header("Location: manage_services.php"); exit();
}
if (isset($_GET['delete'])) {
    $conn->prepare("DELETE FROM dich_vu WHERE ma_dich_vu=?")->execute([$_GET['delete']]); header("Location: manage_services.php"); exit();
}

$edit_data = null;
if(isset($_GET['edit'])) { $stmt=$conn->prepare("SELECT * FROM dich_vu WHERE ma_dich_vu=?"); $stmt->execute([$_GET['edit']]); $edit_data=$stmt->fetch(PDO::FETCH_ASSOC); }
$services = $conn->query("SELECT * FROM dich_vu ORDER BY ma_dich_vu DESC")->fetchAll(PDO::FETCH_ASSOC);

$page_title = "QUẢN LÝ DỊCH VỤ ĐI KÈM";
include 'admin_header.php';
?>

<style>
    .img-preview { width: 80px; height: 60px; object-fit: cover; border-radius: 6px; border: 1px solid #ddd; box-shadow: 0 2px 5px rgba(0,0,0,0.1);}
</style>

<div class="form-card <?= $edit_data ? 'edit-mode' : '' ?>">
    <h3 class="form-title"><?= $edit_data ? '✏️ CẬP NHẬT DỊCH VỤ' : '➕ THÊM DỊCH VỤ MỚI' ?></h3>
    <form method="POST" class="form-grid">
        <?php if ($edit_data): ?><input type="hidden" name="ma_dich_vu" value="<?= htmlspecialchars($edit_data['ma_dich_vu']) ?>"><?php endif; ?>
        <div class="form-group"><label>Tên Dịch Vụ</label><input type="text" name="ten_dich_vu" required value="<?= $edit_data ? htmlspecialchars($edit_data['ten_dich_vu']) : '' ?>"></div>
        <div class="form-group"><label>Đơn Giá (VNĐ)</label><input type="number" name="gia_dich_vu" required value="<?= $edit_data ? htmlspecialchars($edit_data[$price_col]) : '' ?>"></div>
        <div class="form-group full-width"><label>Link Ảnh</label><input type="text" name="url_anh" value="<?= $edit_data ? htmlspecialchars($edit_data['url_anh'] ?? '') : '' ?>"></div>
        <div class="form-group full-width"><label>Mô Tả</label><textarea name="mo_ta" rows="2"><?= $edit_data ? htmlspecialchars($edit_data['mo_ta'] ?? '') : '' ?></textarea></div>
        <div class="full-width"><button type="submit" name="<?= $edit_data ? 'update_service' : 'add_service' ?>" class="btn-submit">Lưu Thay Đổi</button></div>
    </form>
</div>

<div class="table-section">
    <div class="table-header"><h3>✨ DANH SÁCH DỊCH VỤ</h3></div>
    <table>
        <thead><tr><th>Ảnh</th><th>Mã DV</th><th>Tên DV</th><th>Giá</th><th>Mô Tả</th><th>Hành Động</th></tr></thead>
        <tbody>
            <?php foreach ($services as $sv): ?>
            <tr>
                <td><img src="<?= htmlspecialchars($sv['url_anh']??'') ?>" class="img-preview"></td>
                <td><strong><?= htmlspecialchars($sv['ma_dich_vu']) ?></strong></td>
                <td><?= htmlspecialchars($sv['ten_dich_vu']) ?></td>
                <td style="color:#e74c3c; font-weight:bold;"><?= number_format($sv[$price_col],0,',','.') ?>đ</td>
                <td><?= htmlspecialchars(mb_strimwidth($sv['mo_ta']??'',0,40,'...')) ?></td>
                <td><a href="manage_services.php?edit=<?= urlencode($sv['ma_dich_vu']) ?>" class="btn-action btn-edit">Sửa</a> <a href="manage_services.php?delete=<?= urlencode($sv['ma_dich_vu']) ?>" class="btn-action btn-delete">Xóa</a></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include 'admin_footer.php'; ?>