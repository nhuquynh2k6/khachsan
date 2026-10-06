<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] != 1) {
    header("Location: trangchu.php");
    exit();
}
include 'config.php';

$success_msg = ""; $error_msg = "";

if (isset($_GET['delete'])) {
    try {
        $conn->prepare("DELETE FROM khach_hang WHERE cccd_ho_chieu = ?")->execute([trim($_GET['delete'])]);
        $success_msg = "Đã xóa hồ sơ khách hàng thành công!";
    } catch (Exception $e) { $error_msg = "Lỗi: Khách hàng đang có đơn đặt phòng liên kết."; }
}

$khach_hangs = $conn->query("SELECT cccd_ho_chieu, ho_ten, so_dien_thoai, email FROM khach_hang ORDER BY ho_ten ASC")->fetchAll(PDO::FETCH_ASSOC);

$page_title = "HỒ SƠ KHÁCH HÀNG";
include 'admin_header.php';
?>

<style>
    .btn-delete { background: #ffeaa7; color: #d63031; border: 1px solid #fab1a0; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 12px; font-weight: bold; text-decoration: none; transition: 0.2s;}
    .btn-delete:hover { background: #ff7675; color: white; border-color: #d63031;}
</style>

<?php if($success_msg): ?> <div class="alert alert-success">✔️ <?= $success_msg ?></div> <?php endif; ?>
<?php if($error_msg): ?> <div class="alert alert-error">❌ <?= $error_msg ?></div> <?php endif; ?>

<div class="table-section">
    <div class="table-header"><h3>👥 Danh sách thông tin Khách hàng</h3></div>
    <table>
        <thead><tr><th>CCCD/Hộ chiếu</th><th>Họ và Tên</th><th>Số điện thoại</th><th>Email</th><th>Hành động</th></tr></thead>
        <tbody>
            <?php if (count($khach_hangs) > 0): ?>
                <?php foreach ($khach_hangs as $kh): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($kh['cccd_ho_chieu']) ?></strong></td>
                    <td><?= htmlspecialchars($kh['ho_ten']) ?></td>
                    <td><?= htmlspecialchars($kh['so_dien_thoai']) ?></td>
                    <td><?= htmlspecialchars($kh['email'] ?? 'Chưa cập nhật') ?></td>
                    <td><a href="manage_users.php?delete=<?= urlencode($kh['cccd_ho_chieu']) ?>" class="btn-delete" onclick="return confirm('⚠️ CẢNH BÁO: Xóa hồ sơ này?');">Xóa</a></td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="5" style="text-align: center; color: #999; padding: 30px;">Chưa có dữ liệu khách hàng.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include 'admin_footer.php'; ?>