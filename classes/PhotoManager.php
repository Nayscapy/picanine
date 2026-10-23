<?php
class PhotoManager {
    private $conn;
    private $table_name = "photos";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function savePhoto($user_id, $image_path, $frame_type, $filter_type, $photo_count) {
        $query = "INSERT INTO " . $this->table_name . " (user_id, image_path, frame_type, filter_type, photo_count) VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        if (!$stmt) return false;

        $stmt->bind_param("isssi", $user_id, $image_path, $frame_type, $filter_type, $photo_count);
        return $stmt->execute();
    }

    public function getUserPhotos($user_id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE user_id = ? ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        if (!$stmt) return false;

        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        return $stmt->get_result();
    }

    public function deletePhoto($photo_id, $user_id) {
        $query_select = "SELECT image_path FROM " . $this->table_name . " WHERE id = ? AND user_id = ?";
        $stmt_select = $this->conn->prepare($query_select);
        if ($stmt_select) {
            $stmt_select->bind_param("ii", $photo_id, $user_id);
            $stmt_select->execute();
            $result = $stmt_select->get_result();

            if ($row = $result->fetch_assoc()) {
                if (file_exists($row['image_path'])) {
                    unlink($row['image_path']);
                }
            }
        }

        $query = "DELETE FROM " . $this->table_name . " WHERE id = ? AND user_id = ?";
        $stmt = $this->conn->prepare($query);
        if (!$stmt) return false;

        $stmt->bind_param("ii", $photo_id, $user_id);
        return $stmt->execute();
    }
}
?>
