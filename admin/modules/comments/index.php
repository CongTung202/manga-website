<?php
require_once '../../includes/init.php';
require_once '../../../includes/db.php';
require_once '../../../includes/functions.php';
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';

// 1. Xử lý Xóa bình luận
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $id = $_GET['id'];
    $pdo->prepare("UPDATE comments SET IsDeleted = 1 WHERE CommentID = ?")->execute([$id]);
    
    // Redirect giữ nguyên trang và từ khóa
    $redirectUrl = "index.php";
    $queryParams = [];
    if (isset($_GET['keyword'])) $queryParams['keyword'] = $_GET['keyword'];
    if (isset($_GET['page'])) $queryParams['page'] = $_GET['page'];
    
    if (!empty($queryParams)) {
        $redirectUrl .= "?" . http_build_query($queryParams);
    }
    
    echo "<script>window.location.href='$redirectUrl';</script>";
    exit;
}

// 2. Cấu hình Phân trang & Tìm kiếm
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$limit = 10; // Số bình luận mỗi trang
$offset = ($page - 1) * $limit;

$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';

// 3. Truy vấn SQL
$sqlWhere = "WHERE c.IsDeleted = 0";
$params = [];

if (!empty($keyword)) {
    $sqlWhere .= " AND (u.UserName LIKE ? OR c.Content LIKE ?)";
    $params[] = "%$keyword%";
    $params[] = "%$keyword%";
}

// Đếm tổng
$sqlCount = "SELECT COUNT(*) 
             FROM comments c
             JOIN users u ON c.UserID = u.UserID
             JOIN articles a ON c.ArticleID = a.ArticleID
             $sqlWhere";
$stmtCount = $pdo->prepare($sqlCount);
$stmtCount->execute($params);
$totalRecords = $stmtCount->fetchColumn();
$totalPages = ceil($totalRecords / $limit);

// Lấy dữ liệu
$sqlData = "SELECT c.*, u.UserName, u.Avatar, u.Role, ch.Index as ChapterIndex, a.Title as ArticleTitle 
            FROM comments c
            JOIN users u ON c.UserID = u.UserID
            JOIN articles a ON c.ArticleID = a.ArticleID
            LEFT JOIN chapters ch ON c.ChapterID = ch.ChapterID
            $sqlWhere 
            ORDER BY c.CreatedAt DESC 
            LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($sqlData);
$stmt->execute($params);
$comments = $stmt->fetchAll();
?>
<style>
    /* --- GIAO DIỆN PHÂN TRANG DARK MODE --- */
    .pagination {
        margin-top: 20px;
        gap: 5px; /* Khoảng cách giữa các nút */
    }

    .page-item .page-link {
        background-color: #2b2b2b; /* Nền tối (giống ô input) */
        border: 1px solid #444;    /* Viền xám tối */
        color: #b0b0b0;            /* Chữ màu xám sáng */
        border-radius: 4px;        /* Bo góc nhẹ */
        transition: all 0.2s;
        min-width: 35px;           /* Chiều rộng tối thiểu để nút vuông vắn */
        text-align: center;
    }

    /* Trạng thái Hover (Di chuột vào) */
    .page-item .page-link:hover {
        background-color: #3a3a3a;
        color: #fff;
        border-color: #666;
    }

    /* Trạng thái Active (Trang hiện tại) */
    .page-item.active .page-link {
        background-color: #506891; /* Màu chủ đạo của web bạn */
        border-color: #506891;
        color: #fff;
        font-weight: bold;
        box-shadow: 0 0 10px rgba(80, 104, 145, 0.4); /* Hiệu ứng phát sáng nhẹ */
    }

    /* Trạng thái Disabled (Nút bị vô hiệu hóa) */
    .page-item.disabled .page-link {
        background-color: #1a1a1a; /* Trùng màu nền body */
        color: #444;               /* Chữ chìm hẳn đi */
        border-color: #333;
    }

    /* Xóa viền xanh mặc định của Bootstrap khi bấm vào */
    .page-link:focus {
        box-shadow: none;
    }
