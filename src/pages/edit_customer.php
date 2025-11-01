<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if the user has the 'admin' role.
if ($_SESSION['role'] !== 'admin') {
    die('Access Denied: You do not have permission to edit customer data.');
}

$customer = null;
$message = '';
$message_type = '';

// Check if ID is provided in URL
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id = $_GET['id'];

    try {
        // Fetch existing customer data
        $stmt = $pdo->prepare("SELECT id, nama, perusahaan, no_telp FROM customer WHERE id = ?");
        $stmt->execute([$id]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$customer) {
            $message = "Customer not found.";
            $message_type = 'error';
        }
    } catch (PDOException $e) {
        $message = "Error fetching customer: " . htmlspecialchars($e->getMessage());
        $message_type = 'error';
    }
} else {
    $message = "No customer ID provided.";
    $message_type = 'error';
}

// Handle form submission for updating customer
if (isset($_POST['update_customer']) && $customer) {
    $nama = trim($_POST['nama']);
    $perusahaan = trim($_POST['perusahaan']);
    $no_telp = trim($_POST['no_telp']);

    if (!empty($nama) && !empty($no_telp)) {
        try {
            $stmt = $pdo->prepare("UPDATE customer SET nama = ?, perusahaan = ?, no_telp = ? WHERE id = ?");
            $stmt->execute([$nama, $perusahaan, $no_telp, $customer['id']]);
            header("Location: " . BASE_URL . "/daftar_customer");
            exit;
        } catch (PDOException $e) {
            $message = "Error updating customer: " . htmlspecialchars($e->getMessage());
            $message_type = 'error';
        }
    } else {
        $message = "Please fill in all required fields correctly.";
        $message_type = 'error';
    }
}

$pageTitle = 'Edit Customer';
ob_start();
?>

<h1 class="text-2xl font-bold mb-6 text-gray-800">Edit Customer</h1>

<?php
if ($message) {
    echo "<div class=\"p-4 mb-4 text-sm ". ($message_type == 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800') . "\" role=\"alert\">" . $message . "</div>";
}

if ($customer) {
?>
    <form action="<?php echo BASE_URL; ?>/edit_customer?id=<?php echo htmlspecialchars($customer['id']); ?>" method="POST" class="bg-white p-8 shadow-lg">
        <input type="hidden" name="id" value="<?php echo htmlspecialchars($customer['id']); ?>">
        <div class="mb-4">
            <label for="nama" class="block text-gray-800 text-sm font-semibold mb-2">Nama Customer:</label>
            <input type="text" name="nama" id="nama" value="<?php echo htmlspecialchars($customer['nama']); ?>" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required>
        </div>

        <div class="mb-4">
            <label for="perusahaan" class="block text-gray-800 text-sm font-semibold mb-2">Perusahaan (Optional):</label>
            <input type="text" name="perusahaan" id="perusahaan" value="<?php echo htmlspecialchars($customer['perusahaan']); ?>" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out">
        </div>

        <div class="mb-4">
            <label for="no_telp" class="block text-gray-800 text-sm font-semibold mb-2">No. Telepon:</label>
            <input type="text" name="no_telp" id="no_telp" value="<?php echo htmlspecialchars($customer['no_telp']); ?>" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required>
        </div>

        <div class="flex items-center justify-start space-x-4">
            <input type="submit" name="update_customer" value="Update Customer" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out">
            <a href="<?php echo BASE_URL; ?>/daftar_customer" class="inline-block align-baseline font-semibold text-sm text-blue-600 hover:text-blue-700">Cancel</a>
        </div>
    </form>
<?php
}
?>

<?php
$content = ob_get_clean();
include __DIR__ . '/../Views/partials/base.php';
?>