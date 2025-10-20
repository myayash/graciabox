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
    // Store or update the form data in the session (from bikin_fo.php)
    $_SESSION['order_form'] = array_merge($_SESSION['order_form'] ?? [], $_POST);
}

$order_form = $_SESSION['order_form'] ?? [];

// Fetch data for dropdowns
$alamat_pengirim = $pdo->query("SELECT * FROM alamat_pengirim")->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>bikin form order - pengiriman</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-900 pt-24 px-8 pb-8 font-mono">

    <?php include 'navbar.php'; ?>
    <h1 class="text-2xl font-bold mb-6 text-gray-800">bikin form order</h1>

    <form action="process_order.php" method="post" class="bg-white p-8 shadow-lg">
        <input type="hidden" name="from_shipping" value="1">
        <h2 class="text-xl font-bold mb-4 text-gray-400">PENGIRIMAN</h2>
        <div class="border-b-2 border-gray-300 mb-6"></div>

        <div class="mb-4">
            <label for="tanggal_kirim" class="block text-gray-800 text-sm font-semibold mb-2">Tanggal Kirim:</label>
            <input type="date" name="tanggal_kirim" id="tanggal_kirim" value="<?php echo htmlspecialchars($order_form['tanggal_kirim'] ?? ''); ?>" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required>
        </div>

        <div class="mb-4">
            <label for="jam_kirim" class="block text-gray-800 text-sm font-semibold mb-2">Jam Kirim:</label>
            <input type="time" name="jam_kirim" id="jam_kirim" value="<?php echo htmlspecialchars($order_form['jam_kirim'] ?? ''); ?>" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required>
        </div>

        <div class="mb-4">
            <label for="dikirim_dari" class="block text-gray-800 text-sm font-semibold mb-2">Dikirim Dari:</label>
            <select name="dikirim_dari" id="dikirim_dari" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required>
                <option value="" disabled <?php echo !isset($order_form['dikirim_dari']) ? 'selected' : ''; ?>>Pilih Lokasi</option>
                <?php foreach ($alamat_pengirim as $alamat): ?>
                    <option value="<?= $alamat['lokasi'] ?>" <?php echo (isset($order_form['dikirim_dari']) && $order_form['dikirim_dari'] === $alamat['lokasi']) ? 'selected' : ''; ?>><?= $alamat['lokasi'] ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-4">
            <label for="tujuan_kirim" class="block text-gray-800 text-sm font-semibold mb-2">Tujuan Kirim:</label>
            <input type="text" name="tujuan_kirim" id="tujuan_kirim" value="<?php echo htmlspecialchars($order_form['tujuan_kirim'] ?? ''); ?>" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required>
        </div>

        <h2 class="text-xl font-bold mb-4 text-gray-400">PEMBAYARAN</h2>
        <div class="border-b-2 border-gray-300 mb-6"></div>
        <div class="mb-4">
            <label for="tanggal_dp" class="block text-gray-800 text-sm font-semibold mb-2">Tanggal DP:</label>
            <input type="date" name="tanggal_dp" id="tanggal_dp" value="<?php echo htmlspecialchars($order_form['tanggal_dp'] ?? ''); ?>" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out">
        </div>

        <div class="mb-4">
            <label class="block text-gray-800 text-sm font-semibold mb-2">Pelunasan:</label>
            <div class="mt-2">
                <label class="inline-flex items-center">
                    <input type="radio" name="pelunasan" value="Harus Lunas" class="form-radio h-4 w-4 text-blue-600" <?php echo (isset($order_form['pelunasan']) && $order_form['pelunasan'] === 'Harus lunas') ? 'checked' : ''; ?>>
                    <span class="ml-2 text-gray-800">Harus lunas</span>
                </label>
                <label class="inline-flex items-center ml-6">
                    <input type="radio" name="pelunasan" value="Setelah dikirim" class="form-radio h-4 w-4 text-blue-600" <?php echo (isset($order_form['pelunasan']) && $order_form['pelunasan'] === 'Setelah dikirim') ? 'checked' : ''; ?>>
                    <span class="ml-2 text-gray-800">Setelah dikirim</span>
                </label>
            </div>
        </div>

        <div class="mb-4">
            <label class="block text-gray-800 text-sm font-semibold mb-2">Ongkir:</label>
            <div class="mt-2">
                <label class="inline-flex items-center">
                    <input type="radio" name="ongkir" value="Gracia" class="form-radio h-4 w-4 text-blue-600" <?php echo (isset($order_form['ongkir']) && $order_form['ongkir'] === 'Gracia') ? 'checked' : ''; ?>>
                    <span class="ml-2 text-gray-800">Gracia</span>
                </label>
                <label class="inline-flex items-center ml-6">
                    <input type="radio" name="ongkir" value="Customer" class="form-radio h-4 w-4 text-blue-600" <?php echo (isset($order_form['ongkir']) && $order_form['ongkir'] === 'Customer') ? 'checked' : ''; ?>>
                    <span class="ml-2 text-gray-800">Customer</span>
                </label>
            </div>
        </div>

        <div class="mb-4">
            <label class="block text-gray-800 text-sm font-semibold mb-2">Packing:</label>
            <div class="mt-2">
                <label class="inline-flex items-center">
                    <input type="radio" name="packing" value="Luar kota (Ekspedisi)" class="form-radio h-4 w-4 text-blue-600" <?php echo (isset($order_form['packing']) && $order_form['packing'] === 'Luar kota (Ekspedisi)') ? 'checked' : ''; ?>>
                    <span class="ml-2 text-gray-800">Luar kota (Ekspedisi)</span>
                </label>
                <label class="inline-flex items-center ml-6">
                    <input type="radio" name="packing" value="Dalam kota" class="form-radio h-4 w-4 text-blue-600" <?php echo (isset($order_form['packing']) && $order_form['packing'] === 'Dalam kota') ? 'checked' : ''; ?>>
                    <span class="ml-2 text-gray-800">Dalam kota</span>
                </label>
            </div>
        </div>

        <div class="flex items-center justify-start space-x-4">
            <input type="submit" value="Submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out">
            <button type="button" onclick="goBack()" class="inline-block align-baseline font-semibold text-sm text-blue-600 hover:text-blue-800">
                Back
            </button>
        </div>
    </form>

    <script>
        function goBack() {
            document.querySelector('form').action = 'bikin_fo.php';
            document.querySelector('form').submit();
        }
    </script>

</body>
</html>
