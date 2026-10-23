<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'กรุณาเข้าสู่ระบบก่อนใช้งาน']);
    exit;
}

require_once 'classes/Database.php';
require_once 'classes/PhotoManager.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['image_data'])) {
    echo json_encode(['success' => false, 'message' => 'ไม่พบข้อมูลรูปภาพ']);
    exit;
}

$userId = $_SESSION['user_id'];
$frameType = $data['frame_type'] ?? 'white';
$filterType = $data['filter_type'] ?? 'normal';
$photoCount = $data['photo_count'] ?? 3;

// แปลงข้อมูลรูป Base64 เป็นไฟล์รูปภาพ PNG
$img = $data['image_data'];
$img = str_replace('data:image/png;base64,', '', $img);
$img = str_replace(' ', '+', $img);
$dataDecoded = base64_decode($img);

$filename = 'uploads/photo_' . $userId . '_' . time() . '_' . rand(1000, 9999) . '.png';

if (file_put_contents($filename, $dataDecoded)) {
    $database = new Database();
    $db = $database->getConnection();
    $photoManager = new PhotoManager($db);

    if ($photoManager->savePhoto($userId, $filename, $frameType, $filterType, $photoCount)) {
        echo json_encode(['success' => true, 'message' => 'บันทึกรูปภาพสำเร็จ', 'path' => $filename]);
    } else {
        echo json_encode(['success' => false, 'message' => 'ไม่สามารถบันทึกข้อมูลลงฐานข้อมูลได้']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'ไม่สามารถบันทึกไฟล์รูปภาพได้']);
}
?>
