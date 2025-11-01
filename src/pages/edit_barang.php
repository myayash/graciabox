<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if the user is logged in at all.
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/login');
    exit();
}

// Check if the user has the 'admin' role.
if ($_SESSION['role'] !== 'admin') {
    die('Access Denied: You do not have permission to edit barang data.');
}

$barang = null;
$message = '';
$message_type = '';

// Fetch data for dropdowns
$model_boxes = $pdo->query("SELECT nama FROM model_box WHERE is_archived = 0")->fetchAll(PDO::FETCH_ASSOC);

// Check if ID is provided in URL
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id = $_GET['id'];

    try {
        // Fetch existing barang data
        $stmt = $pdo->prepare("SELECT id, model_box, ukuran, nama FROM barang WHERE id = ?");
        $stmt->execute([$id]);
        $barang = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$barang) {
            $message = "Barang not found.";
            $message_type = 'error';
        }
    } catch (PDOException $e) {
        $message = "Error fetching barang: " . htmlspecialchars($e->getMessage());
        $message_type = 'error';
    }
}
 else {
    $message = "No barang ID provided.";
    $message_type = 'error';
}

// Handle form submission for updating barang
if (isset($_POST['update_barang']) && $barang) {
    $model_box = trim($_POST['model_box']);
    $length = trim($_POST['length']);
    $width = trim($_POST['width']);
    $height = trim($_POST['height']);
    $nama = trim($_POST['nama']);

    if (!empty($model_box) && !empty($length) && !empty($width) && !empty($height)) {
        try {
            $ukuran = $length . ' x ' . $width . ' x ' . $height;
            $stmt = $pdo->prepare("UPDATE barang SET model_box = ?, ukuran = ?, nama = ? WHERE id = ?");
            $stmt->execute([$model_box, $ukuran, $nama, $barang['id']]);
            
            header("Location: " . BASE_URL . "/daftar_barang");
            exit;
        } catch (PDOException $e) {
            $message = "Error updating barang: " . htmlspecialchars($e->getMessage());
            $message_type = 'error';
        }
    } else {
        $message = "Please fill in all required fields correctly.";
        $message_type = 'error';
    }
}

$pageTitle = 'Edit Barang';
ob_start();
?>

<h1 class="text-2xl font-bold mb-6 text-gray-800">Edit Barang</h1>

<?php
if ($message) {
    echo "<div class=\"p-4 mb-4 text-sm ". ($message_type == 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800') . "\" role=\"alert\">" . $message . "</div>";
}

if ($barang) {
    $ukuran_parts = explode(' x ', $barang['ukuran']);
    $length = $ukuran_parts[0] ?? '';
    $width = $ukuran_parts[1] ?? '';
    $height = $ukuran_parts[2] ?? '';
?>
    <form action="<?php echo BASE_URL; ?>/edit_barang?id=<?php echo htmlspecialchars($barang['id']); ?>" method="POST" class="bg-white p-8 shadow-lg">
        <input type="hidden" name="id" value="<?php echo htmlspecialchars($barang['id']); ?>">
        <div class="mb-4">
            <label for="model_box" class="block text-gray-800 text-sm font-semibold mb-2">Model Box:</label>
            <select name="model_box" id="model_box" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required>
                <option value="" disabled>Select Model Box</option>
                <?php foreach ($model_boxes as $mb): ?>
                    <option value="<?= htmlspecialchars($mb['nama']) ?>" <?= ($barang['model_box'] == $mb['nama']) ? 'selected' : '' ?>><?= htmlspecialchars($mb['nama']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-4">
            <label class="block text-gray-800 text-sm font-semibold mb-2">Ukuran:</label>
            <div class="flex space-x-2">
                <input type="number" step="0.01" name="length" placeholder="Length" value="<?= htmlspecialchars($length) ?>" max="99.99" pattern="[0-9]{2}.[0-9]{2}" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required>
                <input type="number" step="0.01" name="width" placeholder="Width" value="<?= htmlspecialchars($width) ?>" max="99.99" pattern="[0-9]{2}.[0-9]{2}" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required>
                <input type="number" step="0.01" name="height" placeholder="Height" value="<?= htmlspecialchars($height) ?>" max="99.99" pattern="[0-9]{2}.[0-9]{2}" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required>
            </div>
        </div>

        <div class="mb-4">
            <label for="nama" class="block text-gray-800 text-sm font-semibold mb-2">Nama Barang (Optional):</label>
            <input type="text" name="nama" id="nama" value="<?php echo htmlspecialchars($barang['nama']); ?>" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out">
        </div>

        <div class="flex items-center justify-start space-x-4">
            <input type="submit" name="update_barang" value="Update Barang" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out">
            <a href="<?php echo BASE_URL; ?>/daftar_barang" class="inline-block align-baseline font-semibold text-sm text-blue-600 hover:text-blue-700">Cancel</a>
        </div>
    </form>
<?php
}
?>

<?php
$content = ob_get_clean();
include __DIR__ . '/../Views/partials/base.php';
?>