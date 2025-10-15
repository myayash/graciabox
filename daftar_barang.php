<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barang List</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="scripts.js"></script>
</head>
<body class="bg-gray-100 text-gray-900 pt-24 px-8 pb-8 font-mono">
    <?php include 'navbar.php'; ?>
    <h1 class="text-2xl font-bold mb-6 text-gray-800">Barang Information
        <div class="inline-flex ml-4">
            <a href="bikin_barang.php" class="px-4 py-2 bg-blue-600 text-white font-bold hover:bg-blue-700 transition duration-150 ease-in-out">Add New Barang</a>
            <?php if (isset($_GET['show_archived']) && $_GET['show_archived'] == 'true'): ?>
                <a href="daftar_barang.php" class="ml-2 px-4 py-2 bg-gray-600 text-white font-bold hover:bg-gray-700 transition duration-150 ease-in-out">Show Active Barang</a>
            <?php else: ?>
                <a href="daftar_barang.php?show_archived=true" class="ml-2 px-4 py-2 bg-gray-600 text-white font-bold hover:bg-gray-700 transition duration-150 ease-in-out">Show Archived Barang</a>
            <?php endif; ?>
        </div>
    </h1>

    <?php
    require_once 'config.php';

    // Handle archive action
    if (isset($_GET['archive_id']) && !empty($_GET['archive_id'])) {
        $archive_id = $_GET['archive_id'];
        try {
            $stmt = $pdo->prepare("UPDATE barang SET is_archived = 1 WHERE id = ?");
            $stmt->execute([$archive_id]);
            header("Location: daftar_barang.php"); // Redirect to refresh the page
            exit;
        } catch (PDOException $e) {
            echo "<p>Error archiving barang: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }

    // Handle unarchive action
    if (isset($_GET['unarchive_id']) && !empty($_GET['unarchive_id'])) {
        $unarchive_id = $_GET['unarchive_id'];
        try {
            $stmt = $pdo->prepare("UPDATE barang SET is_archived = 0 WHERE id = ?");
            $stmt->execute([$unarchive_id]);
            header("Location: daftar_barang.php?show_archived=true"); // Redirect to refresh the page, staying on archived view
            exit;
        } catch (PDOException $e) {
            echo "<p>Error unarchiving barang: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }

    try {
        $sql = "SELECT id, model_box, ukuran, nama, dibuat, is_archived FROM barang";
        if (isset($_GET['show_archived']) && $_GET['show_archived'] == 'true') {
            $sql .= " WHERE is_archived = 1"; // Show only archived
        } else {
            $sql .= " WHERE is_archived = 0"; // Show only active
        }
        $stmt = $pdo->query($sql);
        $barangs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($barangs) {
            echo "<div class=\"overflow-x-auto bg-white shadow-lg\">";
            echo "<table class=\"min-w-full divide-y divide-gray-200\">";
            echo "<thead><tr>";
            // Define a mapping for column names to display names
            $column_display_names = [
                'id' => 'ID',
                'model_box' => 'Model Box',
                'ukuran' => 'Ukuran',
                'nama' => 'Nama Barang',
                'dibuat' => 'Dibuat',
                'is_archived' => 'Archived'
            ];

            // Dynamically create table headers from column names, using display names if available
            foreach (array_keys($barangs[0]) as $columnName) {
                if ($columnName == 'is_archived') continue;
                echo "<th class=\"px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider\">" . htmlspecialchars($column_display_names[$columnName] ?? $columnName) . "</th>";
            }
            echo "<th class=\"px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider\">Actions</th>";
            echo "</tr></thead>";
            echo "<tbody class=\"bg-white divide-y divide-gray-200\">";
            // Populate table rows with data
            foreach ($barangs as $barang) {
                echo "<tr>";
                foreach ($barang as $columnName => $value) {
                    if ($columnName == 'is_archived') continue;
                    echo "<td class=\"px-6 py-4 whitespace-nowrap text-sm text-gray-900\">" . htmlspecialchars($value) . "</td>";
                }
                echo "<td class=\"px-6 py-4 whitespace-nowrap text-sm font-medium flex items-center space-x-2\">";
                echo "<a href=\"edit_barang.php?id=" . htmlspecialchars($barang['id']) . "\" class=\"text-indigo-600 hover:text-indigo-900\">Edit</a>";
                if (isset($_GET['show_archived']) && $_GET['show_archived'] == 'true') {
                    echo "<a href=\"daftar_barang.php?unarchive_id=" . htmlspecialchars($barang['id']) . "\" onclick=\"return confirm('Are you sure you want to unarchive this barang?');\" class=\"text-green-600 hover:text-green-900\">Unarchive</a>";
                } else {
                    echo "<a href=\"daftar_barang.php?archive_id=" . htmlspecialchars($barang['id']) . "\" onclick=\"return confirm('Are you sure you want to archive this barang?');\" class=\"text-red-600 hover:text-red-900\">Archive</a>";
                }
                echo "</td>";
                echo "</tr>";
            }
            echo "</tbody>";
            echo "</table>";
            echo "</div>";
        } else {
            echo "<p class=\"mt-4 text-gray-600\">No barang found in the database.</p>";
        }
    } catch (PDOException $e) {
        echo "<p class=\"mt-4 text-red-600\">Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    ?>

</body>
</html>