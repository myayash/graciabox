<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Model Box List</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="scripts.js"></script>
</head>
<body class="bg-gray-100 text-gray-900 pt-24 px-8 pb-8 font-mono">
    <?php include 'navbar.php'; ?>
    <h1 class="text-2xl font-bold mb-6 text-gray-800">Model Box Information
        <div class="inline-flex ml-4">
            <a href="bikin_model_box.php" class="px-4 py-2 bg-blue-600 text-white font-bold hover:bg-blue-700 transition duration-150 ease-in-out">Add New Model Box</a>
            <?php if (isset($_GET['show_archived']) && $_GET['show_archived'] == 'true'): ?>
                <a href="daftar_model_box.php" class="ml-2 px-4 py-2 bg-gray-600 text-white font-bold hover:bg-gray-700 transition duration-150 ease-in-out">Show Active Model Boxes</a>
            <?php else: ?>
                <a href="daftar_model_box.php?show_archived=true" class="ml-2 px-4 py-2 bg-gray-600 text-white font-bold hover:bg-gray-700 transition duration-150 ease-in-out">Show Archived Model Boxes</a>
            <?php endif; ?>
        </div>
    </h1>

    <?php
    require_once 'config.php';

    // Handle archive action
    if (isset($_GET['archive_id']) && !empty($_GET['archive_id'])) {
        $archive_id = $_GET['archive_id'];
        try {
            $stmt = $pdo->prepare("UPDATE model_box SET is_archived = 1 WHERE id = ?");
            $stmt->execute([$archive_id]);
            header("Location: daftar_model_box.php"); // Redirect to refresh the page
            exit;
        } catch (PDOException $e) {
            echo "<p>Error archiving model box: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }

    // Handle unarchive action
    if (isset($_GET['unarchive_id']) && !empty($_GET['unarchive_id'])) {
        $unarchive_id = $_GET['unarchive_id'];
        try {
            $stmt = $pdo->prepare("UPDATE model_box SET is_archived = 0 WHERE id = ?");
            $stmt->execute([$unarchive_id]);
            header("Location: daftar_model_box.php?show_archived=true"); // Redirect to refresh the page, staying on archived view
            exit;
        } catch (PDOException $e) {
            echo "<p>Error unarchiving model box: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }

    try {
        $sql = "SELECT id, nama, dibuat, is_archived FROM model_box";
        if (isset($_GET['show_archived']) && $_GET['show_archived'] == 'true') {
            $sql .= " WHERE is_archived = 1"; // Show only archived
        } else {
            $sql .= " WHERE is_archived = 0"; // Show only active
        }
        $stmt = $pdo->query($sql);
        $model_boxes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($model_boxes) {
            echo "<div class=\"overflow-x-auto bg-white shadow-lg\">";
            echo "<table class=\"min-w-full divide-y divide-gray-200\">";
            echo "<thead><tr>";
            // Define a mapping for column names to display names
            $column_display_names = [
                'id' => 'ID',
                'nama' => 'Nama Model Box',
                'dibuat' => 'Dibuat',
                'is_archived' => 'Archived'
            ];

            // Dynamically create table headers from column names, using display names if available
            foreach (array_keys($model_boxes[0]) as $columnName) {
                if ($columnName == 'is_archived') continue;
                echo "<th class=\"px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider\">" . htmlspecialchars($column_display_names[$columnName] ?? $columnName) . "</th>";
            }
            echo "<th class=\"px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider\">Actions</th>";
            echo "</tr></thead>";
            echo "<tbody class=\"bg-white divide-y divide-gray-200\">";
            // Populate table rows with data
            foreach ($model_boxes as $model_box) {
                echo "<tr>";
                foreach ($model_box as $columnName => $value) {
                    if ($columnName == 'is_archived') continue;
                    echo "<td class=\"px-6 py-4 whitespace-nowrap text-sm text-gray-900\">" . htmlspecialchars($value) . "</td>";
                }
                echo "<td class=\"px-6 py-4 whitespace-nowrap text-sm font-medium flex items-center space-x-2\">";
                echo "<a href=\"edit_model_box.php?id=" . htmlspecialchars($model_box['id']) . "\" class=\"text-indigo-600 hover:text-indigo-900\">Edit</a>";
                if (isset($_GET['show_archived']) && $_GET['show_archived'] == 'true') {
                    echo "<a href=\"daftar_model_box.php?unarchive_id=" . htmlspecialchars($model_box['id']) . "\" onclick=\"return confirm('Are you sure you want to unarchive this model box?');\" class=\"text-green-600 hover:text-green-900\">Unarchive</a>";
                } else {
                    echo "<a href=\"daftar_model_box.php?archive_id=" . htmlspecialchars($model_box['id']) . "\" onclick=\"return confirm('Are you sure you want to archive this model box?');\" class=\"text-red-600 hover:text-red-900\">Archive</a>";
                }
                echo "</td>";
                echo "</tr>";
            }
            echo "</tbody>";
            echo "</table>";
            echo "</div>";
        } else {
            echo "<p class=\"mt-4 text-gray-600\">No model boxes found in the database.</p>";
        }
    } catch (PDOException $e) {
        echo "<p class=\"mt-4 text-red-600\">Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    ?>

</body>
</html>