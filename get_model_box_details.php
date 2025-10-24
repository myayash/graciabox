<?php
include 'config.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Invalid request'];

if (isset($_GET['model_box_name'])) {
    $modelBoxName = $_GET['model_box_name'];

    try {
        $stmt = $pdo->prepare("SELECT box_luar, box_dlm FROM model_box WHERE nama = ? AND is_archived = 0");
        $stmt->execute([$modelBoxName]);
        $modelBoxDetails = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($modelBoxDetails) {
            $response = ['success' => true, 'data' => $modelBoxDetails];
        } else {
            $response = ['success' => false, 'message' => 'Model Box not found or archived.'];
        }
    } catch (PDOException $e) {
        $response = ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
    }
} else {
    $response = ['success' => false, 'message' => 'Model Box name not provided.'];
}

echo json_encode($response);
?>