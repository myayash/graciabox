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



// Fetch data for dropdowns
$customers = $pdo->query("SELECT * FROM customer WHERE is_archived = 0")->fetchAll(PDO::FETCH_ASSOC);
$model_boxes = $pdo->query("SELECT * FROM model_box WHERE is_archived = 0")->fetchAll(PDO::FETCH_ASSOC);
$boards = $pdo->query("SELECT * FROM board WHERE is_archived = 0")->fetchAll(PDO::FETCH_ASSOC);
$distinct_suppliers = $pdo->query("SELECT DISTINCT supplier FROM kertas WHERE is_archived = 0")->fetchAll(PDO::FETCH_ASSOC);
$sales_reps = $pdo->query("SELECT * FROM empl_sales WHERE is_archived = 0")->fetchAll(PDO::FETCH_ASSOC);
$barangs = $pdo->query("SELECT * FROM barang WHERE is_archived = 0")->fetchAll(PDO::FETCH_ASSOC);
$aksesoris_jenis = $pdo->query("SELECT DISTINCT jenis FROM aksesoris")->fetchAll(PDO::FETCH_ASSOC);
$aksesoris_ukuran = $pdo->query("SELECT DISTINCT ukuran FROM aksesoris")->fetchAll(PDO::FETCH_ASSOC);
$aksesoris_warna = $pdo->query("SELECT DISTINCT warna FROM aksesoris")->fetchAll(PDO::FETCH_ASSOC);
$dudukan_options = $pdo->query("SELECT * FROM dudukan")->fetchAll(PDO::FETCH_ASSOC);
$logo_options = $pdo->query("SELECT DISTINCT jenis FROM logo")->fetchAll(PDO::FETCH_ASSOC);
$logo_uk_poly_options = $pdo->query("SELECT DISTINCT uk_poly FROM logo")->fetchAll(PDO::FETCH_ASSOC);

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

    <form action="bikin_shipping.php" method="post" class="bg-white p-8 shadow-lg">
        <h2 class="text-xl font-bold mb-4 text-gray-400">BOX</h2>
        <div class="border-b-2 border-gray-300 mb-6"></div>
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
                <div class="mt-2 pl-4">
                    <label class="inline-flex items-center">
                        <input type="radio" name="cover_luar_radio" value="lidah" class="form-radio h-4 w-4 text-blue-600 transition duration-150 ease-in-out" required>
                        <span class="ml-2 text-gray-800">Lidah</span>
                    </label>
                    <label class="inline-flex items-center ml-6">
                        <input type="radio" name="cover_luar_radio" value="selongsong" class="form-radio h-4 w-4 text-blue-600 transition duration-150 ease-in-out" required>
                        <span class="ml-2 text-gray-800">Selongsong</span>
                    </label>
                    <label class="inline-flex items-center ml-6">
                        <input type="radio" name="cover_luar_radio" value="kuping" class="form-radio h-4 w-4 text-blue-600 transition duration-150 ease-in-out" required>
                        <span class="ml-2 text-gray-800">Kuping</span>
                    </label>
                    <label class="inline-flex items-center ml-6">
                        <input type="radio" name="cover_luar_radio" value="tutup_atas" class="form-radio h-4 w-4 text-blue-600 transition duration-150 ease-in-out" required>
                        <span class="ml-2 text-gray-800">Tutup atas</span>
                    </label>
                </div>
                <div class="flex space-x-2 mt-2 pl-4">
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
                <label class="block text-gray-800 text-sm font-semibold mb-2 flex space-x-2 pl-4">Box</label>
                <div class="flex space-x-2 pl-4">
                    <select name="box_supplier" id="box_supplier" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required onchange="updateKertasOptions('box')">
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
                <label class="block text-gray-800 text-sm font-semibold mb-2 flex space-x-2 pl-4">Dudukan</label>
                <div class="flex space-x-2 pl-4">
                    <select name="dudukan_supplier" id="dudukan_supplier" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required onchange="updateKertasOptions('dudukan')">
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
        </div>

        <h2 class="text-xl font-bold mb-4 text-gray-400">SPK</h2>
        <div class="border-b-2 border-gray-300 mb-6"></div>

        <div class="mb-4">
            <label class="block text-gray-800 text-sm font-semibold mb-2">Aksesoris:</label>
            <div class="flex space-x-2">
                <select name="aksesoris_jenis" id="aksesoris_jenis" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out">
                    <option value="" disabled selected>Pilih Jenis</option>
                    <?php foreach ($aksesoris_jenis as $jenis): ?>
                        <option value="<?= $jenis['jenis'] ?>"><?= $jenis['jenis'] ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="aksesoris_ukuran" id="aksesoris_ukuran" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out">
                    <option value="" disabled selected>Pilih Ukuran</option>
                    <?php foreach ($aksesoris_ukuran as $ukuran): ?>
                        <option value="<?= $ukuran['ukuran'] ?>"><?= $ukuran['ukuran'] ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="aksesoris_warna" id="aksesoris_warna" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out">
                    <option value="" disabled selected>Pilih Warna</option>
                    <?php foreach ($aksesoris_warna as $warna): ?>
                        <option value="<?= $warna['warna'] ?>"><?= $warna['warna'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="mb-4">
            <div class="flex space-x-4">
                <div class="w-1/2">
                    <label for="dudukan" class="block text-gray-800 text-sm font-semibold mb-2">Dudukan:</label>
                    <select name="dudukan" id="dudukan" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out">
                        <option value="" disabled selected>Pilih Dudukan</option>
                        <?php foreach ($dudukan_options as $dudukan): ?>
                            <option value="<?= $dudukan['id'] ?>"><?= $dudukan['jenis'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="w-1/2">
                    <label for="jumlah_layer" class="block text-gray-800 text-sm font-semibold mb-2">Jumlah layer</label>
                    <input type="number" name="jumlah_layer" id="jumlah_layer" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" min="0" step="1">
                </div>
            </div>
        </div>

        <div class="mb-4">
            <div class="flex space-x-4">
                <div class="w-1/2">
                    <label for="logo" class="block text-gray-800 text-sm font-semibold mb-2">Logo:</label>
                    <select name="logo" id="logo" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out">
                        <option value="" disabled selected>Pilih Logo</option>
                        <?php foreach ($logo_options as $logo): ?>
                            <option value="<?= $logo['jenis'] ?>"><?= $logo['jenis'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="w-1/2">
                    <label for="ukuran_poly" class="block text-gray-800 text-sm font-semibold mb-2">Ukuran Poly:</label>
                    <select name="ukuran_poly" id="ukuran_poly" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out">
                        <option value="" disabled selected>Pilih Ukuran Poly</option>
                        <?php foreach ($logo_uk_poly_options as $uk_poly): ?>
                            <option value="<?= $uk_poly['uk_poly'] ?>"><?= $uk_poly['uk_poly'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="mb-4">
            <div class="flex space-x-4">
                <div class="w-1/2">
                    <label class="block text-gray-800 text-sm font-semibold mb-2">Lokasi Poly:</label>
                    <div class="mt-2">
                        <label class="inline-flex items-center">
                            <input type="radio" name="lokasi_poly" value="Pabrik" class="form-radio h-4 w-4 text-blue-600 transition duration-150 ease-in-out" required>
                            <span class="ml-2 text-gray-800">Pabrik</span>
                        </label>
                        <label class="inline-flex items-center ml-6">
                            <input type="radio" name="lokasi_poly" value="Luar" class="form-radio h-4 w-4 text-blue-600 transition duration-150 ease-in-out" required>
                            <span class="ml-2 text-gray-800">Luar</span>
                        </label>
                    </div>
                </div>
                <div class="w-1/2">
                    <label class="block text-gray-800 text-sm font-semibold mb-2">Klise:</label>
                    <div class="mt-2">
                        <label class="inline-flex items-center">
                            <input type="radio" name="klise" value="In Stock" class="form-radio h-4 w-4 text-blue-600 transition duration-150 ease-in-out" required>
                            <span class="ml-2 text-gray-800">In Stock</span>
                        </label>
                        <label class="inline-flex items-center ml-6">
                            <input type="radio" name="klise" value="Bikin baru" class="form-radio h-4 w-4 text-blue-600 transition duration-150 ease-in-out" required>
                            <span class="ml-2 text-gray-800">Bikin baru</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <div class="mb-4">
            <label for="keterangan" class="block text-gray-800 text-sm font-semibold mb-2">Keterangan:</label>
            <textarea name="keterangan" id="keterangan" rows="3" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out"></textarea>
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

        <div class="flex items-center justify-start space-x-4">
            <input type="submit" value="Next" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out">
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