<?php
require_once 'includes/init.php';
require_once '../includes/db.php';
require_once '../includes/functions.php'; // Để dùng getImageUrl
require_once 'includes/header.php';
require_once 'includes/sidebar.php';

// --- THỐNG KÊ TỔNG QUAN ---
$countArt = $pdo->query("SELECT COUNT(*) FROM articles WHERE IsDeleted=0")->fetchColumn();
$countChap = $pdo->query("SELECT COUNT(*) FROM chapters WHERE IsDeleted=0")->fetchColumn();
$sumView = $pdo->query("SELECT SUM(ViewCount) FROM articles WHERE IsDeleted=0")->fetchColumn();
$countUser = $pdo->query("SELECT COUNT(*) FROM users WHERE IsDeleted=0")->fetchColumn();

// --- DỮ LIỆU MỚI (TÍNH NĂNG MỚI) ---
// 1. Lấy 5 thành viên mới nhất
$newUsers = $pdo->query("SELECT * FROM users WHERE IsDeleted=0 ORDER BY CreatedAt DESC LIMIT 5")->fetchAll();

// 2. Lấy 5 bình luận mới nhất
$recentComments = $pdo->query("
    SELECT c.*, u.UserName, u.Avatar, a.Title 
    FROM comments c 
    JOIN users u ON c.UserID = u.UserID 
    JOIN articles a ON c.ArticleID = a.ArticleID 
    WHERE c.IsDeleted=0 
    ORDER BY c.CreatedAt DESC LIMIT 5
")->fetchAll();
?>

<div class="container-fluid">
    <h3 class="fw-bold mb-4">Tổng quan hệ thống</h3>

    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card border-0 text-white stats-card-1 h-100 position-relative overflow-hidden">
                <div class="card-body p-4">
                    <h5 class="card-title fw-bold text-uppercase fs-6">Tổng Truyện</h5>
                    <h2 class="display-5 fw-bold mb-0"><?= number_format($countArt) ?></h2>
                    <i class="fas fa-book stats-icon"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 text-white stats-card-2 h-100 position-relative overflow-hidden">
                <div class="card-body p-4">
                    <h5 class="card-title fw-bold text-uppercase fs-6">Tổng Chapter</h5>
                    <h2 class="display-5 fw-bold mb-0"><?= number_format($countChap) ?></h2>
                    <i class="fas fa-file-alt stats-icon"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 text-white stats-card-3 h-100 position-relative overflow-hidden">
                <div class="card-body p-4">
                    <h5 class="card-title fw-bold text-uppercase fs-6">Lượt xem</h5>
                    <h2 class="display-5 fw-bold mb-0"><?= number_format($sumView) ?></h2>
                    <i class="fas fa-eye stats-icon"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 text-white stats-card-4 h-100 position-relative overflow-hidden">
                <div class="card-body p-4">
                    <h5 class="card-title fw-bold text-uppercase fs-6">Thành viên</h5>
                    <h2 class="display-5 fw-bold mb-0"><?= number_format($countUser) ?></h2>
                    <i class="fas fa-users stats-icon"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        
        <div class="col-lg-7">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center py-3">
                    <h6 class="m-0 fw-bold"><i class="fas fa-comments me-2 text-warning"></i>Bình luận mới nhất</h6>
                    <a href="<?= ADMIN_MODULES_URL ?>comments/" class="btn btn-sm btn-light py-0" style="font-size:11px;">Xem tất cả</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th class="ps-3">Người dùng</th>
                                    <th>Nội dung</th>
                                    <th>Truyện</th>
                                    <th>Lúc</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($recentComments as $cmt): ?>
                                <tr>
                                    <td class="ps-3">
                                        <div class="d-flex align-items-center">
                                            <img src="<?= !empty($cmt['Avatar']) ? getImageUrl($cmt['Avatar']) : 'https://ui-avatars.com/api/?name='.$cmt['UserName'] ?>" 
                                                 class="rounded-circle me-2" width="25" height="25" style="object-fit:cover;">
                                            <span class="fw-bold" style="font-size:12px;"><?= htmlspecialchars($cmt['UserName']) ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="d-inline-block text-truncate text-muted" style="max-width: 150px; font-size:12px;">
                                            <?= htmlspecialchars($cmt['Content']) ?>
                                        </span>
                                    </td>
                                    <td><span class="badge bg-secondary" style="font-size:10px;"><?= htmlspecialchars($cmt['Title']) ?></span></td>
                                    <td class="text-muted" style="font-size:11px;"><?= date('H:i d/m', strtotime($cmt['CreatedAt'])) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center py-3">
                    <h6 class="m-0 fw-bold"><i class="fas fa-user-plus me-2 text-success"></i>Thành viên mới</h6>
                    <a href="<?= ADMIN_MODULES_URL ?>users/" class="btn btn-sm btn-light py-0" style="font-size:11px;">Quản lý</a>
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush bg-transparent">
                        <?php foreach($newUsers as $u): ?>
                        <li class="list-group-item bg-transparent border-bottom border-secondary d-flex align-items-center py-3">
                            <img src="<?= !empty($u['Avatar']) ? getImageUrl($u['Avatar']) : 'https://ui-avatars.com/api/?name='.$u['UserName'] ?>" 
                                 class="rounded-circle me-3" width="35" height="35" style="object-fit:cover; border:1px solid #444;">
                            <div class="flex-grow-1">
                                <h6 class="mb-0 fw-bold" style="font-size:13px;"><?= htmlspecialchars($u['UserName']) ?></h6>
                                <small class="text-muted" style="font-size:11px;"><?= htmlspecialchars($u['Email']) ?></small>
                            </div>
                            <span class="badge bg-dark border border-secondary text-muted" style="font-size:10px;">
                                <?= date('d/m/Y', strtotime($u['CreatedAt'])) ?>
                            </span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>

    </div>
</div>

<?php require_once 'includes/footer.php'; ?>