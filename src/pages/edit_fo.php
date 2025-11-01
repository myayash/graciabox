<?php
// Check if the user is logged in at all.
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/login');
    exit();
}

// Check if the user has the 'admin' role.
if ($_SESSION['role'] !== 'admin') {
    die('Access Denied: You do not have permission to edit orders.');
}

// Check if ID is set
if (!isset($_GET['id'])) {
    die('Error: Order ID not specified.');
}

$order_id = $_GET['id'];

// Fetch the existing order
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$order_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    die('Error: Order not found.');
}

// Prepare order data for form pre-filling
$order_form = [
    'id' => $order['id'],
    'nama_customer' => $order['nama'],
    'kode_pisau' => $order['kode_pisau'],
    'quantity' => str_replace(' pcs', '', $order['quantity']),
    'model_box_baru' => $order['model_box'],
    'jenis_board' => $order['jenis_board'],
    'keterangan' => $order['keterangan'],
    'dibuat_oleh' => $order['sales_pj'],
    'lokasi' => $order['lokasi'],
    'jumlah_layer' => $order['jumlah_layer'],
    'logo' => $order['logo'],
    'ukuran_poly' => $order['ukuran_poly'],
    'lokasi_poly' => $order['lokasi_poly'],
    'klise' => $order['klise'],
    'barang_lama' => null, // Will be set if kode_pisau is 'lama'
    'dudukan' => null, // Will be set if dudukan is present
    'length' => '',
    'width' => '',
    'height' => '',
];

// Parse Ukuran (Length, Width, Height) based on kode_pisau
if ($order_form['kode_pisau'] === 'baru') {
    if (!empty($order['ukuran'])) {
        $ukuran_parts = explode(' x ', $order['ukuran']);
        $order_form['length'] = $ukuran_parts[0] ?? '';
        $order_form['width'] = $ukuran_parts[1] ?? '';
        $order_form['height'] = $ukuran_parts[2] ?? '';
    }
} else if ($order_form['kode_pisau'] === 'lama') {
    // If kode_pisau is 'lama', we need to get the barang_id first
    // and then fetch the ukuran from the barang table.
    // This assumes nama_box_lama in orders table corresponds to nama in barang table
    // and model_box in orders table corresponds to model_box in barang table
    // and ukuran in orders table corresponds to ukuran in barang table
    $stmt_barang_id = $pdo->prepare("SELECT id, ukuran FROM barang WHERE nama = ? AND model_box = ? AND ukuran = ?");
    $stmt_barang_id->execute([$order['nama_box_lama'], $order['model_box'], $order['ukuran']]);
    $barang_data = $stmt_barang_id->fetch(PDO::FETCH_ASSOC);

    if ($barang_data) {
        $order_form['barang_lama'] = $barang_data['id'];
        if (!empty($barang_data['ukuran'])) {
            $ukuran_parts = explode(' x ', $barang_data['ukuran']);
            $order_form['length'] = $ukuran_parts[0] ?? '';
            $order_form['width'] = $ukuran_parts[1] ?? '';
            $order_form['height'] = $ukuran_parts[2] ?? '';
        }
    }
}

// Parse Cover Dalam
if (!empty($order['cover_dlm'])) {
    preg_match('/supplier:(.*?) - jenis:(.*?) - warna:(.*?) - gsm:(.*?) - ukuran:(.*)/', $order['cover_dlm'], $matches);
    if (count($matches) === 6) {
        $order_form['cover_dalam_supplier'] = trim($matches[1]);
        $order_form['cover_dalam_jenis'] = trim($matches[2]);
        $order_form['cover_dalam_warna'] = trim($matches[3]);
        $order_form['cover_dalam_gsm'] = trim($matches[4]);
        $order_form['cover_dalam_ukuran'] = trim($matches[5]);
    }
}

