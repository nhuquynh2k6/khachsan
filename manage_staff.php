<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] != 1) {
    header("Location: trangchu.php");
    exit();
}
include 'config.php';

$thong_bao = ""; $loai_thong_bao = "";
$current_admin_id = $_SESSION['user_id'] ?? 'admin01'; 

if (isset($_POST['btn_add_staff'])) {
    try {
        $conn->prepare("INSERT INTO nhan_vien (ma_nv, ho_ten, vai_tro, mat_khau, trang_thai, cccd_ho_chieu, email, so_dien_thoai) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")->execute([trim($_POST['txt_ma_nv']), trim($_POST['txt_fullname']), $_POST['txt_role'], password_hash($_POST['txt_password'], PASSWORD_DEFAULT), $_POST['txt_status'], trim($_POST['txt_cccd']), trim($_POST['txt_email']), trim($_POST['txt_phone'])]);
        $thong_bao = "Thêm nhân viên thành công!"; $loai_thong_bao = "success";
    } catch (Exception $e) { $thong_bao = "Lỗi thêm nhân viên!"; $loai_thong_bao = "error"; }
}

if (isset($_POST['btn_update_staff'])) {
    try {
        if (!empty($_POST['txt_password'])) {
            $conn->prepare("UPDATE nhan_vien SET ho_ten=?, vai_tro=?, trang_thai=?, cccd_ho_chieu=?, email=?, so_dien_thoai=?, mat_khau=? WHERE ma_nv=?")->execute([trim($_POST['txt_fullname']), $_POST['txt_role'], $_POST['txt_status'], trim($_POST['txt_cccd']), trim($_POST['txt_email']), trim($_POST['txt_phone']), password_hash($_POST['txt_password'], PASSWORD_DEFAULT), $_POST['txt_ma_nv']]);
        } else {
            $conn->prepare("UPDATE nhan_vien SET ho_ten=?, vai_tro=?, trang_thai=?, cccd_ho_chieu=?, email=?, so_dien_thoai=? WHERE ma_nv=?")->execute([trim($_POST['txt_fullname']), $_POST['txt_role'], $_POST['txt_status'], trim($_POST['txt_cccd']), trim($_POST['txt_email']), trim($_POST['txt_phone']), $_POST['txt_ma_nv']]);
        }
        $thong_bao = "Cập nhật thành công!"; $loai_thong_bao = "success";
    } catch (Exception $e) { $thong_bao = "Lỗi cập nhật!"; $loai_thong_bao = "error"; }
}

if (isset($_GET['delete'])) {
    if ($_GET['delete'] === $current_admin_id) { $thong_bao = "Không thể tự xóa mình!"; $loai_thong_bao = "error"; }
    else { try { $conn->prepare("DELETE FROM nhan_vien WHERE ma_nv=?")->execute([$_GET['delete']]); $thong_bao = "Đã xóa!"; $loai_thong_bao = "success"; } catch (Exception $e) { $thong_bao = "Lỗi xóa!"; $loai_thong_bao = "error"; } }
}

$staffs = $conn->query("SELECT * FROM nhan_vien ORDER BY ma_nv ASC")->fetchAll(PDO::FETCH_ASSOC);
$edit_staff = null; if(isset($_GET['edit'])) { $stmt=$conn->prepare("SELECT * FROM nhan_vien WHERE ma_nv=?"); $stmt->execute([$_GET['edit']]); $edit_staff=$stmt->fetch(PDO::FETCH_ASSOC); }

$page_title = "QUẢN LÝ NHÂN SỰ";
include 'admin_header.php';
?>

<?php if($thong_bao): ?> <div class="alert alert-<?= $loai_thong_bao ?>"> <?= $thong_bao ?> </div> <?php endif; ?>

<div class="form-card <?= $edit_staff ? 'edit-mode' : '' ?>">
    <h3 class="form-title"><?= $edit_staff ? "✏️ CẬP NHẬT: " . htmlspecialchars($edit_staff['ma_nv']) : "➕ THÊM NHÂN VIÊN" ?></h3>
    <form method="POST" class="form-grid">
        <div class="form-group"><label>Mã NV</label><input type="text" name="txt_ma_nv" <?= $edit_staff ? 'readonly' : 'required' ?> value="<?= $edit_staff['ma_nv'] ?? '' ?>"></div>
        <div class="form-group"><label>Họ Tên</label><input type="text" name="txt_fullname" required value="<?= $edit_staff['ho_ten'] ?? '' ?>"></div>
        <div class="form-group"><label>Mật khẩu</label><input type="password" name="txt_password" <?= $edit_staff ? '' : 'required' ?>></div>
        <div class="form-group"><label>Vai trò</label><select name="txt_role"><option value="Quản lý" <?= ($edit_staff['vai_tro']??'')=='Quản lý'?'selected':'' ?>>Quản lý</option><option value="Lễ tân" <?= ($edit_staff['vai_tro']??'')=='Lễ tân'?'selected':'' ?>>Lễ tân</option></select></div>
        <div class="form-group"><label>Trạng thái</label><select name="txt_status"><option value="Đang làm việc" <?= ($edit_staff['trang_thai']??'')=='Đang làm việc'?'selected':'' ?>>Đang làm việc</option><option value="Đã nghỉ" <?= ($edit_staff['trang_thai']??'')=='Đã nghỉ'?'selected':'' ?>>Đã nghỉ</option></select></div>
        <div class="form-group"><label>Số điện thoại</label><input type="text" name="txt_phone" required value="<?= $edit_staff['so_dien_thoai'] ?? '' ?>"></div>
        <div class="form-group"><label>Email</label><input type="email" name="txt_email" required value="<?= $edit_staff['email'] ?? '' ?>"></div>
        <div class="form-group"><label>CCCD</label><input type="text" name="txt_cccd" required value="<?= $edit_staff['cccd_ho_chieu'] ?? '' ?>"></div>
        <div class="full-width"><button type="submit" name="<?= $edit_staff ? 'btn_update_staff' : 'btn_add_staff' ?>" class="btn-submit">Xác nhận</button></div>
    </form>
</div>

<div class="table-section">
    <div class="table-header"><h3>👷‍♂️ DANH SÁCH NHÂN SỰ</h3></div>
    <table>
        <thead><tr><th>Mã NV</th><th>Họ Tên</th><th>Vai Trò</th><th>SĐT</th><th>Trạng Thái</th><th>Hành Động</th></tr></thead>
        <tbody>
            <?php foreach($staffs as $s): ?>
            <tr>
                <td><strong><?= htmlspecialchars($s['ma_nv']) ?></strong></td>
                <td><?= htmlspecialchars($s['ho_ten']) ?></td>
                <td><?= htmlspecialchars($s['vai_tro']) ?></td>
                <td><?= htmlspecialchars($s['so_dien_thoai']) ?></td>
                <td><span class="status-badge" style="<?= $s['trang_thai']=='Đang làm việc'?'background:#d4efdf;color:#27ae60;':'background:#fadbd8;color:#c0392b;' ?>"><?= htmlspecialchars($s['trang_thai']) ?></span></td>
                <td><a href="manage_staff.php?edit=<?= urlencode($s['ma_nv']) ?>" class="btn-action btn-edit">Sửa</a> <a href="manage_staff.php?delete=<?= urlencode($s['ma_nv']) ?>" class="btn-action btn-delete" onclick="return confirm('⚠️ Chắc chắn muốn xóa?')">Xóa</a></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include 'admin_footer.php'; ?>