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
    if (empty($_POST['nama_customer']) || empty($_POST['kode_pisau']) || empty($_POST['jenis_board']) || empty($_POST['cover_dalam_supplier']) || empty($_POST['cover_dalam_jenis']) || empty($_POST['cover_dalam_warna']) || empty($_POST['cover_dalam_gsm']) || empty($_POST['cover_dalam_ukuran']) || empty($_POST['cover_luar_supplier']) || empty($_POST['cover_luar_jenis']) || empty($_POST['cover_luar_warna']) || empty($_POST['cover_luar_gsm']) || empty($_POST['cover_luar_ukuran']) || empty($_POST['box_supplier']) || empty($_POST['box_jenis']) || empty($_POST['box_warna']) || empty($_POST['box_gsm']) || empty($_POST['box_ukuran']) || empty($_POST['dudukan_supplier']) || empty($_POST['dudukan_jenis']) || empty($_POST['dudukan_warna']) || empty($_POST['dudukan_gsm']) || empty($_POST['dudukan_ukuran'])) {
        die('Error: Nama Customer, Kode Pisau, Jenis Board, and all Cover Dalam, Cover Luar, Box, and Dudukan fields are required.');
    }

    if (isset($_POST['kode_pisau'])) {
        if ($_POST['kode_pisau'] === 'baru') {
            if (empty($_POST['model_box_baru']) || empty($_POST['length']) || empty($_POST['width']) || empty($_POST['height']) || empty($_POST['dibuat_oleh'])) {
                die('Error: Missing required fields for new kode pisau. Please go back and fill them.');
            }
        } else if ($_POST['kode_pisau'] === 'lama') {
            if (empty($_POST['barang_lama']) || empty($_POST['dibuat_oleh'])) {
                die('Error: Missing required fields for old kode pisau. Please go back and fill them.');
            }
        }

        $nama = $_POST['nama_customer'];
        $kode_pisau = $_POST['kode_pisau'];
        $jenis_board = $_POST['jenis_board'];
        $cover_dlm_supplier = $_POST['cover_dalam_supplier'];
        $cover_dlm_jenis = $_POST['cover_dalam_jenis'];
        $cover_dlm_warna = $_POST['cover_dalam_warna'];
        $cover_dlm_gsm = $_POST['cover_dalam_gsm'];
        $cover_dlm_ukuran = $_POST['cover_dalam_ukuran'];

        $cover_dlm = "supplier:{$cover_dlm_supplier}, jenis:{$cover_dlm_jenis}, warna:{$cover_dlm_warna}, gsm:{$cover_dlm_gsm}, ukuran:{$cover_dlm_ukuran}";
        $cover_luar_supplier = $_POST['cover_luar_supplier'];
        $cover_luar_jenis = $_POST['cover_luar_jenis'];
        $cover_luar_warna = $_POST['cover_luar_warna'];
        $cover_luar_gsm = $_POST['cover_luar_gsm'];
        $cover_luar_ukuran = $_POST['cover_luar_ukuran'];
        $cover_luar = "supplier:{$cover_luar_supplier}, jenis:{$cover_luar_jenis}, warna:{$cover_luar_warna}, gsm:{$cover_luar_gsm}, ukuran:{$cover_luar_ukuran}";

        $box_supplier = $_POST['box_supplier'];
        $box_jenis = $_POST['box_jenis'];
        $box_warna = $_POST['box_warna'];
        $box_gsm = $_POST['box_gsm'];
        $box_ukuran = $_POST['box_ukuran'];

        $dudukan_supplier = $_POST['dudukan_supplier'];
        $dudukan_jenis = $_POST['dudukan_jenis'];
        $dudukan_warna = $_POST['dudukan_warna'];
        $dudukan_gsm = $_POST['dudukan_gsm'];
        $dudukan_ukuran = $_POST['dudukan_ukuran'];

        $box = "supplier:{$box_supplier}, jenis:{$box_jenis}, warna:{$box_warna}, gsm:{$box_gsm}, ukuran:{$box_ukuran}";
        $dudukan = "supplier:{$dudukan_supplier}, jenis:{$dudukan_jenis}, warna:{$dudukan_warna}, gsm:{$dudukan_gsm}, ukuran:{$dudukan_ukuran}";
        $sales_pj = $_POST['dibuat_oleh'];
        $lokasi = $_POST['lokasi'];
        $quantity = $_POST['quantity'] . ' pcs';
        $nama_box_lama_value = NULL;

        if ($kode_pisau === 'baru') {
            $ukuran = $_POST['length'] . ' x ' . $_POST['width'] . ' x ' . $_POST['height'];
            $model_box = $_POST['model_box_baru'];

            $stmt = $pdo->prepare("INSERT INTO barang (model_box, ukuran, nama) VALUES (?, ?, ?)");
            $stmt->execute([$model_box, $ukuran, $nama]);
            $barang_id = $pdo->lastInsertId();

        } else { // lama
            $barang_id = $_POST['barang_lama'];
            $stmt = $pdo->prepare("SELECT * FROM barang WHERE id = ?");
            $stmt->execute([$barang_id]);
            $barang = $stmt->fetch(PDO::FETCH_ASSOC);

            $ukuran = $barang['ukuran'];
            $model_box = $barang['model_box'];
            $nama_box_lama_value = $barang['nama'];
        }

        $stmt = $pdo->prepare("INSERT INTO orders (nama, kode_pisau, ukuran, model_box, jenis_board, cover_dlm, cover_luar, box, dudukan, sales_pj, nama_box_lama, lokasi, quantity) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$nama, $kode_pisau, $ukuran, $model_box, $jenis_board, $cover_dlm, $cover_luar, $box, $dudukan, $sales_pj, $nama_box_lama_value, $lokasi, $quantity]);

        header("Location: index.php");
        exit;
    }
}