// Parse Cover Luar, Box, Dudukan from cover_lr
if (!empty($order['cover_lr'])) {
    $cover_lr_lines = explode("\n", $order['cover_lr']);

    // Cover Luar
    if (isset($cover_lr_lines[0])) {
        preg_match('/\((.*?)\) (.*?) - (.*?) - (.*?) - (.*?) gsm - (.*)/', $cover_lr_lines[0], $matches);
        if (count($matches) === 7) {
            $order_form['cover_luar_radio'] = trim($matches[1]);
            $order_form['cover_luar_supplier'] = trim($matches[2]);
            $order_form['cover_luar_jenis'] = trim($matches[3]);
            $order_form['cover_luar_warna'] = trim($matches[4]);
            $order_form['cover_luar_gsm'] = trim($matches[5]);
            $order_form['cover_luar_ukuran'] = trim($matches[6]);
        }
    }

    // Box
    if (isset($cover_lr_lines[1])) {
        preg_match('/\(box\) (.*?) - (.*?) - (.*?) - (.*?) gsm - (.*)/', $cover_lr_lines[1], $matches);
        if (count($matches) === 6) {
            $order_form['box_supplier'] = trim($matches[1]);
            $order_form['box_jenis'] = trim($matches[2]);
            $order_form['box_warna'] = trim($matches[3]);
            $order_form['box_gsm'] = trim($matches[4]);
            $order_form['box_ukuran'] = trim($matches[5]);
        }
    }

    // Dudukan
    if (isset($cover_lr_lines[2])) {
        preg_match('/\(dudukan\) (.*?) - (.*?) - (.*?) - (.*?) gsm - (.*)/', $cover_lr_lines[2], $matches);
        if (count($matches) === 6) {
            $order_form['dudukan_supplier'] = trim($matches[1]);
            $order_form['dudukan_jenis'] = trim($matches[2]);
            $order_form['dudukan_warna'] = trim($matches[3]);
            $order_form['dudukan_gsm'] = trim($matches[4]);
            $order_form['dudukan_ukuran'] = trim($matches[5]);
        }
    }
}

// Parse Aksesoris
if (!empty($order['aksesoris'])) {
    preg_match('/jenis:(.*?) - ukuran:(.*?) - warna:(.*)/', $order['aksesoris'], $matches);
    if (count($matches) === 4) {
        $order_form['aksesoris_jenis'] = trim($matches[1]);
        $order_form['aksesoris_ukuran'] = trim($matches[2]);
        $order_form['aksesoris_warna'] = trim($matches[3]);
    }
}

// If kode_pisau is 'lama', find the corresponding barang_id
if ($order_form['kode_pisau'] === 'lama' && !empty($order['nama_box_lama'])) {
    $stmt = $pdo->prepare("SELECT id FROM barang WHERE nama = ? AND model_box = ? AND ukuran = ?");
    $stmt->execute([$order['nama_box_lama'], $order['model_box'], $order['ukuran']]);
    $barang_id_row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($barang_id_row) {
        $order_form['barang_lama'] = $barang_id_row['id'];
    }
}

// If dudukan is present, find its ID
if (!empty($order['dudukan'])) {
    $stmt = $pdo->prepare("SELECT id FROM dudukan WHERE jenis = ?");
    $stmt->execute([$order['dudukan']]);
    $dudukan_id_row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($dudukan_id_row) {
        $order_form['dudukan'] = $dudukan_id_row['id'];
    }
}

// Fetch data for dropdowns
$customers = $pdo->query("SELECT * FROM customer WHERE is_archived = 0")->fetchAll(PDO::FETCH_ASSOC);
$model_boxes = $pdo->query("SELECT * FROM model_box WHERE is_archived = 0")->fetchAll(PDO::FETCH_ASSOC);
$boards = $pdo->query("SELECT * FROM board WHERE is_archived = 0")->fetchAll(PDO::FETCH_ASSOC);
$distinct_suppliers = $pdo->query("SELECT DISTINCT supplier FROM kertas WHERE is_archived = 0")->fetchAll(PDO::FETCH_ASSOC);
$sales_reps = $pdo->query("SELECT * FROM empl_sales WHERE is_archived = 0")->fetchAll(PDO::FETCH_ASSOC);
$barangs = $pdo->query("SELECT * FROM barang WHERE is_archived = 0")->fetchAll(PDO::FETCH_ASSOC);
$aksesoris_jenis_options = $pdo->query("SELECT DISTINCT jenis FROM aksesoris")->fetchAll(PDO::FETCH_ASSOC);
$aksesoris_ukuran_options = $pdo->query("SELECT DISTINCT ukuran FROM aksesoris")->fetchAll(PDO::FETCH_ASSOC);
$aksesoris_warna_options = $pdo->query("SELECT DISTINCT warna FROM aksesoris")->fetchAll(PDO::FETCH_ASSOC);
$dudukan_options = $pdo->query("SELECT * FROM dudukan")->fetchAll(PDO::FETCH_ASSOC);
$logo_options = $pdo->query("SELECT DISTINCT jenis FROM logo")->fetchAll(PDO::FETCH_ASSOC);
$logo_uk_poly_options = $pdo->query("SELECT DISTINCT uk_poly FROM logo")->fetchAll(PDO::FETCH_ASSOC);
$alamat_pengirim = $pdo->query("SELECT * FROM alamat_pengirim")->fetchAll(PDO::FETCH_ASSOC);

