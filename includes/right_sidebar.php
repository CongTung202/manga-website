<?php
// includes/right_sidebar.php
require_once __DIR__ . '/functions.php';

// 1. [CẬP NHẬT] Top truyện (Lấy thêm tên tác giả)
$stmtTop = $pdo->query("
    SELECT a.*, 
           (SELECT auth.Name 
            FROM authors auth 
            JOIN articles_authors aa ON auth.AuthorID = aa.AuthorID 
            WHERE aa.ArticleID = a.ArticleID 
            LIMIT 1) as AuthorName
    FROM articles a 
    WHERE a.IsDeleted = 0 
    ORDER BY a.ViewCount DESC 
    LIMIT 5
");
$topArticles = $stmtTop->fetchAll();

// 2. Lấy Lịch sử (Giữ nguyên logic cũ nhưng thêm kiểm tra kỹ hơn)
$historyData = [];
if (isset($_SESSION['user_id'])) {
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
    $cookieName = 'manga_history';
    if (isset($_COOKIE[$cookieName])) {
        $decoded = json_decode($_COOKIE[$cookieName], true);
        if (is_array($decoded)) {
            $historyData = array_slice($decoded, 0, 5); // Lấy 5 truyện gần nhất từ cookie
        }
    }
}
?>

<style>
    /* CSS Fix Vỡ Giao Diện */
    .aside-wrap { font-size: 13px; color: var(--text-main); width: 100%; overflow: hidden; }
    
    .aside-header { 
        display: flex; justify-content: space-between; align-items: center; 
        border-bottom: 1px solid var(--border-color); 
        padding-bottom: 10px; margin-bottom: 15px; margin-top: 30px;
    }
    .aside-title { font-size: 16px; font-weight: bold; color: var(--text-main); margin: 0; }
    
    /* List Item */
    .aside-list-item { 
        display: flex; 
        align-items: flex-start; 
        margin-bottom: 15px; 
        position: relative; 
        cursor: pointer; 
        width: 100%; /* Đảm bảo không tràn */
    }
    
    .aside-thumb { 
        width: 60px; height: 80px; /* Tăng chiều cao chút cho cân đối */
        flex-shrink: 0; 
        border-radius: 4px; overflow: hidden; 
        border: 1px solid var(--border-color); 
        margin-right: 12px; 
        background-color: var(--bg-element);
    }
    .aside-thumb img { width: 100%; height: 100%; object-fit: cover; opacity: 0.9; transition: 0.2s; }
    .aside-list-item:hover .aside-thumb img { opacity: 1; transform: scale(1.05); }
    
    .ranking-num { 
        font-size: 20px; font-weight: 900; color: var(--text-muted); 
        margin-right: 10px; line-height: 1; margin-top: 0;
        width: 20px; text-align: center; font-style: italic;
    }
    .ranking-num.top-1 { color: #e74c3c; } 
    .ranking-num.top-2 { color: #e67e22; } 
    .ranking-num.top-3 { color: #f1c40f; } 
    
    /* [QUAN TRỌNG] Fix lỗi tên truyện dài */
    .aside-info { 
        flex: 1; /* Tự động chiếm khoảng trống còn lại */
        min-width: 0; /* Bắt buộc để text-overflow hoạt động trong Flexbox */
        display: flex; 
        flex-direction: column; 
        gap: 4px;
    }
    
    .aside-comic-title { 
        font-size: 14px; 
        font-weight: bold; 
        color: var(--text-main); 
        line-height: 1.3;
        transition: 0.2s;
        
        /* Cắt dòng thông minh (Tối đa 2 dòng) */
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        white-space: normal; /* Cho phép xuống dòng */
    }
    .aside-list-item:hover .aside-comic-title { color: var(--primary-theme); }

    .aside-comic-author { 
        font-size: 12px; 
        color: var(--text-muted); 
        white-space: nowrap; 
        overflow: hidden; 
        text-overflow: ellipsis; 
    }
    
    .history-chap { 
        font-size: 12px; color: var(--primary-theme); font-weight: bold; display: block; 
    }
    .history-time { font-size: 11px; color: var(--text-muted); }
</style>

<div class="aside-wrap">

    <?php if (!empty($historyData)): ?>
        <div class="aside-header" style="margin-top: 0;">
            <h3 class="aside-title"><i class="fas fa-history me-2"></i> Lịch sử đọc</h3>
        </div>
        <div class="aside-list mb-4">
            <?php foreach($historyData as $item): ?>
            <div class="aside-list-item mb-3" onclick="window.location.href='<?= BASE_URL ?>chapter/<?= $item['id'] ?>/<?= $item['chap_id'] ?>'">
                <div class="aside-thumb" style="height: 60px; width: 60px;"> <?php if($item['image']): ?>
                        <img src="<?= getImageUrl($item['image']) ?>" alt="Cover">
                    <?php else: ?>
                        <div class="d-flex align-items-center justify-content-center h-100 text-muted small">No Img</div>
                    <?php endif; ?>
                </div>
                
                <div class="aside-info">
                    <div class="aside-comic-title"><?= htmlspecialchars($item['title']) ?></div>
                    <div>
                        <span class="history-chap">Đọc tiếp Chap <?= $item['chap_index'] ?></span>
                        <span class="history-time">
                            <?php
                                $diff = time() - $item['time'];
                                if ($diff < 60) echo 'Vừa xong';
                                elseif ($diff < 3600) echo floor($diff/60).' phút trước';
                                elseif ($diff < 86400) echo floor($diff/3600).' giờ trước';
                                else echo date('d/m', $item['time']);
                            ?>
                        </span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div style="border-bottom: 1px solid var(--border-color); margin: 20px 0;"></div>
    <?php endif; ?>

    <div class="aside-header">
        <h3 class="aside-title"><i class="fas fa-crown me-2 text-warning"></i> Xem nhiều</h3>
    </div>
    
    <div class="aside-list">
        <?php $rank = 1; foreach($topArticles as $art): ?>
        <div class="aside-list-item" onclick="window.location.href='<?= BASE_URL ?>truyen/<?= $art['ArticleID'] ?>'">
            
            <div class="ranking-num top-<?= $rank ?>"><?= $rank ?></div>
            
            <div class="aside-thumb">
                <?php if($art['CoverImage']): ?>
                    <img src="<?= getImageUrl($art['CoverImage']) ?>" alt="Cover">
                <?php else: ?>
                    <div class="d-flex align-items-center justify-content-center h-100 text-muted small">No Img</div>
                <?php endif; ?>
            </div>
            
            <div class="aside-info">
                <div class="aside-comic-title"><?= htmlspecialchars($art['Title']) ?></div>
                
                <div class="aside-comic-author">
                    <i class="fas fa-pen-nib me-1"></i> 
                    <?= !empty($art['AuthorName']) ? htmlspecialchars($art['AuthorName']) : 'Đang cập nhật' ?>
                </div>
                
                <div class="aside-comic-author">
                    <i class="fas fa-eye me-1"></i> <?= number_format($art['ViewCount']) ?> lượt xem
                </div>
            </div>
        </div>
        <?php $rank++; endforeach; ?>
    </div>

</div>