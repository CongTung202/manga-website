<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
$userId = $_SESSION['user_id'];

// --- [PHẦN 1] XỬ LÝ AJAX (Xóa bình luận) ---
if (isset($_POST['ajax_action']) && $_POST['ajax_action'] == 'delete_comment') {
    $commentId = $_POST['comment_id'] ?? 0;
    
    $stmtCheck = $pdo->prepare("SELECT CommentID FROM comments WHERE CommentID = ? AND UserID = ?");
    $stmtCheck->execute([$commentId, $userId]);
    
    if ($stmtCheck->rowCount() > 0) {
        $pdo->prepare("UPDATE comments SET IsDeleted = 1 WHERE CommentID = ?")->execute([$commentId]);
        echo json_encode(['status' => 'success', 'message' => 'Đã xóa bình luận.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Bạn không có quyền xóa.']);
    }
    exit;
}

// --- [PHẦN 2] XỬ LÝ AJAX (Upload Avatar Đã Nén) ---
if (isset($_POST['ajax_action']) && $_POST['ajax_action'] == 'upload_avatar') {
    header('Content-Type: application/json');
    
    if (isset($_FILES['avatar_file']) && $_FILES['avatar_file']['error'] == 0) {
        // Upload lên Cloudinary
        $avatarUrl = uploadImageToCloud($_FILES['avatar_file'], 'avatars');

        if ($avatarUrl) {
            $stmt = $pdo->prepare("UPDATE users SET Avatar = ? WHERE UserID = ?");
            $stmt->execute([$avatarUrl, $userId]);
            $_SESSION['avatar'] = $avatarUrl;
            
            // Trả về JSON thành công kèm URL mới để JS cập nhật giao diện
            echo json_encode([
                'status' => 'success', 
                'message' => 'Cập nhật ảnh đại diện thành công!',
                'new_avatar_url' => getImageUrl($avatarUrl)
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Lỗi upload lên Cloud. Vui lòng thử lại.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Không nhận được file ảnh.']);
    }
    exit;
}

// Lấy thông tin User
$stmtUser = $pdo->prepare("SELECT * FROM users WHERE UserID = ?");
$stmtUser->execute([$userId]);
$user = $stmtUser->fetch();

// Lấy lịch sử bình luận
$sqlCmt = "SELECT c.*, a.Title AS ArticleTitle, a.ArticleID 
           FROM comments c 
           JOIN articles a ON c.ArticleID = a.ArticleID 
           WHERE c.UserID = ? AND c.IsDeleted = 0 
           ORDER BY c.CreatedAt DESC";
$stmtCmt = $pdo->prepare($sqlCmt);
$stmtCmt->execute([$userId]);
$myComments = $stmtCmt->fetchAll();
$totalComments = count($myComments);
$pageTitle = "Hồ sơ cá nhân";
require_once 'includes/header.php';
?>

<link rel="stylesheet" href="<?= BASE_URL ?>css/profile.css?v=<?= time() ?>">

<script src="https://cdnjs.cloudflare.com/ajax/libs/compressorjs/1.2.1/compressor.min.js"></script>

<div class="toast-container" id="toastContainer"></div>

<div class="main-container">
    <div class="profile-container">
        
        <div class="profile-box">
            <div class="box-backdrop" style="background-image: url('<?= getImageUrl($_SESSION['avatar']) ?>')"></div>
            
            <div class="profile-row">
                <div class="profile-left">
                    <div class="avatar-wrapper">
                        <img src="<?= getImageUrl($_SESSION['avatar']) ?>" class="avatar-img" id="displayAvatar">
                        
                        <form id="avatarForm" enctype="multipart/form-data" style="display: none;">
                            <input type="file" name="avatar_file" id="avatarInput" accept="image/*">
                        </form>

                        <button class="btn-upload-cam" type="button" onclick="document.getElementById('avatarInput').click();" title="Đổi ảnh đại diện">
                            <i class="fas fa-camera"></i>
                        </button>
                    </div>
                </div>

                <div class="profile-center">
                    <h4 class="user-name">
                        <?= htmlspecialchars($user['UserName']) ?>
                        <span class="user-badge <?= $user['Role'] == 1 ? 'badge-admin' : 'badge-member' ?>">
                            <?= $user['Role'] == 1 ? 'Quản trị viên' : 'Thành viên' ?>
                        </span>
                    </h4>
                    <p class="user-email">
                        <i class="far fa-envelope"></i> <?= htmlspecialchars($user['Email']) ?>
                    </p>
                    <p class="join-date">
                        <i class="far fa-calendar-alt"></i> Tham gia: <?= date('d/m/Y', strtotime($user['CreatedAt'] ?? 'now')) ?>
                    </p>
                </div>

                <div class="profile-right">
                    <div class="stat-box">
                        <span class="stat-val"><?= $totalComments ?></span>
                        <span class="stat-label">Bình luận</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="activity-box">
            <div class="box-heading">
                <i class="fas fa-history" style="color: var(--primary-theme)"></i> Hoạt động gần đây
            </div>
            
            <div class="activity-list" id="commentList">
                <?php if ($totalComments > 0): ?>
                    <?php foreach ($myComments as $cmt): ?>
                        <div class="activity-item" id="cmt-row-<?= $cmt['CommentID'] ?>">
                            <div class="act-header">
                                <a href="<?= BASE_URL ?>truyen/<?= $cmt['ArticleID'] ?>" class="act-manga-title">
                                    <i class="fas fa-book-open"></i> <?= htmlspecialchars($cmt['ArticleTitle']) ?>
                                </a>
                                
                                <div class="act-meta">
                                    <span class="time" title="<?= $cmt['CreatedAt'] ?>">
                                        <i class="far fa-clock"></i> <?= date('d/m/Y', strtotime($cmt['CreatedAt'])) ?>
                                    </span>
                                    
                                    <button class="btn-del-cmt" onclick="deleteComment(<?= $cmt['CommentID'] ?>)" title="Xóa bình luận">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="act-body">
                                <?= nl2br(htmlspecialchars($cmt['Content'])) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="far fa-comment-dots"></i>
                        <p>Bạn chưa có bình luận nào.</p>
                        <a href="<?= BASE_URL ?>" style="color: var(--primary-theme); margin-top: 10px; display:inline-block;">Đi đọc truyện ngay</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<script>
    // --- HÀM TOAST THÔNG BÁO CHUNG ---
    function showToast(message, type = 'success') {
        const container = document.getElementById('toastContainer');
        const icon = type === 'success' ? 'fa-check-circle' : (type === 'info' ? 'fa-spinner fa-spin' : 'fa-exclamation-circle');
        const color = type === 'success' ? '#2ecc71' : (type === 'info' ? '#0dcaf0' : '#ff4d4d');
        
        const toast = document.createElement('div');
        toast.className = `custom-toast ${type}`;
        toast.innerHTML = `<i class="fas ${icon}" style="color: ${color}"></i><span>${message}</span>`;
        container.appendChild(toast);
        
        setTimeout(() => toast.classList.add('show'), 10);
        // Nếu là info (đang tải) thì không tự tắt, các cái khác tắt sau 3s
        if (type !== 'info') {
            setTimeout(() => { toast.classList.remove('show'); setTimeout(() => toast.remove(), 300); }, 3000);
        }
        return toast; // Trả về element để có thể xóa thủ công
    }

    // --- 1. XỬ LÝ NÉN VÀ UPLOAD AVATAR ---
    document.getElementById('avatarInput').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (!file) return;

        // Thông báo đang xử lý
        const loadingToast = showToast('Đang nén và tải ảnh lên...', 'info');

        // Khởi chạy Compressor.js
        new Compressor(file, {
            quality: 0.6, // Chất lượng 60% (rất tốt cho web, giảm dung lượng cực mạnh)
            maxWidth: 800, // Chiều ngang tối đa 800px
            maxHeight: 800,
            success(result) {
                // Tạo form data với file ĐÃ NÉN
                const formData = new FormData();
                formData.append('ajax_action', 'upload_avatar');
                formData.append('avatar_file', result, result.name || 'avatar.jpg');

                // Gửi lên server bằng AJAX
                fetch('profile.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    loadingToast.remove(); // Xóa thông báo "Đang tải"
                    
                    if (data.status === 'success') {
                        showToast(data.message, 'success');
                        // Cập nhật ngay hình ảnh trên giao diện không cần reload
                        document.getElementById('displayAvatar').src = data.new_avatar_url;
                        document.querySelector('.box-backdrop').style.backgroundImage = `url('${data.new_avatar_url}')`;
                    } else {
                        showToast(data.message, 'error');
                    }
                })
                .catch(err => {
                    loadingToast.remove();
                    console.error(err);
                    showToast('Lỗi kết nối khi tải ảnh.', 'error');
                });
            },
            error(err) {
                loadingToast.remove();
                showToast('Lỗi khi nén ảnh: ' + err.message, 'error');
            },
        });
    });

    // --- 2. HÀM XÓA BÌNH LUẬN ---
    function deleteComment(id) {
        if (!confirm('Bạn chắc chắn muốn xóa bình luận này?')) return;
        const formData = new FormData();
        formData.append('ajax_action', 'delete_comment');
        formData.append('comment_id', id);
        
        fetch('profile.php', { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                const row = document.getElementById('cmt-row-' + id);
                if (row) { row.style.opacity = '0'; row.style.transform = 'translateX(20px)'; setTimeout(() => row.remove(), 300); }
                showToast(data.message, 'success');
            } else { showToast(data.message, 'error'); }
        })
        .catch(err => { console.error(err); showToast('Có lỗi xảy ra', 'error'); });
    }
</script>

<?php require_once 'includes/footer.php'; ?>