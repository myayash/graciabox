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

    // Fetch data for dropdowns
    $customers = $pdo->query("SELECT * FROM customer WHERE is_archived = 0")->fetchAll(PDO::FETCH_ASSOC);
    $model_boxes_data = $pdo->query("SELECT * FROM model_box WHERE is_archived = 0")->fetchAll(PDO::FETCH_ASSOC);
    $boards = $pdo->query("SELECT * FROM board WHERE is_archived = 0")->fetchAll(PDO::FETCH_ASSOC);
    $papers = $pdo->query("SELECT * FROM kertas WHERE is_archived = 0")->fetchAll(PDO::FETCH_ASSOC);
    $sales_reps = $pdo->query("SELECT * FROM empl_sales WHERE is_archived = 0")->fetchAll(PDO::FETCH_ASSOC);
    $barangs = $pdo->query("SELECT * FROM barang WHERE is_archived = 0")->fetchAll(PDO::FETCH_ASSOC);

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
        $cover_dlm = trim($_POST['cover_dlm']);
        $sales_pj = trim($_POST['sales_pj']);

        $ukuran = '';
        $model_box = '';
        $nama_box_lama_value = NULL;

        if ($kode_pisau === 'baru') {
            $ukuran = trim($_POST['ukuran']);
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
                $stmt = $pdo->prepare("UPDATE orders SET nama = ?, kode_pisau = ?, ukuran = ?, model_box = ?, jenis_board = ?, cover_dlm = ?, sales_pj = ?, nama_box_lama = ? WHERE id = ?");
                $stmt->execute([$nama, $kode_pisau, $ukuran, $model_box, $jenis_board, $cover_dlm, $sales_pj, $nama_box_lama_value, $order['id']]);
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

            <div id="kode_pisau_baru_fields" class="mb-4" style="display:none;">
                <div class="mb-4">
                    <label for="ukuran" class="block text-gray-800 text-sm font-semibold mb-2">Ukuran:</label>
                    <input type="text" id="ukuran" name="ukuran" value="<?php echo htmlspecialchars($order['ukuran']); ?>" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required>
                </div>

                <div class="mb-4">
                    <label for="model_box" class="block text-gray-800 text-sm font-semibold mb-2">Model Box:</label>
                    <select id="model_box" name="model_box" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required>
                        <option value="">Pilih Model Box</option>
                        <?php foreach ($model_boxes_data as $model_box_item): ?>
                            <option value="<?= htmlspecialchars($model_box_item['nama']) ?>" <?= ($order['model_box'] == $model_box_item['nama']) ? 'selected' : '' ?>><?= htmlspecialchars($model_box_item['nama']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
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
                <label for="cover_dlm" class="block text-gray-800 text-sm font-semibold mb-2">Cover Dlm:</label>
                <select id="cover_dlm" name="cover_dlm" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out">
                    <?php foreach ($papers as $paper): ?>
                        <?php
                        $display_text = '';
                        foreach ($paper as $key => $value) {
                            if ($key != 'id') {
                                $display_text .= $key . ': ' . $value . ', ';
                            }
                        }
                        $display_text = rtrim($display_text, ', ');
                        ?>
                        <option value="<?= htmlspecialchars($paper['jenis']) ?>" <?= ($order['cover_dlm'] == $paper['jenis']) ? 'selected' : '' ?>><?= htmlspecialchars($display_text) ?></option>
                    <?php endforeach; ?>
                </select>
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

        // Initial call to set visibility based on current order data
        document.addEventListener('DOMContentLoaded', function() {
            const kodePisauRadios = document.querySelectorAll('input[name="kode_pisau"]');
            kodePisauRadios.forEach(radio => {
                if (radio.checked) {
                    handleKodePisauChange(radio.value);
                }
            });
        });
    </script>

</body>
</html>
