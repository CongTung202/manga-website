<?php
// 1. Khởi tạo & Bảo mật
require_once '../../includes/init.php';

// 2. XỬ LÝ HÀNH ĐỘNG
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    // Xóa báo cáo
    if ($_GET['action'] == 'delete') {
        $stmt = $pdo->prepare("DELETE FROM error_reports WHERE ReportID = ?");
        $stmt->execute([$id]);
        header("Location: index.php?msg=deleted");
        exit;
    }
    
    // Đổi trạng thái (Đã sửa <-> Chưa sửa)
    if ($_GET['action'] == 'toggle') {
        $stmt = $pdo->prepare("SELECT Status FROM error_reports WHERE ReportID = ?");
        $stmt->execute([$id]);
        $curr = $stmt->fetchColumn();
        $new = $curr == 0 ? 1 : 0;
        
        $pdo->prepare("UPDATE error_reports SET Status = ? WHERE ReportID = ?")->execute([$new, $id]);
        header("Location: index.php?msg=updated");
        exit;
    }
}

// 3. LẤY DANH SÁCH BÁO CÁO
$sql = "SELECT * FROM error_reports ORDER BY Status ASC, CreatedAt DESC";
$reports = $pdo->query($sql)->fetchAll();

require_once '../../includes/header.php';
require_once '../../includes/sidebar.php'; 
?>

