<?php
// includes/action_report.php
require_once 'db.php'; // Kết nối CSDL

// Trả về JSON để Javascript xử lý
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $content = trim($_POST['content'] ?? '');
    $device = trim($_POST['device'] ?? 'Unknown');
    $currentUrl = trim($_POST['url'] ?? '');

    if (empty($content)) {
        echo json_encode(['status' => 'error', 'message' => 'Vui lòng nhập nội dung lỗi!']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO error_reports (ErrorContent, DeviceType, PageUrl, CreatedAt) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$content, $device, $currentUrl]);

        echo json_encode(['status' => 'success', 'message' => 'Cảm ơn bạn đã báo lỗi!']);
    } catch (PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống, vui lòng thử lại sau.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid Request']);
}
?>