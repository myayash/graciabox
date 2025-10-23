<?php session_start();

// Check if the user is logged in at all.
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Check if the user has the 'admin' role.
// For now, let's allow non-admins to see the list, but not perform actions.
// if ($_SESSION['role'] !== 'admin') {
//     die('Access Denied: You do not have permission to view this page.');
// } 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar SPK Logo</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="scripts.js"></script>
</head>
<body class="bg-gray-100 text-gray-900 pt-24 px-8 pb-8 font-mono">
    <?php include 'navbar.php'; ?>
    <h1 class="text-2xl font-bold mb-6 text-gray-800 flex items-center">
        <span class="mr-4">Daftar SPK Logo</span>
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
        <div class="inline-flex">
            <a href="bikin_fo.php" class="px-4 py-2 bg-blue-600 text-white font-bold hover:bg-blue-700 transition duration-150 ease-in-out">+ SPK Logo</a>
            <?php if (isset($_GET['show_archived']) && $_GET['show_archived'] == 'true'): ?>
                <a href="daftar_spk_logo.php" class="ml-2 px-4 py-2 bg-gray-600 text-white font-bold hover:bg-gray-700 transition duration-150 ease-in-out">Active SPK Logo</a>
            <?php else: ?>
                <a href="daftar_spk_logo.php?show_archived=true" class="ml-2 px-4 py-2 bg-gray-600 text-white font-bold hover:bg-gray-700 transition duration-150 ease-in-out">Archived SPK Logo</a>
            <?php endif; ?>
        </div>
        <a href="daftar_spk.php" class="ml-auto px-4 py-2 bg-blue-600 text-white font-bold hover:bg-blue-700 transition duration-150 ease-in-out">SPK Dudukan</a>
        <?php endif; ?>
    </h1>

    <form action="daftar_spk_logo.php" method="get" class="mb-4">
        <input type="text" name="search" placeholder="Cari data SPK Logo" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" class="p-2 border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500">
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
            header("Location: daftar_spk_logo.php");
            exit;
        }
        $archive_id = $_GET['archive_id'];
        try {
            $stmt = $pdo->prepare("UPDATE spk_logo SET is_archived = 1 WHERE id = ?");
            $stmt->execute([$archive_id]);
            header("Location: daftar_spk_logo.php"); // Redirect to refresh the page
            exit;
        } catch (PDOException $e) {
            echo "<p>Error archiving SPK: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }

    // Handle unarchive action
    if (isset($_GET['unarchive_id']) && !empty($_GET['unarchive_id'])) {
        if (!$is_admin) { // Only allow admin to unarchive
            header("Location: daftar_spk_logo.php");
            exit;
        }
        $unarchive_id = $_GET['unarchive_id'];
        try {
            $stmt = $pdo->prepare("UPDATE spk_logo SET is_archived = 0 WHERE id = ?");
            $stmt->execute([$unarchive_id]);
            header("Location: daftar_spk_logo.php?show_archived=true"); // Redirect to refresh the page, staying on archived view
            exit;
        } catch (PDOException $e) {
            echo "<p>Error unarchiving SPK: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }

    try {
        $sql = "SELECT id, nama, ukuran, quantity, dibuat, model_box, logo, ukuran_poly, lokasi_poly, klise, logo_img, is_archived FROM spk_logo";
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
            $conditions[] = "(nama LIKE ? OR ukuran LIKE ? OR model_box LIKE ? OR logo LIKE ?)";
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        }

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }
        
        $sql .= " ORDER BY id DESC";


        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $spk_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($spk_list) {
            echo "<div class=\"overflow-x-auto bg-white shadow-lg\">";
            echo "<table class=\"min-w-full divide-y divide-gray-200\">";
            echo "<thead><tr>";
            
            $column_display_names = [
                'id' => 'ID',
                'nama' => 'Nama',
                'ukuran' => 'Ukuran',
                'quantity' => 'Quantity',
                'dibuat' => 'Dibuat',
                'model_box' => 'Model Box',
                'logo' => 'Logo',
                'logo_img' => 'Gambar Logo',
                'ukuran_poly' => 'Ukuran Poly',
                'lokasi_poly' => 'Lokasi Poly',
                'klise' => 'Klise',
            ];

            foreach (array_keys($spk_list[0]) as $columnName) {
                if ($columnName == 'is_archived') continue;
                echo "<th class=\"px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider\">" . htmlspecialchars($column_display_names[$columnName] ?? $columnName) . "</th>";
            }
            if ($is_admin) {
                echo "<th class=\"px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider\">Actions</th>";
            }
            echo "</tr></thead>";
            echo "<tbody class=\"bg-white divide-y divide-gray-200\">";
            
            foreach ($spk_list as $spk) {
                echo "<tr>";
                foreach ($spk as $key => $value) {
                    if ($key == 'is_archived') continue;

                    if ($key === 'logo_img') {
                        echo "<td class=\"px-6 py-4 whitespace-normal text-sm text-gray-900\">";
                        if (!empty($value)) {
                            $images = explode(',', $value);
                            echo '<div class="grid grid-cols-3 gap-2">';
                            foreach ($images as $image) {
                                echo "<img src=\"uploads/" . htmlspecialchars(trim($image)) . "\" alt=\"Gambar Logo\" class=\"h-16 w-16 object-cover\">";
                            }
                            echo '</div>';
                        }
                        echo "</td>";
                    } else {
                        echo "<td class=\"px-6 py-4 whitespace-nowrap text-sm text-gray-900\">" . htmlspecialchars($value) . "</td>";
                    }
                }
                if ($is_admin) {
                    echo "<td class=\"px-6 py-4 whitespace-nowrap text-sm font-medium flex items-center space-x-2\">";
                    if (isset($_GET['show_archived']) && $_GET['show_archived'] == 'true') {
                        echo "<a href=\"daftar_spk_logo.php?unarchive_id=" . htmlspecialchars($spk['id']) . "\" onclick=\"return confirm('Are you sure you want to unarchive this SPK?');\" class=\"text-green-600 hover:text-green-900\">Unarchive</a>";
                    } else {
                        echo "<a href=\"daftar_spk_logo.php?archive_id=" . htmlspecialchars($spk['id']) . "\" onclick=\"return confirm('Are you sure you want to archive this SPK?');\" class=\"text-red-600 hover:text-red-900\">Archive</a>";
                    }
                    echo "</td>";
                }
                echo "</tr>";
            }
            echo "</tbody>";
            echo "</table>";
            echo "</div>";
        } else {
            echo "<p class=\"mt-4 text-gray-600\">No SPK found in the database.</p>";
        }
    } catch (PDOException $e) {
        echo "<p class=\"mt-4 text-red-600\">Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    ?>

</body>
</html>