// Fetch data for dropdowns
$customers = $pdo->query("SELECT * FROM customer WHERE is_archived = 0")->fetchAll(PDO::FETCH_ASSOC);
$model_boxes = $pdo->query("SELECT * FROM model_box WHERE is_archived = 0")->fetchAll(PDO::FETCH_ASSOC);
$boards = $pdo->query("SELECT * FROM board WHERE is_archived = 0")->fetchAll(PDO::FETCH_ASSOC);
$distinct_suppliers = $pdo->query("SELECT DISTINCT supplier FROM kertas WHERE is_archived = 0")->fetchAll(PDO::FETCH_ASSOC);
$sales_reps = $pdo->query("SELECT * FROM empl_sales WHERE is_archived = 0")->fetchAll(PDO::FETCH_ASSOC);
$barangs = $pdo->query("SELECT * FROM barang WHERE is_archived = 0")->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>bikin form order</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="scripts.js"></script>
</head>
<body class="bg-gray-100 text-gray-900 pt-24 px-8 pb-8 font-mono">

    <?php include 'navbar.php'; ?>
    <h1 class="text-2xl font-bold mb-6 text-gray-800">bikin form order</h1>

    <form action="bikin_fo.php" method="post" class="bg-white p-8 shadow-lg">
        <div class="mb-4">
            <label for="nama_customer" class="block text-gray-800 text-sm font-semibold mb-2">Nama Customer:</label>
            <select name="nama_customer" id="nama_customer" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required>
                <option value="" disabled selected>Pilih Customer</option>
                <?php foreach ($customers as $customer): ?>
                    <option value="<?= $customer['nama'] ?>"><?= $customer['nama'] ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-4">
            <label class="block text-gray-800 text-sm font-semibold mb-2">Ukuran (cm):</label>
            <div class="flex space-x-2">
                <input type="text" name="length" placeholder="Length" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out">
                <input type="text" name="width" placeholder="Width" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out">
                <input type="text" name="height" placeholder="Height" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out">
            </div>
        </div>

        <div class="mb-4">
            <label class="block text-gray-800 text-sm font-semibold mb-2">Kode Pisau:</label>
            <div class="mt-2">
                <label class="inline-flex items-center">
                    <input type="radio" name="kode_pisau" value="baru" onchange="handleKodePisauChange(this.value)" class="form-radio h-4 w-4 text-blue-600 transition duration-150 ease-in-out" required>
                    <span class="ml-2 text-gray-800">Baru</span>
                </label>
                <label class="inline-flex items-center ml-6">
                    <input type="radio" name="kode_pisau" value="lama" onchange="handleKodePisauChange(this.value)" class="form-radio h-4 w-4 text-blue-600 transition duration-150 ease-in-out" required>
                    <span class="ml-2 text-gray-800">Lama</span>
                </label>
            </div>
        </div>

        <div class="mb-4">
            <label for="quantity" class="block text-gray-800 text-sm font-semibold mb-2">Quantity:</label>
            <input type="number" name="quantity" id="quantity" step="1" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required>
        </div>

        <div id="kode_pisau_baru_fields" style="display:none;" class="mb-4">
            <div class="mb-4">
                <label for="model_box_baru" class="block text-gray-800 text-sm font-semibold mb-2">Model Box:</label>
                <select name="model_box_baru" id="model_box_baru" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required>
                    <option value="" disabled selected>Pilih Model Box</option>
                    <?php foreach ($model_boxes as $model_box): ?>
                        <option value="<?= $model_box['nama'] ?>"><?= $model_box['nama'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

        </div>

        <div id="kode_pisau_lama_fields" style="display:none;" class="mb-4">
            <label for="barang_lama" class="block text-gray-800 text-sm font-semibold mb-2">Barang:</label>
            <select name="barang_lama" id="barang_lama" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out">
                <option value="" disabled selected>Pilih Barang</option>
                <?php foreach ($barangs as $barang): ?>
                    <option value="<?= $barang['id'] ?>"><?= $barang['model_box'] . ' - ' . $barang['ukuran'] . ' - ' . $barang['nama'] ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div id="shared_fields" style="display:none;" class="mb-4">
            <div class="mb-4">
                <label for="jenis_board" class="block text-gray-800 text-sm font-semibold mb-2">Jenis Board:</label>
                <select name="jenis_board" id="jenis_board" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required>
                    <option value="" disabled selected>Pilih Jenis Board</option>
                    <?php foreach ($boards as $board): ?>
                        <option value="<?= $board['jenis'] ?>"><?= $board['jenis'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-4">
                <label class="block text-gray-800 text-sm font-semibold mb-2">Cover Dalam:</label>
                <div class="flex space-x-2">
                    <select name="cover_dalam_supplier" id="cover_dalam_supplier" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required>
                        <option value="" disabled selected>Supplier</option>
                        <?php foreach ($distinct_suppliers as $supplier): ?>
                            <option value="<?= $supplier['supplier'] ?>"><?= $supplier['supplier'] ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="cover_dalam_jenis" id="cover_dalam_jenis" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required disabled>
                        <option value="" disabled selected>Jenis</option>
                    </select>
                    <select name="cover_dalam_warna" id="cover_dalam_warna" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required disabled>
                        <option value="" disabled selected>Warna</option>
                    </select>
                    <select name="cover_dalam_gsm" id="cover_dalam_gsm" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required disabled>
                        <option value="" disabled selected>GSM</option>
                    </select>
                    <select name="cover_dalam_ukuran" id="cover_dalam_ukuran" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required disabled>
                        <option value="" disabled selected>Ukuran</option>
                    </select>
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-gray-800 text-sm font-semibold mb-2">Cover Luar:</label>
                <div class="mt-2">
                    <label class="inline-flex items-center">
                        <input type="radio" name="cover_luar" value="lidah" class="form-radio h-4 w-4 text-blue-600 transition duration-150 ease-in-out" required>
                        <span class="ml-2 text-gray-800">Lidah</span>
                    </label>
                    <label class="inline-flex items-center ml-6">
                        <input type="radio" name="cover_luar" value="selongsong" class="form-radio h-4 w-4 text-blue-600 transition duration-150 ease-in-out" required>
                        <span class="ml-2 text-gray-800">Selongsong</span>
                    </label>
                    <label class="inline-flex items-center ml-6">
                        <input type="radio" name="cover_luar" value="kuping" class="form-radio h-4 w-4 text-blue-600 transition duration-150 ease-in-out" required>
                        <span class="ml-2 text-gray-800">Kuping</span>
                    </label>
                    <label class="inline-flex items-center ml-6">
                        <input type="radio" name="cover_luar" value="tutup_atas" class="form-radio h-4 w-4 text-blue-600 transition duration-150 ease-in-out" required>
                        <span class="ml-2 text-gray-800">Tutup atas</span>
                    </label>
                </div>
                <div class="flex space-x-2 mt-2">
                    <select name="cover_luar_supplier" id="cover_luar_supplier" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required onchange="updateKertasOptions('cover_luar')">
                        <option value="" disabled selected>Supplier</option>
                        <?php foreach ($distinct_suppliers as $supplier): ?>
                            <option value="<?= $supplier['supplier'] ?>"><?= $supplier['supplier'] ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="cover_luar_jenis" id="cover_luar_jenis" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required disabled>
                        <option value="" disabled selected>Jenis</option>
                    </select>
                    <select name="cover_luar_warna" id="cover_luar_warna" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required disabled>
                        <option value="" disabled selected>Warna</option>
                    </select>
                    <select name="cover_luar_gsm" id="cover_luar_gsm" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required disabled>
                        <option value="" disabled selected>GSM</option>
                    </select>
                    <select name="cover_luar_ukuran" id="cover_luar_ukuran" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required disabled>
                        <option value="" disabled selected>Ukuran</option>
                    </select>
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-gray-800 text-sm font-semibold mb-2">Box:</label>
                <div class="flex space-x-2">
                    <select name="box_supplier" id="box_supplier" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required>
                        <option value="" disabled selected>Supplier</option>
                        <?php foreach ($distinct_suppliers as $supplier): ?>
                            <option value="<?= $supplier['supplier'] ?>"><?= $supplier['supplier'] ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="box_jenis" id="box_jenis" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required disabled>
                        <option value="" disabled selected>Jenis</option>
                    </select>
                    <select name="box_warna" id="box_warna" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required disabled>
                        <option value="" disabled selected>Warna</option>
                    </select>
                    <select name="box_gsm" id="box_gsm" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required disabled>
                        <option value="" disabled selected>GSM</option>
                    </select>
                    <select name="box_ukuran" id="box_ukuran" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required disabled>
                        <option value="" disabled selected>Ukuran</option>
                    </select>
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-gray-800 text-sm font-semibold mb-2">Dudukan:</label>
                <div class="flex space-x-2">
                    <select name="dudukan_supplier" id="dudukan_supplier" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required>
                        <option value="" disabled selected>Supplier</option>
                        <?php foreach ($distinct_suppliers as $supplier): ?>
                            <option value="<?= $supplier['supplier'] ?>"><?= $supplier['supplier'] ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="dudukan_jenis" id="dudukan_jenis" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required disabled>
                        <option value="" disabled selected>Jenis</option>
                    </select>
                    <select name="dudukan_warna" id="dudukan_warna" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required disabled>
                        <option value="" disabled selected>Warna</option>
                    </select>
                    <select name="dudukan_gsm" id="dudukan_gsm" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required disabled>
                        <option value="" disabled selected>GSM</option>
                    </select>
                    <select name="dudukan_ukuran" id="dudukan_ukuran" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required disabled>
                        <option value="" disabled selected>Ukuran</option>
                    </select>
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-gray-800 text-sm font-semibold mb-2">Lokasi:</label>
                <div class="mt-2">
                    <label class="inline-flex items-center">
                        <input type="radio" name="lokasi" value="BSD" class="form-radio h-4 w-4 text-blue-600 transition duration-150 ease-in-out" required>
                        <span class="ml-2 text-gray-800">BSD</span>
                    </label>
                    <label class="inline-flex items-center ml-6">
                        <input type="radio" name="lokasi" value="Pondok Aren" class="form-radio h-4 w-4 text-blue-600 transition duration-150 ease-in-out" required>
                        <span class="ml-2 text-gray-800">Pondok Aren</span>
                    </label>
                </div>
            </div>

            <div class="mb-4">
                <label for="dibuat_oleh" class="block text-gray-800 text-sm font-semibold mb-2">Dibuat Oleh:</label>
                <select name="dibuat_oleh" id="dibuat_oleh" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out">
                    <option value="" disabled selected>Pilih Karyawan Sales</option>
                    <?php foreach ($sales_reps as $sales_rep): ?>
                        <option value="<?= $sales_rep['nama'] ?>"><?= $sales_rep['nama'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="flex items-center justify-start space-x-4">
            <input type="submit" value="Submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out">
            <a href="daftar_fo.php" class="inline-block align-baseline font-semibold text-sm text-blue-600 hover:text-blue-800">
                Back
            </a>
        </div>
    </form>

    <script>
        function handleKodePisauChange(value) {
            const modelBoxBaru = document.getElementById('model_box_baru');
            const dibuatOleh = document.getElementById('dibuat_oleh');
            const barangLama = document.getElementById('barang_lama');
            const sharedFields = document.getElementById('shared_fields');
            const kodePisauBaruFields = document.getElementById('kode_pisau_baru_fields');
            const kodePisauLamaFields = document.getElementById('kode_pisau_lama_fields');

            // Hide all conditional fields initially
            kodePisauBaruFields.style.display = 'none';
            kodePisauLamaFields.style.display = 'none';
            sharedFields.style.display = 'none';

            // Disable all inputs within the conditional fields
            modelBoxBaru.required = false;
            barangLama.required = false;
            dibuatOleh.required = false;

            if (value === 'baru') {
                kodePisauBaruFields.style.display = 'block';
                sharedFields.style.display = 'block';
                modelBoxBaru.required = true;
                dibuatOleh.required = true;
            } else if (value === 'lama') {
                kodePisauLamaFields.style.display = 'block';
                sharedFields.style.display = 'block';
                barangLama.required = true;
                dibuatOleh.required = true;
            }
        }
    </script>
    </body>
</html>