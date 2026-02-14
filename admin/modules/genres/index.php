<?php
require_once '../../includes/init.php';
require_once '../../../includes/db.php';
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';

// Xử lý Thêm nhanh (nếu submit từ modal hoặc form cùng trang)
// Nhưng để đồng bộ, ta sẽ dùng file add.php riêng hoặc nút thêm ở trên.

// Lấy danh sách thể loại + Đếm số truyện
$sql = "SELECT g.*, COUNT(ag.ArticleID) as ArticleCount 
        FROM genres g 
        LEFT JOIN articles_genres ag ON g.GenreID = ag.GenreID 
        GROUP BY g.GenreID 
        ORDER BY g.Name ASC";
$genres = $pdo->query($sql)->fetchAll();
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold m-0">Quản lý Thể loại</h3>
        <a href="add.php" class="btn btn-naver px-4 py-2 rounded-pill">
            <i class="fas fa-plus me-2"></i>Thêm thể loại
        </a>
    </div>

    <div class="card-custom p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th class="ps-3 rounded-start border-0" style="width: 80px;">ID</th>
                        <th class="border-0">Tên thể loại</th>
                        <th class="border-0">Số lượng truyện</th>
                        <th class="text-end rounded-end border-0 pe-3">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($genres as $g): ?>
                    <tr>
                        <td class="text-muted ps-3">#<?= $g['GenreID'] ?></td>
                        <td>
                            <span class="fw-bold fs-6"><?= htmlspecialchars($g['Name']) ?></span>
                        </td>
                        <td>
                            <?php if($g['ArticleCount'] > 0): ?>
                                <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-1 rounded-pill">
                                    <?= $g['ArticleCount'] ?> truyện
                                </span>
                            <?php else: ?>
                                <span class="badge bg-secondary bg-opacity-10 text-muted px-3 py-1 rounded-pill">
                                    Trống
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end pe-3">
                            <a href="edit.php?id=<?= $g['GenreID'] ?>" class="btn btn-sm btn-light text-warning me-1" title="Sửa">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="delete.php?id=<?= $g['GenreID'] ?>" class="btn btn-sm btn-light text-danger" onclick="return confirm('Xóa thể loại này? Các truyện thuộc thể loại này sẽ bị mất nhãn.')" title="Xóa">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>