<?php
include 'config.php';
session_start();

// Check if the user is logged in at all.
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Check if the user has the 'admin' role.
if ($_SESSION['role'] !== 'admin') {
    die('Access Denied: You do not have permission to create new orders.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Merge the latest POST data (shipping) with session data (fo + previous shipping if any)
    $form_data = array_merge($_SESSION['order_form'] ?? [], $_POST);

    // Updated validation to check for essential fields.
    if (empty($form_data['nama_customer']) || empty($form_data['kode_pisau']) || empty($form_data['jenis_board']) || empty($form_data['cover_dalam_supplier']) || empty($form_data['cover_dalam_jenis']) || empty($form_data['cover_dalam_warna']) || empty($form_data['cover_dalam_gsm']) || empty($form_data['cover_dalam_ukuran']) || empty($form_data['cover_luar_supplier']) || empty($form_data['cover_luar_jenis']) || empty($form_data['cover_luar_warna']) || empty($form_data['cover_luar_gsm']) || empty($form_data['cover_luar_ukuran']) || empty($form_data['box_supplier']) || empty($form_data['box_jenis']) || empty($form_data['box_warna']) || empty($form_data['box_gsm']) || empty($form_data['box_ukuran']) || empty($form_data['dudukan_supplier']) || empty($form_data['dudukan_jenis']) || empty($form_data['dudukan_warna']) || empty($form_data['dudukan_gsm']) || empty($form_data['dudukan_ukuran'])) {
        die('Error: Nama Customer, Kode Pisau, Jenis Board, and all Cover Dalam, Cover Luar, Box, and Dudukan fields are required.');
    }

    if (isset($form_data['kode_pisau'])) {
        if ($form_data['kode_pisau'] === 'baru') {
            if (empty($form_data['model_box_baru']) || empty($form_data['length']) || empty($form_data['width']) || empty($form_data['height']) || empty($form_data['dibuat_oleh'])) {
                die('Error: Missing required fields for new kode pisau. Please go back and fill them.');
            }
        } else if ($form_data['kode_pisau'] === 'lama') {
            if (empty($form_data['barang_lama']) || empty($form_data['dibuat_oleh'])) {
                die('Error: Missing required fields for old kode pisau. Please go back and fill them.');
            }
        }

        $nama = $form_data['nama_customer'];
        $kode_pisau = $form_data['kode_pisau'];
        $jenis_board = $form_data['jenis_board'];
        $cover_dlm_supplier = $form_data['cover_dalam_supplier'];
        $cover_dlm_jenis = $form_data['cover_dalam_jenis'];
        $cover_dlm_warna = $form_data['cover_dalam_warna'];
        $cover_dlm_gsm = $form_data['cover_dalam_gsm'];
        $cover_dlm_ukuran = $form_data['cover_dalam_ukuran'];

        $cover_dlm = "supplier:{$cover_dlm_supplier} - jenis:{$cover_dlm_jenis} - warna:{$cover_dlm_warna} - gsm:{$cover_dlm_gsm} - ukuran:{$cover_dlm_ukuran}";
        
        // Handle Cover Luar, Box, Dudukan
        $cover_luar_radio = $form_data['cover_luar_radio'];
        $cover_luar_supplier = $form_data['cover_luar_supplier'];
        $cover_luar_jenis = $form_data['cover_luar_jenis'];
        $cover_luar_warna = $form_data['cover_luar_warna'];
        $cover_luar_gsm = $form_data['cover_luar_gsm'];
        $cover_luar_ukuran = $form_data['cover_luar_ukuran'];
        $cover_luar_str = "({$cover_luar_radio}) {$cover_luar_supplier} - {$cover_luar_jenis} - {$cover_luar_warna} - {$cover_luar_gsm} gsm - {$cover_luar_ukuran}";

        $box_supplier = $form_data['box_supplier'];
        $box_jenis = $form_data['box_jenis'];
        $box_warna = $form_data['box_warna'];
        $box_gsm = $form_data['box_gsm'];
        $box_ukuran = $form_data['box_ukuran'];
        $box_str = "(box) {$box_supplier} - {$box_jenis} - {$box_warna} - {$box_gsm} gsm - {$box_ukuran}";

        $dudukan_supplier = $form_data['dudukan_supplier'];
        $dudukan_jenis = $form_data['dudukan_jenis'];
        $dudukan_warna = $form_data['dudukan_warna'];
        $dudukan_gsm = $form_data['dudukan_gsm'];
        $dudukan_ukuran = $form_data['dudukan_ukuran'];
        $dudukan_str = "(dudukan) {$dudukan_supplier} - {$dudukan_jenis} - {$dudukan_warna} - {$dudukan_gsm} gsm - {$dudukan_ukuran}";

        $cover_lr = $cover_luar_str . "\n" . $box_str . "\n" . $dudukan_str;

        $sales_pj = $form_data['dibuat_oleh'];
        $lokasi = $form_data['lokasi'];
        $quantity = $form_data['quantity'] . ' pcs';
        $keterangan = $form_data['keterangan'] ?? NULL;
        $nama_box_lama_value = NULL;

        if ($kode_pisau === 'baru') {
            $ukuran = $form_data['length'] . ' x ' . $form_data['width'] . ' x ' . $form_data['height'];
            $model_box = $form_data['model_box_baru'];

            $stmt = $pdo->prepare("INSERT INTO barang (model_box, ukuran, nama) VALUES (?, ?, ?)");
            $stmt->execute([$model_box, $ukuran, $nama]);
            $barang_id = $pdo->lastInsertId();

        } else { // lama
            $barang_id = $form_data['barang_lama'];
            $stmt = $pdo->prepare("SELECT * FROM barang WHERE id = ?");
            $stmt->execute([$barang_id]);
            $barang = $stmt->fetch(PDO::FETCH_ASSOC);

            $ukuran = $barang['ukuran'];
            $model_box = $barang['model_box'];
            $nama_box_lama_value = $barang['nama'];
        }

        $aksesoris_jenis = $form_data['aksesoris_jenis'] ?? NULL;
        $aksesoris_ukuran = $form_data['aksesoris_ukuran'] ?? NULL;
        $aksesoris_warna = $form_data['aksesoris_warna'] ?? NULL;
        $aksesoris = "jenis:{$aksesoris_jenis} - ukuran:{$aksesoris_ukuran} - warna:{$aksesoris_warna}";

        $dudukan_id = $form_data['dudukan'] ?? NULL;
        $dudukan_jenis = NULL;
        if ($dudukan_id) {
            $stmt = $pdo->prepare("SELECT jenis FROM dudukan WHERE id = ?");
            $stmt->execute([$dudukan_id]);
            $dudukan_row = $stmt->fetch(PDO::FETCH_ASSOC);
            $dudukan_jenis = $dudukan_row['jenis'];
        }

        $jumlah_layer = $form_data['jumlah_layer'] ?? NULL;
        $logo = $form_data['logo'] ?? NULL;
        $ukuran_poly = $form_data['ukuran_poly'] ?? NULL;
        $lokasi_poly = $form_data['lokasi_poly'] ?? NULL;
        $klise = $form_data['klise'] ?? NULL;

        // Shipping data
        $tanggal_kirim = $form_data['tanggal_kirim'];
        $jam_kirim = $form_data['jam_kirim'];
        $dikirim_dari = $form_data['dikirim_dari'];
        $tujuan_kirim = $form_data['tujuan_kirim'];

        $stmt = $pdo->prepare("INSERT INTO orders (nama, kode_pisau, ukuran, model_box, jenis_board, cover_dlm, sales_pj, nama_box_lama, lokasi, quantity, keterangan, cover_lr, aksesoris, dudukan, jumlah_layer, logo, ukuran_poly, lokasi_poly, klise, tanggal_kirim, jam_kirim, dikirim_dari, tujuan_kirim) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$nama, $kode_pisau, $ukuran, $model_box, $jenis_board, $cover_dlm, $sales_pj, $nama_box_lama_value, $lokasi, $quantity, $keterangan, $cover_lr, $aksesoris, $dudukan_jenis, $jumlah_layer, $logo, $ukuran_poly, $lokasi_poly, $klise, $tanggal_kirim, $jam_kirim, $dikirim_dari, $tujuan_kirim]);

        // Unset the session data
        unset($_SESSION['order_form']);

        header("Location: index.php");
        exit;
    }
}