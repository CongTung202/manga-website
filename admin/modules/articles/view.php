<?php
require_once '../../../includes/db.php';
require_once '../../../includes/functions.php'; // Gọi functions
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';

$id = $_GET['id'] ?? null;
if (!$id) die("Không tìm thấy ID truyện");

// 1. Lấy thông tin truyện
$sql = "SELECT a.*, c.Name as CategoryName 
        FROM articles a 
        LEFT JOIN categories c ON a.CategoryID = c.CategoryID 
        WHERE a.ArticleID = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$article = $stmt->fetch();

// 2. Lấy danh sách thể loại
$stmtGenres = $pdo->prepare("SELECT g.Name FROM genres g JOIN articles_genres ag ON g.GenreID = ag.GenreID WHERE ag.ArticleID = ?");
$stmtGenres->execute([$id]);
$genres = $stmtGenres->fetchAll(PDO::FETCH_COLUMN);

// 3. Lấy danh sách tác giả
$stmtAuthors = $pdo->prepare("SELECT a.Name FROM authors a JOIN articles_authors aa ON a.AuthorID = aa.AuthorID WHERE aa.ArticleID = ?");
$stmtAuthors->execute([$id]);
$authors = $stmtAuthors->fetchAll(PDO::FETCH_COLUMN);

// 4. Lấy danh sách chương
$stmtChapters = $pdo->prepare("SELECT * FROM chapters WHERE ArticleID = ? ORDER BY `Index` DESC");
$stmtChapters->execute([$id]);
$chapters = $stmtChapters->fetchAll();
?>

<div class="container-fluid">
    <div class="mb-3">
        <a href="index.php" class="text-decoration-none text-muted"><i class="fas fa-arrow-left me-1"></i> Quay lại danh sách</a>
    </div>

    <div class="card-custom p-4 mb-4">
        <div class="row">
            <div class="col-md-3 text-center">
                <?php if($article['CoverImage']): ?>
                    <img src="<?= getImageUrl($article['CoverImage']) ?>" class="img-fluid rounded shadow-sm" style="max-height: 300px; border: 1px solid var(--border-color);">
                <?php else: ?>
                    <div class="rounded d-flex align-items-center justify-content-center text-muted border" style="height: 300px; background: var(--bg-body);">No Image</div>
                <?php endif; ?>
            </div>
            
            <div class="col-md-9">
                <div class="d-flex justify-content-between">
                    <h2 class="fw-bold mb-2 text-white"><?= htmlspecialchars($article['Title']) ?></h2>
                    <div>
                        <a href="edit.php?id=<?= $article['ArticleID'] ?>" class="btn btn-warning rounded-pill px-4"><i class="fas fa-edit me-2"></i>Sửa</a>
                    </div>
                </div>

                <div class="mb-3">
                    <?php if($article['CategoryName']): ?>
                        <span class="badge bg-primary me-2"><?= htmlspecialchars($article['CategoryName']) ?></span>
                    <?php endif; ?>
                    
                    <?php if(!empty($genres)): ?>
                        <?php foreach($genres as $g): ?>
                            <span class="badge border border-secondary text-light me-1" style="background-color: var(--bg-body);"><?= htmlspecialchars($g) ?></span>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="mb-3 text-muted">
                    <strong>Tác giả:</strong> 
                    <?php if(!empty($authors)): ?>
                        <span class="text-light"><?= htmlspecialchars(implode(', ', $authors)) ?></span>
                    <?php else: ?>
                        <span class="fst-italic">Đang cập nhật</span>
                    <?php endif; ?>
                </div>

                <p>
                    <strong>Trạng thái:</strong> 
                    <?= $article['Status'] == 1 ? '<span class="text-success fw-bold">Đang tiến hành</span>' : ($article['Status'] == 2 ? '<span class="text-primary fw-bold">Hoàn thành</span>' : '<span class="text-secondary fw-bold">Tạm ngưng</span>') ?>
                    <span class="mx-2">|</span>
                    <strong>Lượt xem:</strong> <span class="text-light"><?= number_format($article['ViewCount']) ?></span>
                </p>

                <div class="p-3 rounded border" style="background-color: var(--bg-body); color: #ccc; border-color: var(--border-color) !important; white-space: pre-line;">
                    <?= htmlspecialchars($article['Description']) ?>
                </div>
            </div>
        </div>
    </div>

    <div class="card-custom p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold m-0 text-white">Danh sách chương (<?= count($chapters) ?>)</h5>
            <a href="../chapters/create.php?article_id=<?= $article['ArticleID'] ?>" class="btn btn-sm btn-naver rounded-pill px-3">
                <i class="fas fa-plus me-1"></i> Thêm chương
            </a>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead style="background-color: #333; color: #fff;">
                    <tr>
                        <th class="rounded-start ps-3 border-0">#</th>
                        <th class="border-0">Tên chương</th>
                        <th class="border-0">Ngày đăng</th>
                        <th class="rounded-end text-end pe-3 border-0">Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($chapters) > 0): ?>
                        <?php foreach($chapters as $chap): ?>
                        <tr>
                            <td class="fw-bold ps-3 text-muted">Chapter <?= $chap['Index'] ?></td>
                            <td class="text-light"><?= htmlspecialchars($chap['Title']) ?></td>
                            <td class="text-muted"><?= date('d/m/Y', strtotime($chap['CreatedAt'])) ?></td>
                            <td class="text-end pe-3">
                                <a href="../chapters/edit.php?id=<?= $chap['ChapterID'] ?>&article_id=<?= $article['ArticleID'] ?>" class="btn btn-sm btn-light text-warning me-1"><i class="fas fa-edit"></i></a>
                                <a href="../chapters/delete.php?id=<?= $chap['ChapterID'] ?>&article_id=<?= $article['ArticleID'] ?>" class="btn btn-sm btn-light text-danger" onclick="return confirm('Xóa chương này?')"><i class="fas fa-trash"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="text-center py-4 text-muted">Chưa có chương nào.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>