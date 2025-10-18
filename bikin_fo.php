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

// Handle POST from shipping (back navigation)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['from_shipping'])) {
    $_SESSION['order_form'] = array_merge($_SESSION['order_form'] ?? [], $_POST);
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

$order_form = $_SESSION['order_form'] ?? [];

// Prepare options for each prefix
$prefixes = ['cover_dalam', 'cover_luar', 'box', 'dudukan'];
$options = [];
foreach ($prefixes as $prefix) {
    $options[$prefix] = [
        'jenis' => [],
        'warna' => [],
        'gsm' => [],
        'ukuran' => [],
        'disabled' => 'disabled'
    ];
    if (isset($order_form[$prefix . '_supplier']) && !empty($order_form[$prefix . '_supplier'])) {
        $supplier = $order_form[$prefix . '_supplier'];
        $base_params = [$supplier];

        $stmt = $pdo->prepare("SELECT DISTINCT jenis FROM kertas WHERE is_archived = 0 AND supplier = ? ORDER BY jenis ASC");
        $stmt->execute($base_params);
        $options[$prefix]['jenis'] = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $stmt = $pdo->prepare("SELECT DISTINCT warna FROM kertas WHERE is_archived = 0 AND supplier = ? ORDER BY warna ASC");
        $stmt->execute($base_params);
        $options[$prefix]['warna'] = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $stmt = $pdo->prepare("SELECT DISTINCT gsm FROM kertas WHERE is_archived = 0 AND supplier = ? ORDER BY gsm ASC");
        $stmt->execute($base_params);
        $options[$prefix]['gsm'] = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $stmt = $pdo->prepare("SELECT DISTINCT ukuran FROM kertas WHERE is_archived = 0 AND supplier = ? ORDER BY ukuran ASC");
        $stmt->execute($base_params);
        $options[$prefix]['ukuran'] = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $options[$prefix]['disabled'] = '';
    }
}

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
                <option value="" disabled <?php echo !isset($order_form['nama_customer']) ? 'selected' : ''; ?>>Pilih Customer</option>
                <?php foreach ($customers as $customer): ?>
                    <option value="<?= $customer['nama'] ?>" <?php echo (isset($order_form['nama_customer']) && $order_form['nama_customer'] === $customer['nama']) ? 'selected' : ''; ?>><?= $customer['nama'] ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-4">
            <label class="block text-gray-800 text-sm font-semibold mb-2">Ukuran (cm):</label>
            <div class="flex space-x-2">
                <input type="text" name="length" placeholder="Length" value="<?php echo htmlspecialchars($order_form['length'] ?? ''); ?>" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out">
                <input type="text" name="width" placeholder="Width" value="<?php echo htmlspecialchars($order_form['width'] ?? ''); ?>" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out">
                <input type="text" name="height" placeholder="Height" value="<?php echo htmlspecialchars($order_form['height'] ?? ''); ?>" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out">
            </div>
        </div>

        <div class="mb-4">
            <label class="block text-gray-800 text-sm font-semibold mb-2">Kode Pisau:</label>
            <div class="mt-2">
                <label class="inline-flex items-center">
                    <input type="radio" name="kode_pisau" value="baru" onchange="handleKodePisauChange(this.value)" class="form-radio h-4 w-4 text-blue-600 transition duration-150 ease-in-out" required <?php echo (isset($order_form['kode_pisau']) && $order_form['kode_pisau'] === 'baru') ? 'checked' : ''; ?>>
                    <span class="ml-2 text-gray-800">Baru</span>
                </label>
                <label class="inline-flex items-center ml-6">
                    <input type="radio" name="kode_pisau" value="lama" onchange="handleKodePisauChange(this.value)" class="form-radio h-4 w-4 text-blue-600 transition duration-150 ease-in-out" required <?php echo (isset($order_form['kode_pisau']) && $order_form['kode_pisau'] === 'lama') ? 'checked' : ''; ?>>
                    <span class="ml-2 text-gray-800">Lama</span>
                </label>
            </div>
        </div>

        <div class="mb-4">
            <label for="quantity" class="block text-gray-800 text-sm font-semibold mb-2">Quantity:</label>
            <input type="number" name="quantity" id="quantity" step="1" value="<?php echo htmlspecialchars($order_form['quantity'] ?? ''); ?>" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required>
        </div>

        <div id="kode_pisau_baru_fields" style="display:none;" class="mb-4">
            <div class="mb-4">
                <label for="model_box_baru" class="block text-gray-800 text-sm font-semibold mb-2">Model Box:</label>
                <select name="model_box_baru" id="model_box_baru" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required>
                    <option value="" disabled <?php echo !isset($order_form['model_box_baru']) ? 'selected' : ''; ?>>Pilih Model Box</option>
                    <?php foreach ($model_boxes as $model_box): ?>
                        <option value="<?= $model_box['nama'] ?>" <?php echo (isset($order_form['model_box_baru']) && $order_form['model_box_baru'] === $model_box['nama']) ? 'selected' : ''; ?>><?= $model_box['nama'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

        </div>

        <div id="kode_pisau_lama_fields" style="display:none;" class="mb-4">
            <label for="barang_lama" class="block text-gray-800 text-sm font-semibold mb-2">Barang:</label>
            <select name="barang_lama" id="barang_lama" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out">
                <option value="" disabled <?php echo !isset($order_form['barang_lama']) ? 'selected' : ''; ?>>Pilih Barang</option>
                <?php foreach ($barangs as $barang): ?>
                    <option value="<?= $barang['id'] ?>" <?php echo (isset($order_form['barang_lama']) && $order_form['barang_lama'] == $barang['id']) ? 'selected' : ''; ?>><?= $barang['model_box'] . ' - ' . $barang['ukuran'] . ' - ' . $barang['nama'] ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div id="shared_fields" style="display:none;" class="mb-4">
            <div class="mb-4">
                <label for="jenis_board" class="block text-gray-800 text-sm font-semibold mb-2">Jenis Board:</label>
                <select name="jenis_board" id="jenis_board" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required>
                    <option value="" disabled <?php echo !isset($order_form['jenis_board']) ? 'selected' : ''; ?>>Pilih Jenis Board</option>
                    <?php foreach ($boards as $board): ?>
                        <option value="<?= $board['jenis'] ?>" <?php echo (isset($order_form['jenis_board']) && $order_form['jenis_board'] === $board['jenis']) ? 'selected' : ''; ?>><?= $board['jenis'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-4">
                <label class="block text-gray-800 text-sm font-semibold mb-2">Cover Dalam:</label>
                <div class="flex space-x-2">
                    <select name="cover_dalam_supplier" id="cover_dalam_supplier" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required onchange="updateKertasOptions('cover_dalam')">
                        <option value="" disabled <?php echo !isset($order_form['cover_dalam_supplier']) ? 'selected' : ''; ?>>Supplier</option>
                        <?php foreach ($distinct_suppliers as $supplier): ?>
                            <option value="<?= $supplier['supplier'] ?>" <?php echo (isset($order_form['cover_dalam_supplier']) && $order_form['cover_dalam_supplier'] === $supplier['supplier']) ? 'selected' : ''; ?>><?= $supplier['supplier'] ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="cover_dalam_jenis" id="cover_dalam_jenis" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required <?php echo $options['cover_dalam']['disabled']; ?>>
                        <option value="" disabled <?php echo !isset($order_form['cover_dalam_jenis']) ? 'selected' : ''; ?>>Jenis</option>
                        <?php foreach ($options['cover_dalam']['jenis'] as $jenis): ?>
                            <option value="<?= $jenis ?>" <?php echo (isset($order_form['cover_dalam_jenis']) && $order_form['cover_dalam_jenis'] === $jenis) ? 'selected' : ''; ?>><?= $jenis ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="cover_dalam_warna" id="cover_dalam_warna" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required <?php echo $options['cover_dalam']['disabled']; ?>>
                        <option value="" disabled <?php echo !isset($order_form['cover_dalam_warna']) ? 'selected' : ''; ?>>Warna</option>
                        <?php foreach ($options['cover_dalam']['warna'] as $warna): ?>
                            <option value="<?= $warna ?>" <?php echo (isset($order_form['cover_dalam_warna']) && $order_form['cover_dalam_warna'] === $warna) ? 'selected' : ''; ?>><?= $warna ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="cover_dalam_gsm" id="cover_dalam_gsm" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required <?php echo $options['cover_dalam']['disabled']; ?>>
                        <option value="" disabled <?php echo !isset($order_form['cover_dalam_gsm']) ? 'selected' : ''; ?>>GSM</option>
                        <?php foreach ($options['cover_dalam']['gsm'] as $gsm): ?>
                            <option value="<?= $gsm ?>" <?php echo (isset($order_form['cover_dalam_gsm']) && $order_form['cover_dalam_gsm'] === $gsm) ? 'selected' : ''; ?>><?= $gsm ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="cover_dalam_ukuran" id="cover_dalam_ukuran" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required <?php echo $options['cover_dalam']['disabled']; ?>>
                        <option value="" disabled <?php echo !isset($order_form['cover_dalam_ukuran']) ? 'selected' : ''; ?>>Ukuran</option>
                        <?php foreach ($options['cover_dalam']['ukuran'] as $ukuran): ?>
                            <option value="<?= $ukuran ?>" <?php echo (isset($order_form['cover_dalam_ukuran']) && $order_form['cover_dalam_ukuran'] === $ukuran) ? 'selected' : ''; ?>><?= $ukuran ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-gray-800 text-sm font-semibold mb-2">Cover Luar:</label>
                <div class="mt-2 pl-4">
                    <label class="inline-flex items-center">
                        <input type="radio" name="cover_luar_radio" value="lidah" class="form-radio h-4 w-4 text-blue-600 transition duration-150 ease-in-out" required <?php echo (isset($order_form['cover_luar_radio']) && $order_form['cover_luar_radio'] === 'lidah') ? 'checked' : ''; ?>>
                        <span class="ml-2 text-gray-800">Lidah</span>
                    </label>
                    <label class="inline-flex items-center ml-6">
                        <input type="radio" name="cover_luar_radio" value="selongsong" class="form-radio h-4 w-4 text-blue-600 transition duration-150 ease-in-out" required <?php echo (isset($order_form['cover_luar_radio']) && $order_form['cover_luar_radio'] === 'selongsong') ? 'checked' : ''; ?>>
                        <span class="ml-2 text-gray-800">Selongsong</span>
                    </label>
                    <label class="inline-flex items-center ml-6">
                        <input type="radio" name="cover_luar_radio" value="kuping" class="form-radio h-4 w-4 text-blue-600 transition duration-150 ease-in-out" required <?php echo (isset($order_form['cover_luar_radio']) && $order_form['cover_luar_radio'] === 'kuping') ? 'checked' : ''; ?>>
                        <span class="ml-2 text-gray-800">Kuping</span>
                    </label>
                    <label class="inline-flex items-center ml-6">
                        <input type="radio" name="cover_luar_radio" value="tutup_atas" class="form-radio h-4 w-4 text-blue-600 transition duration-150 ease-in-out" required <?php echo (isset($order_form['cover_luar_radio']) && $order_form['cover_luar_radio'] === 'tutup_atas') ? 'checked' : ''; ?>>
                        <span class="ml-2 text-gray-800">Tutup atas</span>
                    </label>
                </div>
                <div class="flex space-x-2 mt-2 pl-4">
                    <select name="cover_luar_supplier" id="cover_luar_supplier" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required onchange="updateKertasOptions('cover_luar')">
                        <option value="" disabled <?php echo !isset($order_form['cover_luar_supplier']) ? 'selected' : ''; ?>>Supplier</option>
                        <?php foreach ($distinct_suppliers as $supplier): ?>
                            <option value="<?= $supplier['supplier'] ?>" <?php echo (isset($order_form['cover_luar_supplier']) && $order_form['cover_luar_supplier'] === $supplier['supplier']) ? 'selected' : ''; ?>><?= $supplier['supplier'] ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="cover_luar_jenis" id="cover_luar_jenis" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required <?php echo $options['cover_luar']['disabled']; ?>>
                        <option value="" disabled <?php echo !isset($order_form['cover_luar_jenis']) ? 'selected' : ''; ?>>Jenis</option>
                        <?php foreach ($options['cover_luar']['jenis'] as $jenis): ?>
                            <option value="<?= $jenis ?>" <?php echo (isset($order_form['cover_luar_jenis']) && $order_form['cover_luar_jenis'] === $jenis) ? 'selected' : ''; ?>><?= $jenis ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="cover_luar_warna" id="cover_luar_warna" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required <?php echo $options['cover_luar']['disabled']; ?>>
                        <option value="" disabled <?php echo !isset($order_form['cover_luar_warna']) ? 'selected' : ''; ?>>Warna</option>
                        <?php foreach ($options['cover_luar']['warna'] as $warna): ?>
                            <option value="<?= $warna ?>" <?php echo (isset($order_form['cover_luar_warna']) && $order_form['cover_luar_warna'] === $warna) ? 'selected' : ''; ?>><?= $warna ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="cover_luar_gsm" id="cover_luar_gsm" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required <?php echo $options['cover_luar']['disabled']; ?>>
                        <option value="" disabled <?php echo !isset($order_form['cover_luar_gsm']) ? 'selected' : ''; ?>>GSM</option>
                        <?php foreach ($options['cover_luar']['gsm'] as $gsm): ?>
                            <option value="<?= $gsm ?>" <?php echo (isset($order_form['cover_luar_gsm']) && $order_form['cover_luar_gsm'] === $gsm) ? 'selected' : ''; ?>><?= $gsm ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="cover_luar_ukuran" id="cover_luar_ukuran" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required <?php echo $options['cover_luar']['disabled']; ?>>
                        <option value="" disabled <?php echo !isset($order_form['cover_luar_ukuran']) ? 'selected' : ''; ?>>Ukuran</option>
                        <?php foreach ($options['cover_luar']['ukuran'] as $ukuran): ?>
                            <option value="<?= $ukuran ?>" <?php echo (isset($order_form['cover_luar_ukuran']) && $order_form['cover_luar_ukuran'] === $ukuran) ? 'selected' : ''; ?>><?= $ukuran ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-gray-800 text-sm font-semibold mb-2 flex space-x-2 pl-4">Box</label>
                <div class="flex space-x-2 pl-4">
                    <select name="box_supplier" id="box_supplier" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required onchange="updateKertasOptions('box')">
                        <option value="" disabled <?php echo !isset($order_form['box_supplier']) ? 'selected' : ''; ?>>Supplier</option>
                        <?php foreach ($distinct_suppliers as $supplier): ?>
                            <option value="<?= $supplier['supplier'] ?>" <?php echo (isset($order_form['box_supplier']) && $order_form['box_supplier'] === $supplier['supplier']) ? 'selected' : ''; ?>><?= $supplier['supplier'] ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="box_jenis" id="box_jenis" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required <?php echo $options['box']['disabled']; ?>>
                        <option value="" disabled <?php echo !isset($order_form['box_jenis']) ? 'selected' : ''; ?>>Jenis</option>
                        <?php foreach ($options['box']['jenis'] as $jenis): ?>
                            <option value="<?= $jenis ?>" <?php echo (isset($order_form['box_jenis']) && $order_form['box_jenis'] === $jenis) ? 'selected' : ''; ?>><?= $jenis ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="box_warna" id="box_warna" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required <?php echo $options['box']['disabled']; ?>>
                        <option value="" disabled <?php echo !isset($order_form['box_warna']) ? 'selected' : ''; ?>>Warna</option>
                        <?php foreach ($options['box']['warna'] as $warna): ?>
                            <option value="<?= $warna ?>" <?php echo (isset($order_form['box_warna']) && $order_form['box_warna'] === $warna) ? 'selected' : ''; ?>><?= $warna ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="box_gsm" id="box_gsm" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required <?php echo $options['box']['disabled']; ?>>
                        <option value="" disabled <?php echo !isset($order_form['box_gsm']) ? 'selected' : ''; ?>>GSM</option>
                        <?php foreach ($options['box']['gsm'] as $gsm): ?>
                            <option value="<?= $gsm ?>" <?php echo (isset($order_form['box_gsm']) && $order_form['box_gsm'] === $gsm) ? 'selected' : ''; ?>><?= $gsm ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="box_ukuran" id="box_ukuran" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required <?php echo $options['box']['disabled']; ?>>
                        <option value="" disabled <?php echo !isset($order_form['box_ukuran']) ? 'selected' : ''; ?>>Ukuran</option>
                        <?php foreach ($options['box']['ukuran'] as $ukuran): ?>
                            <option value="<?= $ukuran ?>" <?php echo (isset($order_form['box_ukuran']) && $order_form['box_ukuran'] === $ukuran) ? 'selected' : ''; ?>><?= $ukuran ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="mb-4">
                <label class="block text-gray-800 text-sm font-semibold mb-2 flex space-x-2 pl-4">Dudukan</label>
                <div class="flex space-x-2 pl-4">
                    <select name="dudukan_supplier" id="dudukan_supplier" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required onchange="updateKertasOptions('dudukan')">
                        <option value="" disabled <?php echo !isset($order_form['dudukan_supplier']) ? 'selected' : ''; ?>>Supplier</option>
                        <?php foreach ($distinct_suppliers as $supplier): ?>
                            <option value="<?= $supplier['supplier'] ?>" <?php echo (isset($order_form['dudukan_supplier']) && $order_form['dudukan_supplier'] === $supplier['supplier']) ? 'selected' : ''; ?>><?= $supplier['supplier'] ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="dudukan_jenis" id="dudukan_jenis" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required <?php echo $options['dudukan']['disabled']; ?>>
                        <option value="" disabled <?php echo !isset($order_form['dudukan_jenis']) ? 'selected' : ''; ?>>Jenis</option>
                        <?php foreach ($options['dudukan']['jenis'] as $jenis): ?>
                            <option value="<?= $jenis ?>" <?php echo (isset($order_form['dudukan_jenis']) && $order_form['dudukan_jenis'] === $jenis) ? 'selected' : ''; ?>><?= $jenis ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="dudukan_warna" id="dudukan_warna" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required <?php echo $options['dudukan']['disabled']; ?>>
                        <option value="" disabled <?php echo !isset($order_form['dudukan_warna']) ? 'selected' : ''; ?>>Warna</option>
                        <?php foreach ($options['dudukan']['warna'] as $warna): ?>
                            <option value="<?= $warna ?>" <?php echo (isset($order_form['dudukan_warna']) && $order_form['dudukan_warna'] === $warna) ? 'selected' : ''; ?>><?= $warna ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="dudukan_gsm" id="dudukan_gsm" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required <?php echo $options['dudukan']['disabled']; ?>>
                        <option value="" disabled <?php echo !isset($order_form['dudukan_gsm']) ? 'selected' : ''; ?>>GSM</option>
                        <?php foreach ($options['dudukan']['gsm'] as $gsm): ?>
                            <option value="<?= $gsm ?>" <?php echo (isset($order_form['dudukan_gsm']) && $order_form['dudukan_gsm'] === $gsm) ? 'selected' : ''; ?>><?= $gsm ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="dudukan_ukuran" id="dudukan_ukuran" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required <?php echo $options['dudukan']['disabled']; ?>>
                        <option value="" disabled <?php echo !isset($order_form['dudukan_ukuran']) ? 'selected' : ''; ?>>Ukuran</option>
                        <?php foreach ($options['dudukan']['ukuran'] as $ukuran): ?>
                            <option value="<?= $ukuran ?>" <?php echo (isset($order_form['dudukan_ukuran']) && $order_form['dudukan_ukuran'] === $ukuran) ? 'selected' : ''; ?>><?= $ukuran ?></option>
                        <?php endforeach; ?>
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
                    <option value="" disabled <?php echo !isset($order_form['aksesoris_jenis']) ? 'selected' : ''; ?>>Pilih Jenis</option>
                    <?php foreach ($aksesoris_jenis as $jenis): ?>
                        <option value="<?= $jenis['jenis'] ?>" <?php echo (isset($order_form['aksesoris_jenis']) && $order_form['aksesoris_jenis'] === $jenis['jenis']) ? 'selected' : ''; ?>><?= $jenis['jenis'] ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="aksesoris_ukuran" id="aksesoris_ukuran" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out">
                    <option value="" disabled <?php echo !isset($order_form['aksesoris_ukuran']) ? 'selected' : ''; ?>>Pilih Ukuran</option>
                    <?php foreach ($aksesoris_ukuran as $ukuran): ?>
                        <option value="<?= $ukuran['ukuran'] ?>" <?php echo (isset($order_form['aksesoris_ukuran']) && $order_form['aksesoris_ukuran'] === $ukuran['ukuran']) ? 'selected' : ''; ?>><?= $ukuran['ukuran'] ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="aksesoris_warna" id="aksesoris_warna" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out">
                    <option value="" disabled <?php echo !isset($order_form['aksesoris_warna']) ? 'selected' : ''; ?>>Pilih Warna</option>
                    <?php foreach ($aksesoris_warna as $warna): ?>
                        <option value="<?= $warna['warna'] ?>" <?php echo (isset($order_form['aksesoris_warna']) && $order_form['aksesoris_warna'] === $warna['warna']) ? 'selected' : ''; ?>><?= $warna['warna'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="mb-4">
            <div class="flex space-x-4">
                <div class="w-1/2">
                    <label for="dudukan" class="block text-gray-800 text-sm font-semibold mb-2">Dudukan:</label>
                    <select name="dudukan" id="dudukan" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out">
                        <option value="" disabled <?php echo !isset($order_form['dudukan']) ? 'selected' : ''; ?>>Pilih Dudukan</option>
                        <?php foreach ($dudukan_options as $dudukan): ?>
                            <option value="<?= $dudukan['id'] ?>" <?php echo (isset($order_form['dudukan']) && $order_form['dudukan'] == $dudukan['id']) ? 'selected' : ''; ?>><?= $dudukan['jenis'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="w-1/2">
                    <label for="jumlah_layer" class="block text-gray-800 text-sm font-semibold mb-2">Jumlah layer</label>
                    <input type="number" name="jumlah_layer" id="jumlah_layer" value="<?php echo htmlspecialchars($order_form['jumlah_layer'] ?? ''); ?>" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" min="0" step="1">
                </div>
            </div>
        </div>

        <div class="mb-4">
            <div class="flex space-x-4">
                <div class="w-1/2">
                    <label for="logo" class="block text-gray-800 text-sm font-semibold mb-2">Logo:</label>
                    <select name="logo" id="logo" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out">
                        <option value="" disabled <?php echo !isset($order_form['logo']) ? 'selected' : ''; ?>>Pilih Logo</option>
                        <?php foreach ($logo_options as $logo): ?>
                            <option value="<?= $logo['jenis'] ?>" <?php echo (isset($order_form['logo']) && $order_form['logo'] === $logo['jenis']) ? 'selected' : ''; ?>><?= $logo['jenis'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="w-1/2">
                    <label for="ukuran_poly" class="block text-gray-800 text-sm font-semibold mb-2">Ukuran Poly:</label>
                    <select name="ukuran_poly" id="ukuran_poly" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out">
                        <option value="" disabled <?php echo !isset($order_form['ukuran_poly']) ? 'selected' : ''; ?>>Pilih Ukuran Poly</option>
                        <?php foreach ($logo_uk_poly_options as $uk_poly): ?>
                            <option value="<?= $uk_poly['uk_poly'] ?>" <?php echo (isset($order_form['ukuran_poly']) && $order_form['ukuran_poly'] === $uk_poly['uk_poly']) ? 'selected' : ''; ?>><?= $uk_poly['uk_poly'] ?></option>
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
                            <input type="radio" name="lokasi_poly" value="Pabrik" class="form-radio h-4 w-4 text-blue-600 transition duration-150 ease-in-out" required <?php echo (isset($order_form['lokasi_poly']) && $order_form['lokasi_poly'] === 'Pabrik') ? 'checked' : ''; ?>>
                            <span class="ml-2 text-gray-800">Pabrik</span>
                        </label>
                        <label class="inline-flex items-center ml-6">
                            <input type="radio" name="lokasi_poly" value="Luar" class="form-radio h-4 w-4 text-blue-600 transition duration-150 ease-in-out" required <?php echo (isset($order_form['lokasi_poly']) && $order_form['lokasi_poly'] === 'Luar') ? 'checked' : ''; ?>>
                            <span class="ml-2 text-gray-800">Luar</span>
                        </label>
                    </div>
                </div>
                <div class="w-1/2">
                    <label class="block text-gray-800 text-sm font-semibold mb-2">Klise:</label>
                    <div class="mt-2">
                        <label class="inline-flex items-center">
                            <input type="radio" name="klise" value="In Stock" class="form-radio h-4 w-4 text-blue-600 transition duration-150 ease-in-out" required <?php echo (isset($order_form['klise']) && $order_form['klise'] === 'In Stock') ? 'checked' : ''; ?>>
                            <span class="ml-2 text-gray-800">In Stock</span>
                        </label>
                        <label class="inline-flex items-center ml-6">
                            <input type="radio" name="klise" value="Bikin baru" class="form-radio h-4 w-4 text-blue-600 transition duration-150 ease-in-out" required <?php echo (isset($order_form['klise']) && $order_form['klise'] === 'Bikin baru') ? 'checked' : ''; ?>>
                            <span class="ml-2 text-gray-800">Bikin baru</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <div class="mb-4">
            <label for="keterangan" class="block text-gray-800 text-sm font-semibold mb-2">Keterangan:</label>
            <textarea name="keterangan" id="keterangan" rows="3" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out"><?php echo htmlspecialchars($order_form['keterangan'] ?? ''); ?></textarea>
        </div>

        <div class="mb-4">
            <label for="dibuat_oleh" class="block text-gray-800 text-sm font-semibold mb-2">Dibuat Oleh:</label>
            <select name="dibuat_oleh" id="dibuat_oleh" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out">
                <option value="" disabled <?php echo !isset($order_form['dibuat_oleh']) ? 'selected' : ''; ?>>Pilih Karyawan Sales</option>
                <?php foreach ($sales_reps as $sales_rep): ?>
                    <option value="<?= $sales_rep['nama'] ?>" <?php echo (isset($order_form['dibuat_oleh']) && $order_form['dibuat_oleh'] === $sales_rep['nama']) ? 'selected' : ''; ?>><?= $sales_rep['nama'] ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-4">
            <label class="block text-gray-800 text-sm font-semibold mb-2">Lokasi:</label>
            <div class="mt-2">
                <label class="inline-flex items-center">
                    <input type="radio" name="lokasi" value="BSD" class="form-radio h-4 w-4 text-blue-600 transition duration-150 ease-in-out" required <?php echo (isset($order_form['lokasi']) && $order_form['lokasi'] === 'BSD') ? 'checked' : ''; ?>>
                    <span class="ml-2 text-gray-800">BSD</span>
                </label>
                <label class="inline-flex items-center ml-6">
                    <input type="radio" name="lokasi" value="Pondok Aren" class="form-radio h-4 w-4 text-blue-600 transition duration-150 ease-in-out" required <?php echo (isset($order_form['lokasi']) && $order_form['lokasi'] === 'Pondok Aren') ? 'checked' : ''; ?>>
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

        document.addEventListener('DOMContentLoaded', function() {
            const kodePisauValue = "<?php echo addslashes($order_form['kode_pisau'] ?? ''); ?>";
            if (kodePisauValue) {
                handleKodePisauChange(kodePisauValue);
            }
        });
    </script>
    </body>
</html>