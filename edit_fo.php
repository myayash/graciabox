<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Order</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="scripts.js"></script>
</head>
<body class="bg-gray-100 text-gray-900 pt-24 px-8 pb-8 font-mono">
    <?php include 'navbar.php'; ?>
    <h1 class="text-2xl font-bold mb-6 text-gray-800">Edit Order</h1>

    <?php
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
    require_once 'config.php';

// Check if the user is logged in at all.
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Check if the user has the 'admin' role.
if ($_SESSION['role'] !== 'admin') {
    die('Access Denied: You do not have permission to edit order data.');
}

    // Fetch data for dropdowns
    $customers = $pdo->query("SELECT * FROM customer WHERE is_archived = 0")->fetchAll(PDO::FETCH_ASSOC);
    $model_boxes_data = $pdo->query("SELECT * FROM model_box WHERE is_archived = 0")->fetchAll(PDO::FETCH_ASSOC);
    $boards = $pdo->query("SELECT * FROM board WHERE is_archived = 0")->fetchAll(PDO::FETCH_ASSOC);
    $papers = $pdo->query("SELECT * FROM kertas WHERE is_archived = 0")->fetchAll(PDO::FETCH_ASSOC);
    $sales_reps = $pdo->query("SELECT * FROM empl_sales WHERE is_archived = 0")->fetchAll(PDO::FETCH_ASSOC);
    $barangs = $pdo->query("SELECT * FROM barang WHERE is_archived = 0")->fetchAll(PDO::FETCH_ASSOC);

    // Fetch distinct values for kertas table columns
    $suppliers = $pdo->query("SELECT DISTINCT supplier FROM kertas WHERE is_archived = 0")->fetchAll(PDO::FETCH_COLUMN);
    $jenis_kertas = $pdo->query("SELECT DISTINCT jenis FROM kertas WHERE is_archived = 0")->fetchAll(PDO::FETCH_COLUMN);
    $warnas = $pdo->query("SELECT DISTINCT warna FROM kertas WHERE is_archived = 0")->fetchAll(PDO::FETCH_COLUMN);
    $gsms = $pdo->query("SELECT DISTINCT gsm FROM kertas WHERE is_archived = 0")->fetchAll(PDO::FETCH_COLUMN);
    $ukurans = $pdo->query("SELECT DISTINCT ukuran FROM kertas WHERE is_archived = 0")->fetchAll(PDO::FETCH_COLUMN);

    $order = null;
    $message = '';
    $message_type = '';

    // Check if ID is provided in URL
    if (isset($_GET['id']) && !empty($_GET['id'])) {
        $id = $_GET['id'];

        try {
            // Fetch existing order data
            $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
            $stmt->execute([$id]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$order) {
                $message = "Order not found.";
                $message_type = 'error';
            }
        } catch (PDOException $e) {
            $message = "Error fetching order: " . htmlspecialchars($e->getMessage());
            $message_type = 'error';
        }
    } else {
        $message = "No order ID provided.";
        $message_type = 'error';
    }

    // Handle form submission for updating order
    if (isset($_POST['update_order']) && $order) {
        $nama = trim($_POST['nama']);
        $kode_pisau = trim($_POST['kode_pisau']);
        $jenis_board = trim($_POST['jenis_board']);
        $sales_pj = trim($_POST['sales_pj']);
        $lokasi = trim($_POST['lokasi']);
        $quantity = trim($_POST['quantity']);
        if (!empty($quantity) && substr($quantity, -4) !== ' pcs') {
            $quantity .= ' pcs';
        }

        // Construct cover_dlm string
        $cover_dlm = "supplier:" . trim($_POST['cover_dalam_supplier']) . " - jenis:" . trim($_POST['cover_dalam_jenis']) . " - warna:" . trim($_POST['cover_dalam_warna']) . " - gsm:" . trim($_POST['cover_dalam_gsm']) . " - ukuran:" . trim($_POST['cover_dalam_ukuran']);

        // Construct cover_lr string
        $cover_luar_radio = $_POST['cover_luar_radio'];
        $cover_luar_str = "({$cover_luar_radio}) " . trim($_POST['cover_luar_supplier']) . " - " . trim($_POST['cover_luar_jenis']) . " - " . trim($_POST['cover_luar_warna']) . " - " . trim($_POST['cover_luar_gsm']) . " gsm - " . trim($_POST['cover_luar_ukuran']);
        $box_str = "(box) " . trim($_POST['box_supplier']) . " - " . trim($_POST['box_jenis']) . " - " . trim($_POST['box_warna']) . " - " . trim($_POST['box_gsm']) . " gsm - " . trim($_POST['box_ukuran']);
        $dudukan_str = "(dudukan) " . trim($_POST['dudukan_supplier']) . " - " . trim($_POST['dudukan_jenis']) . " - " . trim($_POST['dudukan_warna']) . " - " . trim($_POST['dudukan_gsm']) . " gsm - " . trim($_POST['dudukan_ukuran']);
        $cover_lr = $cover_luar_str . "\n" . $box_str . "\n" . $dudukan_str;

        $ukuran = '';
        $model_box = '';
        $nama_box_lama_value = NULL;

        if ($kode_pisau === 'baru') {
            $ukuran = trim($_POST['length']) . ' x ' . trim($_POST['width']) . ' x ' . trim($_POST['height']);
            $model_box = trim($_POST['model_box']);
            // For 'baru', nama_box_lama is not directly set, it's a new item
        } else if ($kode_pisau === 'lama') {
            $barang_id = trim($_POST['barang_lama']);
            $stmt = $pdo->prepare("SELECT * FROM barang WHERE id = ?");
            $stmt->execute([$barang_id]);
            $barang = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($barang) {
                $ukuran = $barang['ukuran'];
                $model_box = $barang['model_box'];
                $nama_box_lama_value = $barang['nama'];
            } else {
                $message = "Selected 'Barang' not found.";
                $message_type = 'error';
            }
        }

        if (empty($message) && !empty($nama) && !empty($kode_pisau) && !empty($ukuran) && !empty($model_box) && !empty($jenis_board)) {
            try {
                $stmt = $pdo->prepare("UPDATE orders SET nama = ?, kode_pisau = ?, ukuran = ?, model_box = ?, jenis_board = ?, cover_dlm = ?, cover_lr = ?, sales_pj = ?, nama_box_lama = ?, lokasi = ?, quantity = ? WHERE id = ?");
                $stmt->execute([$nama, $kode_pisau, $ukuran, $model_box, $jenis_board, $cover_dlm, $cover_lr, $sales_pj, $nama_box_lama_value, $lokasi, $quantity, $order['id']]);
                $message = "Order updated successfully!";
                $message_type = 'success';
                header("Location: daftar_fo.php");
                exit;
            } catch (PDOException $e) {
                $message = "Error updating order: " . htmlspecialchars($e->getMessage());
                $message_type = 'error';
            }
        } else if (empty($message)) {
            $message = "Please fill in all required fields correctly.";
            $message_type = 'error';
        }
    }

    if ($message) {
        echo "<div class=\"p-4 mb-4 text-sm ". ($message_type == 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800') . "\" role=\"alert\">" . $message . "</div>";
    }

    if ($order) {
        $ukuran_parts = explode(' x ', $order['ukuran']);
        $length = $ukuran_parts[0] ?? '';
        $width = $ukuran_parts[1] ?? '';
        $height = $ukuran_parts[2] ?? '';

        // Parse cover_dlm
        $cover_dlm_parts = [];
        preg_match('/supplier:(.*?)\s*-\s*jenis:(.*?)\s*-\s*warna:(.*?)\s*-\s*gsm:(.*?)\s*-\s*ukuran:(.*)/i', $order['cover_dlm'], $cover_dlm_parts);
        $cover_dalam_supplier = trim($cover_dlm_parts[1] ?? '');
        $cover_dalam_jenis = trim($cover_dlm_parts[2] ?? '');
        $cover_dalam_warna = trim($cover_dlm_parts[3] ?? '');
        $cover_dalam_gsm = trim($cover_dlm_parts[4] ?? '');
        $cover_dalam_ukuran = trim($cover_dlm_parts[5] ?? '');

        // Parse cover_lr
        $cover_lr_lines = explode("\n", $order['cover_lr']);
        $cover_luar_str = $cover_lr_lines[0] ?? '';
        $box_str = $cover_lr_lines[1] ?? '';
        $dudukan_str = $cover_lr_lines[2] ?? '';

        // Parse cover_luar_str
        $cover_luar_parts = [];
        preg_match('/\((.*?)\)\s*(.*?)\s*-\s*(.*?)\s*-\s*(.*?)\s*-\s*(.*?) gsm\s*-\s*(.*)/i', $cover_luar_str, $cover_luar_parts);
        $cover_luar_radio = trim($cover_luar_parts[1] ?? '');
        $cover_luar_supplier = trim($cover_luar_parts[2] ?? '');
        $cover_luar_jenis = trim($cover_luar_parts[3] ?? '');
        $cover_luar_warna = trim($cover_luar_parts[4] ?? '');
        $cover_luar_gsm = trim($cover_luar_parts[5] ?? '');
        $cover_luar_ukuran = trim($cover_luar_parts[6] ?? '');

        // Parse box_str
        $box_parts = [];
        preg_match('/\(box\)\s*(.*?)\s*-\s*(.*?)\s*-\s*(.*?)\s*-\s*(.*?) gsm\s*-\s*(.*)/i', $box_str, $box_parts);
        $box_supplier = trim($box_parts[1] ?? '');
        $box_jenis = trim($box_parts[2] ?? '');
        $box_warna = trim($box_parts[3] ?? '');
        $box_gsm = trim($box_parts[4] ?? '');
        $box_ukuran = trim($box_parts[5] ?? '');

        // Parse dudukan_str
        $dudukan_parts = [];
        preg_match('/\(dudukan\)\s*(.*?)\s*-\s*(.*?)\s*-\s*(.*?)\s*-\s*(.*?) gsm\s*-\s*(.*)/i', $dudukan_str, $dudukan_parts);
        $dudukan_supplier = trim($dudukan_parts[1] ?? '');
        $dudukan_jenis = trim($dudukan_parts[2] ?? '');
        $dudukan_warna = trim($dudukan_parts[3] ?? '');
        $dudukan_gsm = trim($dudukan_parts[4] ?? '');
        $dudukan_ukuran = trim($dudukan_parts[5] ?? '');

    ?>
        <form action="" method="POST" class="bg-white p-8 shadow-lg">
                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($order['id']); ?>">
            <div class="mb-4">
                <label for="nama" class="block text-gray-800 text-sm font-semibold mb-2">Nama:</label>
                <select id="nama" name="nama" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required>
                    <?php foreach ($customers as $customer): ?>
                        <option value="<?= htmlspecialchars($customer['nama']) ?>" <?= ($order['nama'] == $customer['nama']) ? 'selected' : '' ?>><?= htmlspecialchars($customer['nama']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-4">
                <label class="block text-gray-800 text-sm font-semibold mb-2">Kode Pisau:</label>
                <div class="mt-2">
                    <label class="inline-flex items-center">
                        <input type="radio" name="kode_pisau" value="baru" onchange="handleKodePisauChange(this.value)" class="form-radio h-4 w-4 text-blue-600 transition duration-150 ease-in-out" <?= ($order['kode_pisau'] == 'baru') ? 'checked' : '' ?>> 
                        <span class="ml-2 text-gray-800">Baru</span>
                    </label>
                    <label class="inline-flex items-center ml-6">
                        <input type="radio" name="kode_pisau" value="lama" onchange="handleKodePisauChange(this.value)" class="form-radio h-4 w-4 text-blue-600 transition duration-150 ease-in-out" <?= ($order['kode_pisau'] == 'lama') ? 'checked' : '' ?>> 
                        <span class="ml-2 text-gray-800">Lama</span>
                    </label>
                </div>
            </div>

            <div class="mb-4">
                <label for="quantity" class="block text-gray-800 text-sm font-semibold mb-2">Quantity:</label>
                <input type="number" name="quantity" id="quantity" step="1" value="<?= htmlspecialchars($order['quantity']) ?>" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required>
            </div>

            <div id="kode_pisau_baru_fields" class="mb-4" style="display:none;">
                <div class="mb-4">
                    <label for="model_box" class="block text-gray-800 text-sm font-semibold mb-2">Model Box:</label>
                    <select id="model_box" name="model_box" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required>
                        <option value="">Pilih Model Box</option>
                        <?php foreach ($model_boxes_data as $model_box_item): ?>
                            <option value="<?= htmlspecialchars($model_box_item['nama']) ?>" <?= ($order['model_box'] == $model_box_item['nama']) ? 'selected' : '' ?>><?= htmlspecialchars($model_box_item['nama']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <input type="hidden" name="nama_box_baru" id="nama_box_baru">
            </div>

            <div id="kode_pisau_lama_fields" class="mb-4" style="display:none;">
                <label for="barang_lama" class="block text-gray-800 text-sm font-semibold mb-2">Nama Box Lama:</label>
                <select name="barang_lama" id="barang_lama" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out">
                    <option value="">Pilih Barang</option>
                    <?php foreach ($barangs as $barang): ?>
                        <option value="<?= htmlspecialchars($barang['id']) ?>" <?= ($order['nama_box_lama'] == $barang['nama']) ? 'selected' : '' ?>><?= htmlspecialchars($barang['model_box'] . ' - ' . $barang['ukuran'] . ' - ' . $barang['nama']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-4">
                <label for="jenis_board" class="block text-gray-800 text-sm font-semibold mb-2">Jenis Board:</label>
                <select id="jenis_board" name="jenis_board" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required>
                    <?php foreach ($boards as $board_item): ?>
                        <option value="<?= htmlspecialchars($board_item['jenis']) ?>" <?= ($order['jenis_board'] == $board_item['jenis']) ? 'selected' : '' ?>><?= htmlspecialchars($board_item['jenis']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-4">
                <label class="block text-gray-800 text-sm font-semibold mb-2">Cover Dalam:</label>
                <div class="flex space-x-2">
                    <select name="cover_dalam_supplier" id="cover_dalam_supplier" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required>
                        <option value="" disabled>Supplier</option>
                        <?php foreach ($suppliers as $s): ?>
                            <option value="<?= $s ?>" <?= ($s == $cover_dalam_supplier) ? 'selected' : '' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="cover_dalam_jenis" id="cover_dalam_jenis" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required>
                        <option value="" disabled>Jenis</option>
                        <?php foreach ($jenis_kertas as $j): ?>
                            <option value="<?= $j ?>" <?= ($j == $cover_dalam_jenis) ? 'selected' : '' ?>><?= $j ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="cover_dalam_warna" id="cover_dalam_warna" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required>
                        <option value="" disabled>Warna</option>
                        <?php foreach ($warnas as $w): ?>
                            <option value="<?= $w ?>" <?= ($w == $cover_dalam_warna) ? 'selected' : '' ?>><?= $w ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="cover_dalam_gsm" id="cover_dalam_gsm" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required>
                        <option value="" disabled>GSM</option>
                        <?php foreach ($gsms as $g): ?>
                            <option value="<?= $g ?>" <?= ($g == $cover_dalam_gsm) ? 'selected' : '' ?>><?= $g ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="cover_dalam_ukuran" id="cover_dalam_ukuran" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required>
                        <option value="" disabled>Ukuran</option>
                        <?php foreach ($ukurans as $u): ?>
                            <option value="<?= $u ?>" <?= ($u == $cover_dalam_ukuran) ? 'selected' : '' ?>><?= $u ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-gray-800 text-sm font-semibold mb-2">Cover Luar:</label>
                <div class="mt-2 pl-4">
                    <label class="inline-flex items-center">
                        <input type="radio" name="cover_luar_radio" value="lidah" class="form-radio h-4 w-4 text-blue-600 transition duration-150 ease-in-out" <?= ($cover_luar_radio == 'lidah') ? 'checked' : '' ?> required>
                        <span class="ml-2 text-gray-800">Lidah</span>
                    </label>
                    <label class="inline-flex items-center ml-6">
                        <input type="radio" name="cover_luar_radio" value="selongsong" class="form-radio h-4 w-4 text-blue-600 transition duration-150 ease-in-out" <?= ($cover_luar_radio == 'selongsong') ? 'checked' : '' ?> required>
                        <span class="ml-2 text-gray-800">Selongsong</span>
                    </label>
                    <label class="inline-flex items-center ml-6">
                        <input type="radio" name="cover_luar_radio" value="kuping" class="form-radio h-4 w-4 text-blue-600 transition duration-150 ease-in-out" <?= ($cover_luar_radio == 'kuping') ? 'checked' : '' ?> required>
                        <span class="ml-2 text-gray-800">Kuping</span>
                    </label>
                    <label class="inline-flex items-center ml-6">
                        <input type="radio" name="cover_luar_radio" value="tutup_atas" class="form-radio h-4 w-4 text-blue-600 transition duration-150 ease-in-out" <?= ($cover_luar_radio == 'tutup_atas') ? 'checked' : '' ?> required>
                        <span class="ml-2 text-gray-800">Tutup atas</span>
                    </label>
                </div>
                <div class="flex space-x-2 mt-2 pl-4">
                    <select name="cover_luar_supplier" id="cover_luar_supplier" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required>
                        <option value="" disabled>Supplier</option>
                        <?php foreach ($suppliers as $s): ?>
                            <option value="<?= $s ?>" <?= ($s == $cover_luar_supplier) ? 'selected' : '' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="cover_luar_jenis" id="cover_luar_jenis" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required>
                        <option value="" disabled>Jenis</option>
                        <?php foreach ($jenis_kertas as $j): ?>
                            <option value="<?= $j ?>" <?= ($j == $cover_luar_jenis) ? 'selected' : '' ?>><?= $j ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="cover_luar_warna" id="cover_luar_warna" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required>
                        <option value="" disabled>Warna</option>
                        <?php foreach ($warnas as $w): ?>
                            <option value="<?= $w ?>" <?= ($w == $cover_luar_warna) ? 'selected' : '' ?>><?= $w ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="cover_luar_gsm" id="cover_luar_gsm" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required>
                        <option value="" disabled>GSM</option>
                        <?php foreach ($gsms as $g): ?>
                            <option value="<?= $g ?>" <?= ($g == $cover_luar_gsm) ? 'selected' : '' ?>><?= $g ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="cover_luar_ukuran" id="cover_luar_ukuran" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required>
                        <option value="" disabled>Ukuran</option>
                        <?php foreach ($ukurans as $u): ?>
                            <option value="<?= $u ?>" <?= ($u == $cover_luar_ukuran) ? 'selected' : '' ?>><?= $u ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-gray-800 text-sm font-semibold mb-2 flex space-x-2 pl-4">Box</label>
                <div class="flex space-x-2 pl-4">
                    <select name="box_supplier" id="box_supplier" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required>
                        <option value="" disabled>Supplier</option>
                        <?php foreach ($suppliers as $s): ?>
                            <option value="<?= $s ?>" <?= ($s == $box_supplier) ? 'selected' : '' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="box_jenis" id="box_jenis" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required>
                        <option value="" disabled>Jenis</option>
                        <?php foreach ($jenis_kertas as $j): ?>
                            <option value="<?= $j ?>" <?= ($j == $box_jenis) ? 'selected' : '' ?>><?= $j ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="box_warna" id="box_warna" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required>
                        <option value="" disabled>Warna</option>
                        <?php foreach ($warnas as $w): ?>
                            <option value="<?= $w ?>" <?= ($w == $box_warna) ? 'selected' : '' ?>><?= $w ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="box_gsm" id="box_gsm" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required>
                        <option value="" disabled>GSM</option>
                        <?php foreach ($gsms as $g): ?>
                            <option value="<?= $g ?>" <?= ($g == $box_gsm) ? 'selected' : '' ?>><?= $g ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="box_ukuran" id="box_ukuran" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required>
                        <option value="" disabled>Ukuran</option>
                        <?php foreach ($ukurans as $u): ?>
                            <option value="<?= $u ?>" <?= ($u == $box_ukuran) ? 'selected' : '' ?>><?= $u ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-gray-800 text-sm font-semibold mb-2 flex space-x-2 pl-4">Dudukan</label>
                <div class="flex space-x-2 pl-4">
                    <select name="dudukan_supplier" id="dudukan_supplier" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required>
                        <option value="" disabled>Supplier</option>
                        <?php foreach ($suppliers as $s): ?>
                            <option value="<?= $s ?>" <?= ($s == $dudukan_supplier) ? 'selected' : '' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="dudukan_jenis" id="dudukan_jenis" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required>
                        <option value="" disabled>Jenis</option>
                        <?php foreach ($jenis_kertas as $j): ?>
                            <option value="<?= $j ?>" <?= ($j == $dudukan_jenis) ? 'selected' : '' ?>><?= $j ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="dudukan_warna" id="dudukan_warna" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required>
                        <option value="" disabled>Warna</option>
                        <?php foreach ($warnas as $w): ?>
                            <option value="<?= $w ?>" <?= ($w == $dudukan_warna) ? 'selected' : '' ?>><?= $w ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="dudukan_gsm" id="dudukan_gsm" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required>
                        <option value="" disabled>GSM</option>
                        <?php foreach ($gsms as $g): ?>
                            <option value="<?= $g ?>" <?= ($g == $dudukan_gsm) ? 'selected' : '' ?>><?= $g ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="dudukan_ukuran" id="dudukan_ukuran" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required>
                        <option value="" disabled>Ukuran</option>
                        <?php foreach ($ukurans as $u): ?>
                            <option value="<?= $u ?>" <?= ($u == $dudukan_ukuran) ? 'selected' : '' ?>><?= $u ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-gray-800 text-sm font-semibold mb-2">Lokasi:</label>
                <div class="mt-2">
                    <label class="inline-flex items-center">
                        <input type="radio" name="lokasi" value="BSD" class="form-radio h-4 w-4 text-blue-600 transition duration-150 ease-in-out" <?= ($order['lokasi'] == 'BSD') ? 'checked' : '' ?>> 
                        <span class="ml-2 text-gray-800">BSD</span>
                    </label>
                    <label class="inline-flex items-center ml-6">
                        <input type="radio" name="lokasi" value="Pondok Aren" class="form-radio h-4 w-4 text-blue-600 transition duration-150 ease-in-out" <?= ($order['lokasi'] == 'Pondok Aren') ? 'checked' : '' ?>> 
                        <span class="ml-2 text-gray-800">Pondok Aren</span>
                    </label>
                </div>
            </div>

            <div class="mb-6">
                <label for="sales_pj" class="block text-gray-800 text-sm font-semibold mb-2">Sales PJ:</label>
                <select id="sales_pj" name="sales_pj" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out">
                    <?php foreach ($sales_reps as $sales_rep): ?>
                        <option value="<?= htmlspecialchars($sales_rep['nama']) ?>" <?= ($order['sales_pj'] == $sales_rep['nama']) ? 'selected' : '' ?>><?= htmlspecialchars($sales_rep['nama']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <p class="text-gray-600 text-sm mb-4">Dibuat: <?php echo htmlspecialchars($order['dibuat']); ?></p>

            <div class="flex items-center justify-start space-x-4">
                <input type="submit" name="update_order" value="Update Order" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out">
                <a href="daftar_fo.php" class="inline-block align-baseline font-semibold text-sm text-blue-600 hover:text-blue-700">Cancel</a>
            </div>        </form>
    <?php
    }
    ?>

    <script>
        function handleKodePisauChange(value) {
            const baruFields = document.getElementById('kode_pisau_baru_fields');
            const lamaFields = document.getElementById('kode_pisau_lama_fields');

            if (value === 'baru') {
                baruFields.style.display = 'block';
                lamaFields.style.display = 'none';
            } else if (value === 'lama') {
                baruFields.style.display = 'none';
                lamaFields.style.display = 'block';
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const namaCustomerDropdown = document.getElementById('nama');
            const namaBoxBaruInput = document.getElementById('nama_box_baru');

            namaCustomerDropdown.addEventListener('change', function() {
                namaBoxBaruInput.value = this.value;
            });

            const kodePisauRadios = document.querySelectorAll('input[name="kode_pisau"]');
            kodePisauRadios.forEach(radio => {
                if (radio.checked) {
                    handleKodePisauChange(radio.value);
                }
            });

            const supplierDropdown = document.getElementById('supplier');
            const jenisKertasDropdown = document.getElementById('jenis_kertas');
            const warnaDropdown = document.getElementById('warna');
            const gsmDropdown = document.getElementById('gsm');
            const ukuranKertasDropdown = document.getElementById('ukuran_kertas');

            function updateDropdown(dropdown, options, selectedValue) {
                dropdown.innerHTML = '<option value="" disabled>' + dropdown.firstElementChild.textContent + '</option>';
                options.forEach(option => {
                    const optionElement = document.createElement('option');
                    optionElement.value = option;
                    optionElement.textContent = option;
                    if (option === selectedValue) {
                        optionElement.selected = true;
                    }
                    dropdown.appendChild(optionElement);
                });
            }

            function fetchAndUpdateOptions() {
                const supplier = supplierDropdown.value;
                const jenis = jenisKertasDropdown.value;
                const warna = warnaDropdown.value;
                const gsm = gsmDropdown.value;

                fetch(`get_kertas_options.php?supplier=${supplier}&jenis=${jenis}&warna=${warna}&gsm=${gsm}`)
                    .then(response => response.json())
                    .then(data => {
                        updateDropdown(jenisKertasDropdown, data.jenis, jenis);
                        updateDropdown(warnaDropdown, data.warna, warna);
                        updateDropdown(gsmDropdown, data.gsm, gsm);
                        updateDropdown(ukuranKertasDropdown, data.ukuran, ukuran_kertas.value);
                    });
            }

            supplierDropdown.addEventListener('change', fetchAndUpdateOptions);
            jenisKertasDropdown.addEventListener('change', fetchAndUpdateOptions);
            warnaDropdown.addEventListener('change', fetchAndUpdateOptions);
            gsmDropdown.addEventListener('change', fetchAndUpdateOptions);

            // Initial population of dropdowns
            fetchAndUpdateOptions();
        });
    </script>

</body>
</html>