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
    if (empty($_POST['nama_customer']) || empty($_POST['kode_pisau']) || empty($_POST['jenis_board']) || empty($_POST['cover_dalam'])) {
        die('Error: Nama Customer, Kode Pisau, Jenis Board, and Cover Dalam are required fields.');
    }

    if (isset($_POST['kode_pisau'])) {
        if ($_POST['kode_pisau'] === 'baru') {
            if (empty($_POST['model_box_baru']) || empty($_POST['ukuran_baru']) || empty($_POST['dibuat_oleh'])) {
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
        $cover_dlm = $_POST['cover_dalam'];
        $sales_pj = $_POST['dibuat_oleh'];
        $nama_box_lama_value = NULL;

        if ($kode_pisau === 'baru') {
            $ukuran = $_POST['ukuran_baru'];
            $model_box = $_POST['model_box_baru'];
            $nama_box = $_POST['nama_box_baru'];

            $stmt = $pdo->prepare("INSERT INTO barang (model_box, ukuran, nama) VALUES (?, ?, ?)");
            $stmt->execute([$model_box, $ukuran, $nama_box]);
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

        $stmt = $pdo->prepare("INSERT INTO orders (nama, kode_pisau, ukuran, model_box, jenis_board, cover_dlm, sales_pj, nama_box_lama) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$nama, $kode_pisau, $ukuran, $model_box, $jenis_board, $cover_dlm, $sales_pj, $nama_box_lama_value]);

        header("Location: index.php");
        exit;
    }
}

// Fetch data for dropdowns
$customers = $pdo->query("SELECT * FROM customer WHERE is_archived = 0")->fetchAll(PDO::FETCH_ASSOC);
$model_boxes = $pdo->query("SELECT * FROM model_box WHERE is_archived = 0")->fetchAll(PDO::FETCH_ASSOC);
$boards = $pdo->query("SELECT * FROM board WHERE is_archived = 0")->fetchAll(PDO::FETCH_ASSOC);
$papers = $pdo->query("SELECT * FROM kertas WHERE is_archived = 0")->fetchAll(PDO::FETCH_ASSOC);
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

            <div class="mb-4">
                <label for="ukuran_baru" class="block text-gray-800 text-sm font-semibold mb-2">Ukuran:</label>
                <input type="text" name="ukuran_baru" id="ukuran_baru" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out">
            </div>

            <div class="mb-4">
                <label for="nama_box_baru" class="block text-gray-800 text-sm font-semibold mb-2">Nama Box:</label>
                <input type="text" name="nama_box_baru" id="nama_box_baru" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out">
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
                <label for="cover_dalam" class="block text-gray-800 text-sm font-semibold mb-2">Cover Dalam:</label>
                <select name="cover_dalam" id="cover_dalam" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required>
                    <option value="" disabled selected>Pilih Cover Dalam</option>
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
                        <option value="<?= $paper['jenis'] ?>"><?= $display_text ?></option>
                    <?php endforeach; ?>
                </select>
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
            const ukuranBaru = document.getElementById('ukuran_baru');
            const dibuatOleh = document.getElementById('dibuat_oleh');
            const barangLama = document.getElementById('barang_lama');

            if (value === 'baru') {
                document.getElementById('kode_pisau_baru_fields').style.display = 'block';
                document.getElementById('kode_pisau_lama_fields').style.display = 'none';
                document.getElementById('shared_fields').style.display = 'block';
                modelBoxBaru.required = true;
                ukuranBaru.required = true;
                dibuatOleh.required = true;
                barangLama.required = false;
            } else if (value === 'lama') {
                document.getElementById('kode_pisau_baru_fields').style.display = 'none';
                document.getElementById('kode_pisau_lama_fields').style.display = 'block';
                document.getElementById('shared_fields').style.display = 'block';
                modelBoxBaru.required = false;
                ukuranBaru.required = false;
                dibuatOleh.required = false;
                barangLama.required = true;
                dibuatOleh.required = true;
            }
        }
    </script>

</body>
</html>