<?php
session_start();
require_once 'config.php';

// Ensure only admin can export, or adjust as per requirements
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

try {
    $sql = "SELECT * FROM orders";
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
            $searchable_columns = [
                'id', 'nama', 'kode_pisau', 'ukuran', 'model_box', 'jenis_board', 
                'cover_dlm', 'sales_pj', 'nama_box_lama', 'lokasi', 'cover_lr', 
                'keterangan', 'quantity', 'feedback_cust', 'aksesoris', 
                'ket_aksesoris', 'dudukan', 'jumlah_layer', 'logo', 'ukuran_poly', 
                'lokasi_poly', 'klise', 'tanggal_kirim', 'jam_kirim', 
                'dikirim_dari', 'tujuan_kirim', 'tanggal_dp', 'pelunasan', 
                'ongkir', 'packing', 'biaya'
            ];
            
            $conditions[] = "(" . implode(" LIKE ? OR ", $searchable_columns) . " LIKE ?)";
            
            $params = array_merge($params, array_fill(0, count($searchable_columns), $searchTerm));
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

        // Use all columns from the first row to generate headers, maintaining order
        $export_columns = array_keys($orders[0]);

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
            'feedback_cust' => 'Feedback Customer',
            'aksesoris' => 'Aksesoris',
            'ket_aksesoris' => 'Keterangan Aksesoris',
            'dudukan' => 'Dudukan',
            'dudukan_img' => 'Gambar Dudukan',
            'jumlah_layer' => 'Jumlah Layer',
            'logo' => 'Logo',
            'logo_img' => 'Gambar Logo',
            'ukuran_poly' => 'Ukuran Poly',
            'lokasi_poly' => 'Lokasi Poly',
            'poly_img' => 'Gambar Poly',
            'klise' => 'Klise',
            'tanggal_kirim' => 'Tanggal Kirim',
            'jam_kirim' => 'Jam Kirim',
            'dikirim_dari' => 'Dikirim Dari',
            'tujuan_kirim' => 'Tujuan Kirim',
            'tanggal_dp' => 'Tanggal DP',
            'pelunasan' => 'Pelunasan',
            'ongkir' => 'Ongkir',
            'packing' => 'Packing',
            'biaya' => 'Biaya',
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