<div class="container-fluid">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold m-0 text-white">Quản lý Báo lỗi</h3>
        <span class="badge bg-secondary px-3 py-2 rounded-pill">Tổng: <?= count($reports) ?></span>
    </div>

    <?php if(isset($_GET['msg'])): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm mb-4">
            <i class="fas fa-check-circle me-2"></i> Thao tác thành công!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card-custom p-4" style="background-color: var(--bg-card); border-radius: 8px;">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th class="rounded-start border-0 py-3 ps-3">ID</th>
                        <th class="border-0 py-3">Trạng thái</th>
                        <th class="border-0 py-3" width="30%">Nội dung lỗi</th>
                        <th class="border-0 py-3">Thiết bị</th>
                        <th class="border-0 py-3 text-center">Thời gian</th>
                        <th class="rounded-end text-end border-0 py-3 pe-3">Hành động</th>
                    </tr>
                </thead>
                
                <tbody>
                    <?php if (count($reports) > 0): ?>
                        <?php foreach ($reports as $r): ?>
                        <tr class="<?= $r['Status'] == 1 ? 'opacity-50' : '' ?>" style="border-bottom: 1px solid #333;">
                            
                            <td class="fw-bold text-muted ps-3">#<?= $r['ReportID'] ?></td>
                            
                            <td>
                                <?php if($r['Status'] == 0): ?>
                                    <span class="badge bg-danger bg-opacity-10 text-danger px-3 py-1 rounded-pill">Chưa sửa</span>
                                <?php else: ?>
                                    <span class="badge bg-success bg-opacity-10 text-success px-3 py-1 rounded-pill">Đã sửa</span>
                                <?php endif; ?>
                            </td>
                            
                            <td>
                                <div class="text-white text-truncate" style="max-width: 300px;" title="<?= htmlspecialchars($r['ErrorContent']) ?>">
                                    <?= htmlspecialchars($r['ErrorContent']) ?>
                                </div>
                            </td>
                            
                            <td>
                                <?php 
                                    $icon = 'question';
                                    if(stripos($r['DeviceType'], 'Mobile') !== false) $icon = 'mobile-alt';
                                    elseif(stripos($r['DeviceType'], 'PC') !== false || stripos($r['DeviceType'], 'Laptop') !== false) $icon = 'desktop';
                                    elseif(stripos($r['DeviceType'], 'Tablet') !== false) $icon = 'tablet-alt';
                                ?>
                                <i class="fas fa-<?= $icon ?> text-secondary me-2"></i>
                                <span class="text-white-50"><?= htmlspecialchars($r['DeviceType']) ?></span>
                            </td>
                            
                            <td class="text-center text-muted small">
                                <?= date('d/m H:i', strtotime($r['CreatedAt'])) ?>
                            </td>
                            
                            <td class="text-end pe-3">
                                <a href="#" class="btn btn-sm btn-light text-info me-1" 
                                   onclick="showErrorDetail(event, 
                                       '<?= htmlspecialchars(addslashes($r['ErrorContent'])) ?>', 
                                       '<?= htmlspecialchars($r['PageUrl']) ?>', 
                                       '<?= htmlspecialchars($r['DeviceType']) ?>', 
                                       '<?= date('d/m/Y H:i', strtotime($r['CreatedAt'])) ?>', 
                                       <?= $r['ReportID'] ?>
                                   )" title="Xem chi tiết">
                                    <i class="fas fa-eye"></i>
                                </a>

                                <a href="index.php?action=toggle&id=<?= $r['ReportID'] ?>" 
                                   class="btn btn-sm btn-light <?= $r['Status'] == 0 ? 'text-success' : 'text-warning' ?> me-1" 
                                   title="<?= $r['Status'] == 0 ? 'Đánh dấu đã sửa' : 'Hoàn tác' ?>">
                                    <i class="fas <?= $r['Status'] == 0 ? 'fa-check' : 'fa-undo' ?>"></i>
                                </a>

                                <a href="index.php?action=delete&id=<?= $r['ReportID'] ?>" 
                                   class="btn btn-sm btn-light text-danger" 
                                   onclick="return confirm('Bạn có chắc muốn xóa báo cáo này?');" 
                                   title="Xóa vĩnh viễn">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="fas fa-check-circle fa-3x mb-3 text-success opacity-25"></i><br>
                                Không có báo lỗi nào. Hệ thống hoạt động tốt!
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="errorDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content text-dark border-0">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-bug me-2"></i>Chi tiết Lỗi #<span id="modalId"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body bg-white">
                <div class="mb-3">
                    <label class="fw-bold text-muted small">NỘI DUNG LỖI</label>
                    <div class="p-3 bg-light border rounded text-danger fw-bold mt-1" id="modalErrorContent" style="white-space: pre-wrap;"></div>
                </div>
                
                <div class="row g-3">
                    <div class="col-6">
                        <label class="fw-bold text-muted small">THIẾT BỊ</label>
                        <div id="modalDevice" class="fw-bold text-dark mt-1"></div>
                    </div>
                    <div class="col-6">
                        <label class="fw-bold text-muted small">THỜI GIAN</label>
                        <div id="modalTime" class="fw-bold text-dark mt-1"></div>
                    </div>
                </div>
                
                <hr class="my-3">
                
                <div class="mb-2">
                    <label class="fw-bold text-muted small">TRANG XẢY RA LỖI</label>
                    <div class="input-group mt-1">
                        <input type="text" class="form-control form-control-sm" id="modalUrl" readonly>
                        <a href="#" id="modalLinkBtn" target="_blank" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-external-link-alt"></i> Mở trang
                        </a>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light justify-content-between">
                <a href="#" id="modalDeleteBtn" class="btn btn-outline-danger btn-sm" onclick="return confirm('Xóa báo cáo này?');">
                    <i class="fas fa-trash me-1"></i> Xóa
                </a>
                <div>
                    <a href="#" id="modalToggleBtn" class="btn btn-success btn-sm me-2">
                        <i class="fas fa-check me-1"></i> Đã sửa
                    </a>
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Đóng</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Script xử lý Modal
function showErrorDetail(event, content, url, device, time, id) {
    event.preventDefault();
    document.getElementById('modalId').innerText = id;
    document.getElementById('modalErrorContent').innerText = content;
    document.getElementById('modalDevice').innerText = device;
    document.getElementById('modalTime').innerText = time;
    document.getElementById('modalUrl').value = url;
    document.getElementById('modalLinkBtn').href = url;
    
    document.getElementById('modalDeleteBtn').href = "index.php?action=delete&id=" + id;
    document.getElementById('modalToggleBtn').href = "index.php?action=toggle&id=" + id;
    
    new bootstrap.Modal(document.getElementById('errorDetailModal')).show();
}
</script>

<?php require_once '../../includes/footer.php'; ?>