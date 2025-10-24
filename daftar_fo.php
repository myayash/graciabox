<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>daftar FO</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="scripts.js"></script>
</head>
<body class="bg-gray-100 text-gray-900 pt-24 px-8 pb-8 font-mono">
    <?php include 'navbar.php'; ?>
    <h1 class="text-2xl font-bold mb-6 text-gray-800 flex items-center">
        <span class="mr-4">daftar FO</span>
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
        <div class="inline-flex">
            <a href="bikin_fo.php" class="px-4 py-2 bg-blue-600 text-white font-bold hover:bg-blue-700 transition duration-150 ease-in-out">+ Order</a>
            <?php if (isset($_GET['show_archived']) && $_GET['show_archived'] == 'true'): ?>
                <a href="daftar_fo.php" class="ml-2 px-4 py-2 bg-gray-600 text-white font-bold hover:bg-gray-700 transition duration-150 ease-in-out">Active Orders</a>
            <?php else: ?>
                <a href="daftar_fo.php?show_archived=true" class="ml-2 px-4 py-2 bg-gray-600 text-white font-bold hover:bg-gray-700 transition duration-150 ease-in-out">Archived Orders</a>
            <?php endif; ?>
        </div>
        <div class="ml-auto">
            <a href="export_fo_excel.php<?php echo isset($_GET['search']) ? '?search=' . htmlspecialchars($_GET['search']) : ''; echo isset($_GET['show_archived']) ? (isset($_GET['search']) ? '&' : '?') . 'show_archived=' . htmlspecialchars($_GET['show_archived']) : ''; ?>" class="px-4 py-2 bg-green-600 text-white font-bold hover:bg-green-700 transition duration-150 ease-in-out">Export to Excel</a>
        </div>
        <?php endif; ?>
    </h1>

    <form action="daftar_fo.php" method="get" class="mb-4">
        <input type="text" name="search" placeholder="cari FO" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" class="p-2 border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500">
        <button type="submit" class="ml-2 px-4 py-2 border border-blue-600 text-blue-600 font-bold hover:bg-blue-100 transition duration-150 ease-in-out">Search</button>
        <?php if (isset($_GET['show_archived'])):
            ?><input type="hidden" name="show_archived" value="<?php echo htmlspecialchars($_GET['show_archived']); ?>">
        <?php endif; ?>
    </form>

    <?php
    require_once 'config.php';

    $is_admin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');

    // Handle archive action
    if (isset($_GET['archive_id']) && !empty($_GET['archive_id'])) {
        if (!$is_admin) { // Only allow admin to archive
            header("Location: daftar_fo.php");
            exit;
        }
        $archive_id = $_GET['archive_id'];
        try {
            $stmt = $pdo->prepare("UPDATE orders SET is_archived = 1 WHERE id = ?");
            $stmt->execute([$archive_id]);
            header("Location: daftar_fo.php"); // Redirect to refresh the page
            exit;
        } catch (PDOException $e) {
            echo "<p>Error archiving order: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }

    // Handle unarchive action
    if (isset($_GET['unarchive_id']) && !empty($_GET['unarchive_id'])) {
        if (!$is_admin) { // Only allow admin to unarchive
            header("Location: daftar_fo.php");
            exit;
        }
        $unarchive_id = $_GET['unarchive_id'];
        try {
            $stmt = $pdo->prepare("UPDATE orders SET is_archived = 0 WHERE id = ?");
            $stmt->execute([$unarchive_id]);
            header("Location: daftar_fo.php?show_archived=true"); // Redirect to refresh the page, staying on archived view
            exit;
        } catch (PDOException $e) {
            echo "<p>Error unarchiving order: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }

    try {
        $sql = "SELECT id, lokasi, nama, ukuran, kode_pisau, nama_box_lama, quantity, model_box, tanggal_kirim, jam_kirim, dikirim_dari, tujuan_kirim, keterangan, sales_pj, dibuat, is_archived FROM orders";
        $conditions = [];
        $params = [];

        // Add archived/active filter
        if (isset($_GET['show_archived']) && $_GET['show_archived'] == 'true') {
            $conditions[] = "is_archived = 1";
        } else {
            $conditions[] = "is_archived = 0";
        }

        // Add search filter
        if (isset($_GET['search']) && $_GET['search'] !== '') {
            $searchTerm = '%' . $_GET['search'] . '%';
            $conditions[] = "(nama LIKE ? OR kode_pisau LIKE ? OR ukuran LIKE ? OR model_box LIKE ? OR jenis_board LIKE ? OR cover_dlm LIKE ? OR sales_pj LIKE ? OR nama_box_lama LIKE ? OR lokasi LIKE ? OR cover_lr LIKE ? OR keterangan LIKE ?)";
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        }

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }
        $sql .= " ORDER BY dibuat DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($orders) {
            echo "<div class=\"overflow-x-auto bg-white shadow-lg\">";
            echo "<table class=\"min-w-full divide-y divide-gray-200\">";
            echo "<thead><tr>";
            // Define a mapping for column names to display names
            $column_display_names = [
                'id' => 'ID',
                'lokasi' => 'Retail',
                'nama' => 'Nama Customer',
                'ukuran' => 'Ukuran (cm)',
                'kode_pisau' => 'Kode Pisau',
                'nama_box_lama' => 'Nama Box',
                'quantity' => 'Quantity',
                'model_box' => 'Model Box',
                'tanggal_kirim' => 'Tanggal Kirim',
                'jam_kirim' => 'Jam Kirim',
                'dikirim_dari' => 'Dikirim Dari',
                'tujuan_kirim' => 'Tujuan Kirim',
                'keterangan' => 'Keterangan',
                'sales_pj' => 'PJ Sales',
                'dibuat' => 'Dibuat',
                'is_archived' => 'Archived'
            ];

            // Dynamically create table headers from column names, using display names if available
            foreach (array_keys($orders[0]) as $columnName) {
                if ($columnName == 'is_archived') continue;
                $thClasses = "px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider";
                if ($columnName == 'cover_dlm' || $columnName == 'cover_lr' || $columnName == 'keterangan') {
                    $thClasses .= " text-center";
                }
                echo "<th class=\"" . $thClasses . "\">" . htmlspecialchars($column_display_names[$columnName] ?? $columnName) . "</th>";
            }
            if ($is_admin) {
                echo "<th class=\"px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider\">Actions</th>";
            }
            echo "</tr></thead>";
            echo "<tbody class=\"bg-white divide-y divide-gray-200\">";
            // Populate table rows with data
            foreach ($orders as $order) {
                echo "<tr>";
                foreach ($order as $columnName => $value) {
                    if ($columnName == 'is_archived') continue;
                    $displayValue = nl2br(htmlspecialchars($value)); // Use nl2br for the new cover_lr column
                    if ($columnName == 'cover_dlm') { // Special formatting only for cover_dlm now
                        $displayValue = preg_replace('/(supplier|jenis|warna|gsm|ukuran):\s*/i', '', $displayValue);
                    }
                    $tdClasses = "px-6 py-4 whitespace-nowrap text-sm text-gray-900";
                    if ($columnName == 'cover_dlm' || $columnName == 'cover_lr' || $columnName == 'keterangan') {
                        $tdClasses .= " text-center";
                    }
                    echo "<td class=\"" . $tdClasses . "\">" . $displayValue . "</td>";
                }
                if ($is_admin) {
                    echo "<td class=\"px-6 py-4 whitespace-nowrap text-sm font-medium flex items-center space-x-2\">";
                    echo "<a href=\"edit_fo.php?id=" . htmlspecialchars($order['id']) . "\" class=\"text-indigo-600 hover:text-indigo-900 mr-2\">Edit</a>";
                    echo "<a href=\"view_fo_pdf.php?id=" . htmlspecialchars($order['id']) . "\" class=\"text-blue-600 hover:text-blue-900 mr-2\">View</a>";
                    if (isset($_GET['show_archived']) && $_GET['show_archived'] == 'true') {
                        echo "<a href=\"daftar_fo.php?unarchive_id=" . htmlspecialchars($order['id']) . "\" onclick=\"return confirm('Are you sure you want to unarchive this order?');\" class=\"text-green-600 hover:text-green-900\">Unarchive</a>";
                    } else {
                        echo "<a href=\"daftar_fo.php?archive_id=" . htmlspecialchars($order['id']) . "\" onclick=\"return confirm('Are you sure you want to archive this order?');\" class=\"text-red-600 hover:text-red-900\">Archive</a>";
                    }
                }
                echo "</td>";
                echo "</tr>";
            }
            echo "</tbody>";
            echo "</table>";
            echo "</div>";
        } else {
            echo "<p class=\"mt-4 text-gray-600\">No orders found in the database.</p>";
        }
    } catch (PDOException $e) {
        echo "<p class=\"mt-4 text-red-600\">Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    ?>

</body>
</html>