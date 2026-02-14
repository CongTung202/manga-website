<?php
require_once '../../includes/init.php';
require_once '../../../includes/db.php';
require_once '../../../includes/functions.php'; // Quan trọng: Gọi functions để dùng getImageUrl
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';

$sql = "SELECT a.*, c.Name as CategoryName 
        FROM articles a 
        LEFT JOIN categories c ON a.CategoryID = c.CategoryID 
        WHERE a.IsDeleted = 0 
        ORDER BY a.CreatedAt DESC";
$stmt = $pdo->query($sql);
$articles = $stmt->fetchAll();
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold m-0">Quản lý Truyện tranh</h3>
        <a href="create.php" class="btn btn-naver px-4 py-2 rounded-pill">
            <i class="fas fa-plus me-2"></i>Thêm mới
        </a>
    </div>

    <div class="card-custom p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th class="border-0 rounded-start">ID</th>
                        <th class="border-0" style="width: 80px;">Ảnh</th>
                        <th class="border-0">Tên truyện</th>
                        <th class="border-0">Nguồn gốc</th>
                        <th class="border-0">Trạng thái</th>
                        <th class="border-0">View</th>
                        <th class="border-0 rounded-end text-end">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($articles as $art): ?>
                    <tr>
                        <td class="fw-bold text-secondary">#<?= $art['ArticleID'] ?></td>
                        <td>
                            <img src="<?= getImageUrl($art['CoverImage']) ?>" class="rounded shadow-sm" width="50" height="70" style="object-fit: cover;">
                        </td>
                        <td>
                            <div class="fw-bold"><?= htmlspecialchars($art['Title']) ?></div>
                            <small class="text-muted"><?= date('d/m/Y', strtotime($art['CreatedAt'])) ?></small>
                        </td>
                        <td>
                            <?php if($art['CategoryName']): ?>
                                <span class="badge bg-secondary border border-secondary"><?= htmlspecialchars($art['CategoryName']) ?></span>
                            <?php else: ?>
                                <span class="text-muted small">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php 
                                if ($art['Status'] == 1) echo '<span class="badge bg-success bg-opacity-10 text-success px-3 py-1 rounded-pill">Tiến hành</span>';
                                elseif ($art['Status'] == 2) echo '<span class="badge bg-primary bg-opacity-10 text-primary px-3 py-1 rounded-pill">Hoàn thành</span>';
                                else echo '<span class="badge bg-secondary bg-opacity-10 text-secondary px-3 py-1 rounded-pill">Tạm ngưng</span>';
                            ?>
                        </td>
                        <td class="fw-bold text-secondary"><?= number_format($art['ViewCount']) ?></td>
                        <td class="text-end">
                            <a href="view.php?id=<?= $art['ArticleID'] ?>" class="btn btn-sm btn-light text-primary me-1" title="Xem chi tiết"><i class="fas fa-eye"></i></a>
                            <a href="edit.php?id=<?= $art['ArticleID'] ?>" class="btn btn-sm btn-light text-warning me-1" title="Sửa"><i class="fas fa-edit"></i></a>
                            <a href="delete.php?id=<?= $art['ArticleID'] ?>" class="btn btn-sm btn-light text-danger" onclick="return confirm('Xóa truyện này?')" title="Xóa"><i class="fas fa-trash"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>