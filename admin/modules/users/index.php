<?php
require_once '../../../includes/db.php';
require_once '../../../includes/functions.php'; // Gọi functions để dùng getImageUrl
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';

// Lấy danh sách user chưa bị xóa
$stmt = $pdo->query("SELECT * FROM users WHERE IsDeleted = 0 ORDER BY CreatedAt DESC");
$users = $stmt->fetchAll();
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold m-0">Quản lý Thành viên</h3>
        <a href="create.php" class="btn btn-naver px-4 py-2 rounded-pill">
            <i class="fas fa-user-plus me-2"></i>Thêm thành viên
        </a>
    </div>

    <div class="card-custom p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th class="rounded-start border-0">ID</th>
                        <th class="border-0">Avatar</th>
                        <th class="border-0">Tên đăng nhập</th>
                        <th class="border-0">Email</th>
                        <th class="border-0">Vai trò</th>
                        <th class="border-0">Ngày tạo</th>
                        <th class="rounded-end text-end border-0">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                    <tr>
                        <td class="fw-bold text-muted">#<?= $u['UserID'] ?></td>
                        <td>
                            <?php 
                                $avatarUrl = !empty($u['Avatar']) ? getImageUrl($u['Avatar']) : 'https://ui-avatars.com/api/?name=' . urlencode($u['UserName']) . '&background=random&color=fff';
                            ?>
                            <img src="<?= $avatarUrl ?>" class="rounded-circle border border-secondary" width="40" height="40" style="object-fit: cover;">
                        </td>
                        <td class="fw-bold"><?= htmlspecialchars($u['UserName']) ?></td>
                        <td><?= htmlspecialchars($u['Email']) ?></td>
                        <td>
                            <?php if($u['Role'] == 1): ?>
                                <span class="badge bg-danger bg-opacity-10 text-danger px-3 py-1 rounded-pill">Admin</span>
                            <?php else: ?>
                                <span class="badge bg-secondary bg-opacity-10 text-secondary px-3 py-1 rounded-pill">Member</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-muted small"><?= date('d/m/Y', strtotime($u['CreatedAt'])) ?></td>
                        <td class="text-end">
                            <a href="edit.php?id=<?= $u['UserID'] ?>" class="btn btn-sm btn-light text-warning me-1"><i class="fas fa-edit"></i></a>
                            
                            <?php if(isset($_SESSION['user_id']) && $_SESSION['user_id'] == $u['UserID']): ?>
                                <button class="btn btn-sm btn-light text-muted" disabled title="Không thể xóa chính mình"><i class="fas fa-trash"></i></button>
                            <?php else: ?>
                                <a href="delete.php?id=<?= $u['UserID'] ?>" class="btn btn-sm btn-light text-danger" onclick="return confirm('Bạn có chắc muốn xóa thành viên này?')" title="Xóa"><i class="fas fa-trash"></i></a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>