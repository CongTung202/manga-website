<?php
// includes/right_sidebar.php
require_once __DIR__ . '/functions.php';

// 1. Top truyện (Giữ nguyên)
$stmtTop = $pdo->query("SELECT * FROM articles WHERE IsDeleted = 0 ORDER BY ViewCount DESC LIMIT 5");
$topArticles = $stmtTop->fetchAll();

// 2. [CẬP NHẬT] Lấy Lịch sử
$historyData = [];

if (isset($_SESSION['user_id'])) {
    // Nếu đã đăng nhập -> Lấy 5 truyện gần nhất từ DB
    $sql = "SELECT h.LastReadAt as time, 
                   a.ArticleID as id, a.Title as title, a.CoverImage as image,
                   c.ChapterID as chap_id, c.`Index` as chap_index
            FROM history h
            JOIN articles a ON h.ArticleID = a.ArticleID
            JOIN chapters c ON h.ChapterID = c.ChapterID
            WHERE h.UserID = ?
            ORDER BY h.LastReadAt DESC LIMIT 5";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$_SESSION['user_id']]);
    $rows = $stmt->fetchAll();

    foreach ($rows as $row) {
        $historyData[] = [
            'id' => $row['id'],
            'title' => $row['title'],
            'image' => $row['image'],
            'chap_id' => $row['chap_id'],
            'chap_index' => $row['chap_index'],
            'time' => strtotime($row['time'])
        ];
    }
} else {
    // Nếu chưa đăng nhập -> Lấy từ Cookie
    $cookieName = 'manga_history';
    $historyData = isset($_COOKIE[$cookieName]) ? json_decode($_COOKIE[$cookieName], true) : [];
}
?>

<style>
    /* Style Sidebar Dark Mode */
    .aside-wrap { font-size: 13px; color: var(--text-main); }
    
    /* Section Header */
    .aside-header { 
        display: flex; justify-content: space-between; align-items: center; 
        border-bottom: 1px solid var(--border-color); 
        padding-bottom: 10px; margin-bottom: 15px; margin-top: 30px;
    }
    .aside-title { font-size: 16px; font-weight: bold; color: var(--text-main); margin: 0; }
    .aside-header a { color: var(--text-muted); text-decoration: none; }
    .aside-header a:hover { color: var(--text-main); }
    
    /* List Item */
    .aside-list-item { display: flex; align-items: flex-start; margin-bottom: 15px; position: relative; cursor: pointer; }
    .aside-list-item:hover .aside-comic-title { color: var(--primary-theme); }
    
    .aside-thumb { 
        width: 60px; height: 50px; flex-shrink: 0; border-radius: 4px; overflow: hidden; 
        border: 1px solid var(--border-color); margin-right: 12px; position: relative;
        background-color: var(--bg-element);
    }
    .aside-thumb img { width: 100%; height: 100%; object-fit: cover; opacity: 0.8; transition: 0.2s; }
    .aside-list-item:hover .aside-thumb img { opacity: 1; transform: scale(1.05); }
    
    /* Ranking Number */
    .ranking-num { 
        font-size: 18px; font-weight: bold; color: var(--text-muted); 
        margin-right: 12px; line-height: 1; margin-top: 5px;
        width: 15px; text-align: center;
    }
    .ranking-num.top-3 { color: var(--primary-theme); } 
    
    .aside-info { overflow: hidden; width: 100%; }
    .aside-comic-title { 
        font-size: 14px; font-weight: bold; display: block; 
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis; 
        color: var(--text-main); margin-bottom: 2px;
        transition: 0.2s;
    }
    .aside-comic-author { font-size: 12px; color: var(--text-muted); }
    
    /* History Item */
    .history-chap { font-size: 11px; color: var(--primary-theme); margin-top: 2px; display: block; font-weight: bold; }
    
    /* Notice List */
    .notice-list { list-style: none; padding: 0; margin: 0; }
    .notice-item { margin-bottom: 10px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .notice-link { color: var(--text-main); font-size: 13px; text-decoration: none; transition: 0.2s; }
    .notice-link:hover { color: var(--primary-theme); }
    .notice-link strong { color: var(--primary-theme); margin-right: 5px; }
</style>

<div class="aside-wrap">

    <?php if (!empty($historyData)): ?>
        <div class="aside-header" style="margin-top: 0;">
            <h3 class="aside-title">Lịch sử đọc</h3>
        </div>
        <div class="aside-list mb-4">
            <?php foreach($historyData as $item): ?>
            <div class="aside-list-item mb-3" onclick="window.location.href='<?= BASE_URL ?>doc/<?= $item['id'] ?>/<?= $item['chap_id'] ?>'">
                <div class="aside-thumb">
                    <?php if($item['image']): ?>
                        <img src="<?= getImageUrl($item['image']) ?>">
                    <?php else: ?>
                        <div class="d-flex align-items-center justify-content-center h-100 text-muted small">No Img</div>
                    <?php endif; ?>
                </div>
                
                <div class="aside-info">
                    <div class="aside-comic-title"><?= htmlspecialchars($item['title']) ?></div>
                    <div class="d-flex justify-content-between align-items-end">
                        <div>
                            <span class="history-chap">Đọc tiếp Chap <?= $item['chap_index'] ?></span>
                            <span style="font-size: 10px; color: var(--text-muted);">
                                <?= (time() - $item['time'] < 60) ? 'Vừa xong' : ((time() - $item['time'] < 3600) ? floor((time() - $item['time'])/60).' phút trước' : 'Hôm nay') ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div style="border-bottom: 1px solid var(--border-color); margin: 30px 0;"></div>
    <?php endif; ?>

    <div class="aside-header">
        <h3 class="aside-title">Độc giả xem nhiều</h3>
    </div>
    
    <div class="aside-list">
        <?php $rank = 1; foreach($topArticles as $art): ?>
        <div class="aside-list-item" onclick="window.location.href='<?= BASE_URL ?>truyen/<?= $art['ArticleID'] ?>'">
            <div class="aside-thumb">
                <?php if($art['CoverImage']): ?>
                    <img src="<?= getImageUrl($art['CoverImage']) ?>">
                <?php else: ?>
                    <div class="d-flex align-items-center justify-content-center h-100 text-muted small">No Img</div>
                <?php endif; ?>
            </div>
            
            <div class="ranking-num <?= $rank <= 3 ? 'top-3' : '' ?>"><?= $rank ?></div>
            
            <div class="aside-info">
                <div class="aside-comic-title"><?= htmlspecialchars($art['Title']) ?></div>
                <div class="aside-comic-author">
                    <i class="fas fa-eye me-1"></i> <?= number_format($art['ViewCount']) ?>
                    <span class="mx-1">|</span> Tác giả...
                </div>
            </div>
        </div>
        <?php $rank++; endforeach; ?>
    </div>

</div>