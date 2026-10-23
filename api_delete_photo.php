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

if (!$data || !isset($data['photo_id'])) {
    echo json_encode(['success' => false, 'message' => 'ไม่พบ รหัสรูปภาพ']);
    exit;
}

$userId = $_SESSION['user_id'];
$photoId = intval($data['photo_id']);

$database = new Database();
$db = $database->getConnection();
$photoManager = new PhotoManager($db);

if ($photoManager->deletePhoto($photoId, $userId)) {
    echo json_encode(['success' => true, 'message' => 'ลบรูปภาพเรียบร้อยแล้ว']);
} else {
    echo json_encode(['success' => false, 'message' => 'ไม่สามารถลบรูปภาพได้']);
}
?>