</style>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold m-0">Quản lý Bình luận</h3>
        <span class="badge bg-primary px-3 py-2 rounded-pill">Tổng: <?= $totalRecords ?></span>
    </div>

    <div class="card-custom p-3 mb-4">
        <form method="GET" action="" class="d-flex gap-2">
            <div class="input-group" style="max-width: 400px;">
                <span class="input-group-text bg-transparent border-end-0"><i class="fas fa-search text-muted"></i></span>
                <input type="text" name="keyword" class="form-control border-start-0 ps-0" 
                       placeholder="Tìm theo User hoặc Nội dung..." 
                       value="<?= htmlspecialchars($keyword) ?>">
            </div>
            <button type="submit" class="btn btn-primary px-4">Tìm kiếm</button>
            <?php if(!empty($keyword)): ?>
                <a href="index.php" class="btn btn-outline-secondary">Đặt lại</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="card-custom p-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th class="ps-3 rounded-start border-0" style="width: 50px;">#</th>
                        <th class="border-0" style="width: 220px;">Người dùng</th>
                        <th class="border-0">Nội dung bình luận</th>
                        <th class="border-0" style="width: 200px;">Tại truyện/Chương</th>
                        <th class="border-0" style="width: 120px;">Thời gian</th>
                        <th class="text-end rounded-end border-0 pe-3" style="width: 80px;">Xóa</th>
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
                                    <span class="fw-bold fs-6">
                                        <?= htmlspecialchars($cmt['UserName']) ?>
                                    </span>
                                    <?php if($cmt['Role'] == 1): ?>
                                        <span class="badge bg-danger" style="font-size: 9px; width: fit-content;">ADMIN</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>

                        <td>
                            <div class="p-2 rounded text-light" style="background-color: #2b2b2b; border: 1px solid #444; font-size: 13px; max-width: 400px; white-space: pre-line;">
                                <?php
                                    $content = htmlspecialchars($cmt['Content']);
                                    if (!empty($keyword)) {
                                        $content = preg_replace('/(' . preg_quote($keyword, '/') . ')/i', '<span class="bg-warning text-dark px-1 rounded">$1</span>', $content);
                                    }
                                    echo $content;
                                ?>
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
                            <a href="index.php?action=delete&id=<?= $cmt['CommentID'] ?>&page=<?= $page ?>&keyword=<?= urlencode($keyword) ?>" 
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
                            <td colspan="6" class="text-center py-4 text-muted">
                                <i class="fas fa-search me-2"></i> Không tìm thấy kết quả.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
        <nav class="mt-4 d-flex justify-content-center">
            <ul class="pagination">
                <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $page - 1 ?>&keyword=<?= urlencode($keyword) ?>">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                </li>

                <?php 
                $start = max(1, $page - 2);
                $end = min($totalPages, $page + 2);
                
                if ($start > 1) echo '<li class="page-item"><a class="page-link" href="?page=1&keyword='.urlencode($keyword).'">1</a></li>';
                if ($start > 2) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';

                for ($i = $start; $i <= $end; $i++): 
                ?>
                    <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                        <a class="page-link" href="?page=<?= $i ?>&keyword=<?= urlencode($keyword) ?>"><?= $i ?></a>
                    </li>
                <?php endfor; 
                
                // [FIX] Đã đưa logic này vào trong thẻ PHP
                if ($end < $totalPages - 1) echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                if ($end < $totalPages) echo '<li class="page-item"><a class="page-link" href="?page='.$totalPages.'&keyword='.urlencode($keyword).'">'.$totalPages.'</a></li>';
                ?>

                <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $page + 1 ?>&keyword=<?= urlencode($keyword) ?>">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </li>
            </ul>
        </nav>
        <?php endif; ?>

    </div>
</div>

<?php require_once '../../includes/footer.php'; ?>