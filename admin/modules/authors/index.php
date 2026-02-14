<?php
require_once '../../includes/init.php';
require_once '../../../includes/db.php';
require_once '../../../includes/functions.php';
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';

// Xử lý Xóa (Soft Delete)
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = $_GET['id'];
    $pdo->prepare("UPDATE authors SET IsDeleted = 1 WHERE AuthorID = ?")->execute([$id]);
    echo "<script>window.location.href='index.php';</script>";
}

// Cấu hình Phân trang & Tìm kiếm
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';

// Query
$sqlWhere = "WHERE IsDeleted = 0";
$params = [];

if (!empty($keyword)) {
    $sqlWhere .= " AND Name LIKE ?";
    $params[] = "%$keyword%";
}

// Đếm tổng
$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM authors $sqlWhere");
$stmtCount->execute($params);
$totalRecords = $stmtCount->fetchColumn();
$totalPages = ceil($totalRecords / $limit);

// Lấy dữ liệu
$sqlData = "SELECT * FROM authors $sqlWhere ORDER BY AuthorID DESC LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sqlData);
$stmt->execute($params);
$authors = $stmt->fetchAll();
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold m-0">Quản lý Tác giả</h3>
        <a href="add.php" class="btn btn-success"><i class="fas fa-plus me-2"></i>Thêm mới</a>
    </div>

    <div class="card-custom p-3 mb-4">
        <form method="GET" class="d-flex gap-2">
            <input type="text" name="keyword" class="form-control" placeholder="Tìm tên tác giả..." value="<?= htmlspecialchars($keyword) ?>" style="max-width: 300px;">
            <button type="submit" class="btn btn-primary">Tìm</button>
        </form>
    </div>

    <div class="card-custom p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th style="width: 100px;">Ảnh</th>
                        <th>Tên tác giả</th>
                        <th>Mô tả</th>
                        <th class="text-end" style="width: 150px;">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($authors as $auth): ?>
                    <tr>
                        <td class="text-muted">#<?= $auth['AuthorID'] ?></td>
                        <td>
                            <?php if (!empty($auth['Avatar'])): ?>
                                <img src="<?= getImageUrl($auth['Avatar']) ?>" class="rounded" width="60" height="60" style="object-fit: cover; border: 1px solid #444;">
                            <?php else: ?>
                                <span class="text-muted small">No Img</span>
                            <?php endif; ?>
                        </td>
                        <td class="fw-bold"><?= htmlspecialchars($auth['Name']) ?></td>
                        <td>
                            <span class="text-muted small" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                <?= htmlspecialchars($auth['Description']) ?>
                            </span>
                        </td>
                        <td class="text-end">
                            <a href="edit.php?id=<?= $auth['AuthorID'] ?>" class="btn btn-sm btn-info me-2" title="Sửa"><i class="fas fa-edit"></i></a>
                            <a href="index.php?action=delete&id=<?= $auth['AuthorID'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Xóa tác giả này?')" title="Xóa"><i class="fas fa-trash"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if(empty($authors)) echo '<tr><td colspan="5" class="text-center py-4 text-muted">Không tìm thấy dữ liệu.</td></tr>'; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
        <nav class="mt-4 d-flex justify-content-center">
            <ul class="pagination">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>&keyword=<?= urlencode($keyword) ?>"><?= $i ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>