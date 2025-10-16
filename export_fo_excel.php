<?php
session_start();
require_once 'config.php';

// Ensure only admin can export, or adjust as per requirements
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

try {
    $sql = "SELECT id, lokasi, nama, ukuran, kode_pisau, quantity, model_box, jenis_board, cover_dlm, nama_box_lama, sales_pj, dibuat FROM orders";
    $conditions = [];
    $params = [];

    if (isset($_GET['id']) && !empty($_GET['id'])) {
        $conditions[] = "id = ?";
        $params[] = $_GET['id'];
    } else {
        // Add archived/active filter
        if (isset($_GET['show_archived']) && $_GET['show_archived'] == 'true') {
            $conditions[] = "is_archived = 1";
        } else {
            $conditions[] = "is_archived = 0";
        }

        // Add search filter
        if (isset($_GET['search']) && $_GET['search'] !== '') {
            $searchTerm = '%' . $_GET['search'] . '%';
            $conditions[] = "(nama LIKE ? OR kode_pisau LIKE ? OR ukuran LIKE ? OR model_box LIKE ? OR jenis_board LIKE ? OR cover_dlm LIKE ? OR sales_pj LIKE ? OR nama_box_lama LIKE ? OR lokasi LIKE ?)";
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        }
    }

    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($orders) {
        // Output as CSV
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=orders_export.csv');

        $output = fopen('php://output', 'w');

        // Get column headers from the first row
        $column_display_names = [
            'id' => 'ID',
            'lokasi' => 'Lokasi',
            'nama' => 'Nama Customer',
            'ukuran' => 'Ukuran (cm)',
            'kode_pisau' => 'Kode Pisau',
            'quantity' => 'Quantity (pcs)',
            'model_box' => 'Model Box',
            'jenis_board' => 'Jenis Board',
            'cover_dlm' => 'Cover Dalam',
            'nama_box_lama' => 'Nama Box',
            'sales_pj' => 'PJ Sales',
            'dibuat' => 'Dibuat'
        ];

        $headers = [];
        foreach (array_keys($orders[0]) as $columnName) {
            $headers[] = $column_display_names[$columnName] ?? $columnName;
        }
        fputcsv($output, $headers);

        // Output data rows
        foreach ($orders as $row) {
            fputcsv($output, $row);
        }

        fclose($output);
        exit;
    } else {
        echo "No orders found to export.";
    }
} catch (PDOException $e) {
    echo "Error: " . htmlspecialchars($e->getMessage());
}
?>