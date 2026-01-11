<?php
// history.php
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Xử lý Xóa lịch sử
if (isset($_POST['clear_history'])) {
    if (isset($_SESSION['user_id'])) {
        // Xóa trong DB
        $pdo->prepare("DELETE FROM history WHERE UserID = ?")->execute([$_SESSION['user_id']]);
    }
    // Xóa cả Cookie cho chắc
    setcookie('manga_history', '', time() - 3600, "/");
    header("Location: history.php");
    exit;
}

$historyData = [];

// [LOGIC MỚI] Lấy dữ liệu
if (isset($_SESSION['user_id'])) {
    // --- TRƯỜNG HỢP 1: ĐÃ ĐĂNG NHẬP (Lấy từ DB) ---
    $sql = "SELECT h.LastReadAt as time, 
                   a.ArticleID as id, a.Title as title, a.CoverImage as image,
                   c.ChapterID as chap_id, c.`Index` as chap_index
            FROM history h
            JOIN articles a ON h.ArticleID = a.ArticleID
            JOIN chapters c ON h.ChapterID = c.ChapterID
            WHERE h.UserID = ?
            ORDER BY h.LastReadAt DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$_SESSION['user_id']]);
    $dbHistory = $stmt->fetchAll();

    // Chuẩn hóa dữ liệu để giống cấu trúc Cookie (để vòng lặp bên dưới không bị lỗi)
    foreach ($dbHistory as $row) {
        $historyData[] = [
            'id' => $row['id'],
            'title' => $row['title'],
            'image' => $row['image'],
            'chap_id' => $row['chap_id'],
            'chap_index' => $row['chap_index'],
            'time' => strtotime($row['time']) // Chuyển datetime MySQL sang timestamp
        ];
    }

} else {
    // --- TRƯỜNG HỢP 2: KHÁCH (Lấy từ Cookie) ---
    $cookieName = 'manga_history';
    $historyData = isset($_COOKIE[$cookieName]) ? json_decode($_COOKIE[$cookieName], true) : [];
}

$pageTitle = "Lịch sử đọc truyện";
require_once 'includes/header.php';
?>

<style>
    /* CSS Riêng cho trang History */
    .history-header {
        display: flex; justify-content: space-between; align-items: center;
        margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px solid var(--border-color);
    }
    .btn-clear {
        background: #ff4d4d; color: #fff; border: none; padding: 6px 15px; 
        border-radius: 4px; font-size: 13px; font-weight: bold; cursor: pointer;
    }
    .btn-clear:hover { opacity: 0.9; }

    .history-list { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; }

    .history-item {
        display: flex; background: var(--bg-element); 
        border: 1px solid var(--border-color); border-radius: 6px; overflow: hidden;
        transition: 0.2s; position: relative;
    }
    .history-item:hover { transform: translateY(-2px); border-color: var(--primary-theme); }

    .h-thumb { width: 100px; height: 140px; flex-shrink: 0; }
    .h-thumb img { width: 100%; height: 100%; object-fit: cover; }

    .h-info { padding: 15px; display: flex; flex-direction: column; justify-content: center; width: 100%; }
    .h-title { 
        font-size: 16px; font-weight: bold; color: var(--text-main); margin-bottom: 8px;
        display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
    }
    .h-chap { color: var(--primary-theme); font-weight: bold; font-size: 14px; margin-bottom: 5px; }
    .h-time { font-size: 12px; color: var(--text-muted); margin-bottom: 10px; }

    .h-actions a {
        display: inline-block; padding: 5px 12px; font-size: 12px; 
        background: var(--bg-body); border: 1px solid var(--border-color); color: var(--text-main);
        border-radius: 4px; text-decoration: none;
    }
    .h-actions a:hover { background: var(--primary-theme); color: #fff; border-color: var(--primary-theme); }

    /* Responsive */
    @media (max-width: 768px) {
        .history-list { grid-template-columns: 1fr; }
    }
</style>

<div class="main-container">
    <main class="content" style="flex: 1;"> <div class="history-header">
            <h3><i class="fas fa-history me-2"></i>Lịch sử đọc truyện</h3>
            <?php if(!empty($historyData)): ?>
                <form method="POST" onsubmit="return confirm('Bạn chắc chắn muốn xóa toàn bộ lịch sử?');">
                    <button type="submit" name="clear_history" class="btn-clear">
                        <i class="fas fa-trash-alt me-1"></i> Xóa lịch sử
                    </button>
                </form>
            <?php endif; ?>
        </div>

        <?php if(!empty($historyData)): ?>
            <div class="history-list">
                <?php foreach($historyData as $item): ?>
                    <div class="history-item">
                        <a href="<?= BASE_URL ?>doc/<?= $item['id'] ?>/<?= $item['chap_id'] ?>" class="h-thumb">
                            <?php if(!empty($item['image'])): ?>
                                <img src="<?= getImageUrl($item['image']) ?>" alt="<?= htmlspecialchars($item['title']) ?>">
                            <?php else: ?>
                                <div style="width:100%; height:100%; background:#333; display:flex; align-items:center; justify-content:center;">No Img</div>
                            <?php endif; ?>
                        </a>
                        
                        <div class="h-info">
                            <a href="<?= BASE_URL ?>doc/<?= $item['id'] ?>/<?= $item['chap_id'] ?>" class="h-title">
                                <?= htmlspecialchars($item['title']) ?>
                            </a>
                            
                            <div class="h-chap">
                                <i class="fas fa-book-open me-1"></i> Đọc tiếp Chapter <?= $item['chap_index'] ?>
                            </div>
                            
                            <div class="h-time">
                                <i class="far fa-clock me-1"></i>
                                <?php
                                    $diff = time() - $item['time'];
                                    if ($diff < 60) echo 'Vừa xong';
                                    elseif ($diff < 3600) echo floor($diff/60) . ' phút trước';
                                    elseif ($diff < 86400) echo floor($diff/3600) . ' giờ trước';
                                    else echo date('d/m/Y', $item['time']);
                                ?>
                            </div>

                            <div class="h-actions">
                                <a href="<?= BASE_URL ?>doc/<?= $item['id'] ?>/<?= $item['chap_id'] ?>">Đọc tiếp</a>
                                <a href="<?= BASE_URL ?>truyen/<?= $item['id'] ?>">Chi tiết</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="fas fa-history text-muted mb-3" style="font-size: 50px;"></i>
                <p class="text-muted">Bạn chưa đọc truyện nào.</p>
                <a href="<?= BASE_URL ?>" class="btn-action btn-read" style="padding: 8px 20px;">Về trang chủ</a>
            </div>
        <?php endif; ?>

    </main>
</div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>