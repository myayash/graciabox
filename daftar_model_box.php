<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>daftar model box</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="scripts.js"></script>
</head>
<body class="bg-gray-100 text-gray-900 pt-24 px-8 pb-8 font-mono">
    <?php include 'navbar.php'; ?>
    <h1 class="text-2xl font-bold mb-6 text-gray-800">daftar model box
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
        <div class="inline-flex ml-4">
            <a href="bikin_model_box.php" class="px-4 py-2 bg-blue-600 text-white font-bold hover:bg-blue-700 transition duration-150 ease-in-out">+ Model Box</a>
            <?php if (isset($_GET['show_archived']) && $_GET['show_archived'] == 'true'): ?>
                <a href="daftar_model_box.php" class="ml-2 px-4 py-2 bg-gray-600 text-white font-bold hover:bg-gray-700 transition duration-150 ease-in-out">Active Model Box</a>
            <?php else: ?>
                <a href="daftar_model_box.php?show_archived=true" class="ml-2 px-4 py-2 bg-gray-600 text-white font-bold hover:bg-gray-700 transition duration-150 ease-in-out">Archived Model Box</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </h1>

    <form action="daftar_model_box.php" method="get" class="mb-4">
        <input type="text" name="search" placeholder="cari model box" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" class="p-2 border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500">
        <button type="submit" class="ml-2 px-4 py-2 border border-blue-600 text-blue-600 font-bold hover:bg-blue-100 transition duration-150 ease-in-out">Search</button>
        <?php if (isset($_GET['show_archived'])): ?>
            <input type="hidden" name="show_archived" value="<?php echo htmlspecialchars($_GET['show_archived']); ?>">
        <?php endif; ?>
    </form>

        <?php

        require_once 'config.php';

    

        $is_admin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');

    

        // Handle archive action

        if (isset($_GET['archive_id']) && !empty($_GET['archive_id'])) {

            if (!$is_admin) { // Only allow admin to archive

                header("Location: daftar_model_box.php");

                exit;

            }

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

            if (!$is_admin) { // Only allow admin to unarchive

                header("Location: daftar_model_box.php");

                exit;

            }

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

            $sql = "SELECT id, nama, box_luar, box_dlm, dibuat, is_archived FROM model_box";
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
                $conditions[] = "(nama LIKE ?)";
                $params = array_merge($params, [$searchTerm]);
            }

            if (!empty($conditions)) {
                $sql .= " WHERE " . implode(" AND ", $conditions);
            }

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $model_boxes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    

            if ($model_boxes) {

                echo "<div class=\"overflow-x-auto bg-white shadow-lg\">";

                echo "<table class=\"min-w-full divide-y divide-gray-200\">";

                echo "<thead><tr>";

                // Define a mapping for column names to display names

                $column_display_names = [

                    'id' => 'ID',

                    'nama' => 'Nama Model Box',

                    'box_luar' => 'Box Luar',

                    'box_dlm' => 'Box Dalam',

                    'dibuat' => 'Dibuat',

                    'is_archived' => 'Archived'

                ];

    

                // Dynamically create table headers from column names, using display names if available

                echo "<th class=\"px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider\">" . htmlspecialchars($column_display_names['id'] ?? 'ID') . "</th>";
                echo "<th class=\"px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider\">" . htmlspecialchars($column_display_names['nama'] ?? 'Nama Model Box') . "</th>";
                echo "<th class=\"px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider\">" . htmlspecialchars($column_display_names['box_luar'] ?? 'Box Luar') . "</th>";
                echo "<th class=\"px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider\">" . htmlspecialchars($column_display_names['box_dlm'] ?? 'Box Dalam') . "</th>";
                echo "<th class=\"px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider\">" . htmlspecialchars($column_display_names['dibuat'] ?? 'Dibuat') . "</th>";

                if ($is_admin) {

                    echo "<th class=\"px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider\">Actions</th>";

                }

                echo "</tr></thead>";

                echo "<tbody class=\"bg-white divide-y divide-gray-200\">";

                // Populate table rows with data

                foreach ($model_boxes as $model_box) {

                    echo "<tr>";

                    echo "<td class=\"px-6 py-4 whitespace-nowrap text-sm text-gray-900\">" . htmlspecialchars($model_box['id']) . "</td>";
                    echo "<td class=\"px-6 py-4 whitespace-nowrap text-sm text-gray-900\">" . htmlspecialchars($model_box['nama']) . "</td>";
                    echo "<td class=\"px-6 py-4 whitespace-nowrap text-sm text-gray-900\">" . htmlspecialchars($model_box['box_luar']) . "</td>";
                    echo "<td class=\"px-6 py-4 whitespace-nowrap text-sm text-gray-900\">" . htmlspecialchars($model_box['box_dlm']) . "</td>";
                    echo "<td class=\"px-6 py-4 whitespace-nowrap text-sm text-gray-900\">" . htmlspecialchars($model_box['dibuat']) . "</td>";

                    if ($is_admin) {

                        echo "<td class=\"px-6 py-4 whitespace-nowrap text-sm font-medium flex items-center space-x-2\">";

                        echo "<a href=\"edit_model_box.php?id=" . htmlspecialchars($model_box['id']) . "\" class=\"text-indigo-600 hover:text-indigo-900\">Edit</a>";

                        if (isset($_GET['show_archived']) && $_GET['show_archived'] == 'true') {

                            echo "<a href=\"daftar_model_box.php?unarchive_id=" . htmlspecialchars($model_box['id']) . "\" onclick=\"return confirm('Are you sure you want to unarchive this model box?');\" class=\"text-green-600 hover:text-green-900\">Unarchive</a>";

                        } else {

                            echo "<a href=\"daftar_model_box.php?archive_id=" . htmlspecialchars($model_box['id']) . "\" onclick=\"return confirm('Are you sure you want to archive this model box?');\" class=\"text-red-600 hover:text-red-900\">Archive</a>";

                        }

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