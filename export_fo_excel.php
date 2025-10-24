<?php
session_start();
require_once 'config.php';

// Ensure only admin can export, or adjust as per requirements
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

try {
    $sql = "SELECT id, lokasi, nama, ukuran, kode_pisau, quantity, model_box, jenis_board, cover_dlm, cover_lr, nama_box_lama, sales_pj, dibuat, keterangan, aksesoris, dudukan, jumlah_layer, logo, ukuran_poly, lokasi_poly, klise, tanggal_kirim, jam_kirim, dikirim_dari, tujuan_kirim, is_archived FROM orders";
    $conditions = [];
    $params = [];

    if (isset($_GET['id']) && !empty($_GET['id'])) {
        $conditions[] = "id = ?";
        $params[] = $_GET['id'];
    } else {
        // Default to showing non-archived records if no specific filter is set
        if (!isset($_GET['show_archived'])) {
            $conditions[] = "is_archived = 0";
        } else if ($_GET['show_archived'] == 'true') {
            $conditions[] = "is_archived = 1";
        } else {
            $conditions[] = "is_archived = 0"; // Explicitly show active if show_archived is false or other value
        }

        // Add search filter
        if (isset($_GET['search']) && $_GET['search'] !== '') {
            $searchTerm = '%' . $_GET['search'] . '%';
            $conditions[] = "(nama LIKE ? OR kode_pisau LIKE ? OR ukuran LIKE ? OR model_box LIKE ? OR jenis_board LIKE ? OR cover_dlm LIKE ? OR cover_lr LIKE ? OR sales_pj LIKE ? OR nama_box_lama LIKE ? OR lokasi LIKE ?)";
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
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

        // Define the explicit order of columns for export
        $export_columns = [
            'id', 'lokasi', 'nama', 'ukuran', 'kode_pisau', 'quantity', 'model_box', 'jenis_board', 
            'cover_dlm', 'cover_lr', 'nama_box_lama', 'sales_pj', 'dibuat', 'keterangan', 
            'aksesoris', 'dudukan', 'jumlah_layer', 'logo', 'ukuran_poly', 'lokasi_poly', 
            'klise', 'tanggal_kirim', 'jam_kirim', 'dikirim_dari', 'tujuan_kirim', 'is_archived'
        ];

        // Get column headers from the explicit list
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
            'cover_lr' => 'Cover LR',
            'nama_box_lama' => 'Nama Box Lama',
            'sales_pj' => 'PJ Sales',
            'dibuat' => 'Dibuat',
            'keterangan' => 'Keterangan',
            'aksesoris' => 'Aksesoris',
            'dudukan' => 'Dudukan',
            'jumlah_layer' => 'Jumlah Layer',
            'logo' => 'Logo',
            'ukuran_poly' => 'Ukuran Poly',
            'lokasi_poly' => 'Lokasi Poly',
            'klise' => 'Klise',
            'tanggal_kirim' => 'Tanggal Kirim',
            'jam_kirim' => 'Jam Kirim',
            'dikirim_dari' => 'Dikirim Dari',
            'tujuan_kirim' => 'Tujuan Kirim',
            'is_archived' => 'Archived'
        ];

        $headers = [];
        foreach ($export_columns as $columnName) {
            $headers[] = $column_display_names[$columnName] ?? $columnName;
        }
        fputcsv($output, $headers);

        // Output data rows
        foreach ($orders as $row) {
            $output_row = [];
            foreach ($export_columns as $columnName) {
                $value = $row[$columnName] ?? ''; // Get value, default to empty string if null

                // Apply specific formatting
                if ($columnName === 'cover_dlm') {
                    $value = preg_replace('/(supplier|jenis|warna|gsm|ukuran):\s*/i', '', $value);
                } else if ($columnName === 'cover_lr') {
                    $value = str_replace("\n", "; ", $value);
                }
                $output_row[] = $value;
            }
            fputcsv($output, $output_row);
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