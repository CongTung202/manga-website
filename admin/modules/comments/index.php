<?php
require_once '../../../includes/db.php';
require_once '../../../includes/functions.php';
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';

// Xử lý Xóa bình luận
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = $_GET['id'];
    $pdo->prepare("UPDATE comments SET IsDeleted = 1 WHERE CommentID = ?")->execute([$id]);
    echo "<script>window.location.href='index.php';</script>";
}

// [FIX QUAN TRỌNG] Sửa lại câu truy vấn SQL
// 1. JOIN articles trực tiếp qua c.ArticleID (giống Dashboard) để không bị sót comment.
// 2. LEFT JOIN chapters để lấy thông tin chương (nếu có), nếu không có chương thì vẫn hiện.
$sql = "SELECT c.*, u.UserName, u.Avatar, u.Role, ch.Index as ChapterIndex, a.Title as ArticleTitle 
        FROM comments c
        JOIN users u ON c.UserID = u.UserID
        JOIN articles a ON c.ArticleID = a.ArticleID
        LEFT JOIN chapters ch ON c.ChapterID = ch.ChapterID
        WHERE c.IsDeleted = 0 
        ORDER BY c.CreatedAt DESC";

$stmt = $pdo->query($sql);
$comments = $stmt->fetchAll();
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold m-0">Quản lý Bình luận</h3>
        <span class="badge bg-secondary px-3 py-2 rounded-pill"><?= count($comments) ?> bình luận</span>
    </div>

    <div class="card-custom p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead style="background-color: #333; color: #fff;">
                    <tr>
                        <th class="rounded-start border-0 ps-3" style="width: 50px;">#</th>
                        <th class="border-0" style="width: 220px;">Người dùng</th>
                        <th class="border-0">Nội dung bình luận</th>
                        <th class="border-0" style="width: 200px;">Tại truyện/Chương</th>
                        <th class="border-0" style="width: 120px;">Thời gian</th>
                        <th class="rounded-end text-end border-0 pe-3" style="width: 80px;">Xóa</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($comments as $cmt): ?>
                    <tr>
                        <td class="text-muted small ps-3">#<?= $cmt['CommentID'] ?></td>
                        
                        <td>
                            <div class="d-flex align-items-center">
                                <?php 
                                    $avatarUrl = !empty($cmt['Avatar']) ? getImageUrl($cmt['Avatar']) : 'https://ui-avatars.com/api/?name=' . urlencode($cmt['UserName']);
                                ?>
                                <img src="<?= $avatarUrl ?>" class="rounded-circle border border-secondary me-2" width="35" height="35" style="object-fit: cover;">
                                
                                <div class="d-flex flex-column">
                                    <span class="fw-bold text-light" style="font-size: 13px;">
                                        <?= htmlspecialchars($cmt['UserName']) ?>
                                    </span>
                                    <?php if($cmt['Role'] == 1): ?>
                                        <span class="badge bg-danger" style="font-size: 9px; width: fit-content;">ADMIN</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>

                        <td>
                            <div class="p-2 rounded" style="background-color: #2b2b2b; color: #e0e0e0; border: 1px solid #444; font-size: 13px; max-width: 400px; white-space: pre-line;">
                                <?= htmlspecialchars($cmt['Content']) ?>
                            </div>
                        </td>

                        <td>
                            <div class="d-flex flex-column">
                                <span class="fw-bold text-primary" style="font-size: 13px;"><?= htmlspecialchars($cmt['ArticleTitle']) ?></span>
                                <?php if(isset($cmt['ChapterIndex']) && $cmt['ChapterIndex'] !== null): ?>
                                    <span class="text-muted small">Chapter <?= $cmt['ChapterIndex'] ?></span>
                                <?php else: ?>
                                    <span class="text-muted small fst-italic">Trang chi tiết</span>
                                <?php endif; ?>
                            </div>
                        </td>

                        <td class="text-muted small">
                            <?= date('d/m/Y', strtotime($cmt['CreatedAt'])) ?><br>
                            <?= date('H:i', strtotime($cmt['CreatedAt'])) ?>
                        </td>

                        <td class="text-end pe-3">
                            <a href="index.php?action=delete&id=<?= $cmt['CommentID'] ?>" 
                               class="btn btn-sm btn-light text-danger" 
                               onclick="return confirm('Xóa bình luận này?')" 
                               title="Xóa">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>

                    <?php if(count($comments) == 0): ?>
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">Chưa có bình luận nào.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>