// Prepare options for each prefix based on fetched data
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
    $current_supplier = $order_form[$prefix . '_supplier'] ?? '';
    if (!empty($current_supplier)) {
        $base_params = [$current_supplier];

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

<?php
$pageTitle = 'Edit Form Order';
ob_start();
?>

<h1 class="text-2xl font-bold mb-6 text-gray-800">Edit Form Order</h1>

<form action="<?php echo BASE_URL; ?>/update_fo" method="post" class="bg-white p-8 shadow-lg">
    <input type="hidden" name="id" value="<?= $order_id ?>">
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
                <?php foreach ($aksesoris_jenis_options as $jenis): ?>
                    <option value="<?= $jenis['jenis'] ?>" <?php echo (isset($order_form['aksesoris_jenis']) && $order_form['aksesoris_jenis'] === $jenis['jenis']) ? 'selected' : ''; ?>><?= $jenis['jenis'] ?></option>
                <?php endforeach; ?>
            </select>
            <select name="aksesoris_ukuran" id="aksesoris_ukuran" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out">
                <option value="" disabled <?php echo !isset($order_form['aksesoris_ukuran']) ? 'selected' : ''; ?>>Pilih Ukuran</option>
                <?php foreach ($aksesoris_ukuran_options as $ukuran): ?>
                    <option value="<?= $ukuran['ukuran'] ?>" <?php echo (isset($order_form['aksesoris_ukuran']) && $order_form['aksesoris_ukuran'] === $ukuran['ukuran']) ? 'selected' : ''; ?>><?= $ukuran['ukuran'] ?></option>
                <?php endforeach; ?>
            </select>
            <select name="aksesoris_warna" id="aksesoris_warna" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out">
                <option value="" disabled <?php echo !isset($order_form['aksesoris_warna']) ? 'selected' : ''; ?>>Pilih Warna</option>
                <?php foreach ($aksesoris_warna_options as $warna): ?>
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

    <h2 class="text-xl font-bold mb-4 text-gray-400">SHIPPING DETAILS</h2>
    <div class="border-b-2 border-gray-300 mb-6"></div>

    <div class="mb-4">
        <label for="tanggal_kirim" class="block text-gray-800 text-sm font-semibold mb-2">Tanggal Kirim:</label>
        <input type="date" name="tanggal_kirim" id="tanggal_kirim" value="<?php echo htmlspecialchars($order['tanggal_kirim'] ?? ''); ?>" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required>
    </div>

    <div class="mb-4">
        <label for="jam_kirim" class="block text-gray-800 text-sm font-semibold mb-2">Jam Kirim:</label>
        <input type="time" name="jam_kirim" id="jam_kirim" value="<?php echo htmlspecialchars($order['jam_kirim'] ?? ''); ?>" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required>
    </div>

    <div class="mb-4">
        <label for="dikirim_dari" class="block text-gray-800 text-sm font-semibold mb-2">Dikirim Dari:</label>
        <select name="dikirim_dari" id="dikirim_dari" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required>
            <option value="" disabled <?php echo !isset($order['dikirim_dari']) ? 'selected' : ''; ?>>Pilih Lokasi</option>
            <?php foreach ($alamat_pengirim as $alamat): ?>
                <option value="<?= $alamat['lokasi'] ?>" <?php echo (isset($order['dikirim_dari']) && $order['dikirim_dari'] === $alamat['lokasi']) ? 'selected' : ''; ?>><?= $alamat['lokasi'] ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="mb-4">
        <label for="tujuan_kirim" class="block text-gray-800 text-sm font-semibold mb-2">Tujuan Kirim:</label>
        <input type="text" name="tujuan_kirim" id="tujuan_kirim" value="<?php echo htmlspecialchars($order['tujuan_kirim'] ?? ''); ?>" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required>
    </div>

    <div class="flex items-center justify-start space-x-4">
        <input type="submit" value="Update" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out">
        <a href="<?php echo BASE_URL; ?>/daftar_fo" class="inline-block align-baseline font-semibold text-sm text-blue-600 hover:text-blue-800">
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

        // Call updateKertasOptions for each prefix to populate dependent dropdowns
        const prefixes = ['cover_dalam', 'cover_luar', 'box', 'dudukan'];
        prefixes.forEach(prefix => {
            const supplierSelect = document.getElementById(prefix + '_supplier');
            if (supplierSelect && supplierSelect.value) {
                updateKertasOptions(prefix);
            }
        });
    });
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../Views/partials/base.php';
?>
