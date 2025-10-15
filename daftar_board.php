<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Board List</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="scripts.js"></script>
</head>
<body class="bg-gray-100 text-gray-900 pt-24 px-8 pb-8 font-mono">
    <?php include 'navbar.php'; ?>
    <h1 class="text-2xl font-bold mb-6 text-gray-800">Board Information
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
        <div class="inline-flex ml-4">
            <a href="bikin_board.php" class="px-4 py-2 bg-blue-600 text-white font-bold hover:bg-blue-700 transition duration-150 ease-in-out">Add New Board</a>
            <?php if (isset($_GET['show_archived']) && $_GET['show_archived'] == 'true'): ?>
                <a href="daftar_board.php" class="ml-2 px-4 py-2 bg-gray-600 text-white font-bold hover:bg-gray-700 transition duration-150 ease-in-out">Show Active Boards</a>
            <?php else: ?>
                <a href="daftar_board.php?show_archived=true" class="ml-2 px-4 py-2 bg-gray-600 text-white font-bold hover:bg-gray-700 transition duration-150 ease-in-out">Show Archived Boards</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </h1>

    <?php
    require_once 'config.php';

    $is_admin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');

    // Handle archive action
    if (isset($_GET['archive_id']) && !empty($_GET['archive_id'])) {
        if (!$is_admin) { // Only allow admin to archive
            header("Location: daftar_board.php");
            exit;
        }
        $archive_id = $_GET['archive_id'];
        try {
            $stmt = $pdo->prepare("UPDATE board SET is_archived = 1 WHERE id = ?");
            $stmt->execute([$archive_id]);
            header("Location: daftar_board.php"); // Redirect to refresh the page
            exit;
        } catch (PDOException $e) {
            echo "<p>Error archiving board: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }

    // Handle unarchive action
    if (isset($_GET['unarchive_id']) && !empty($_GET['unarchive_id'])) {
        if (!$is_admin) { // Only allow admin to unarchive
            header("Location: daftar_board.php");
            exit;
        }
        $unarchive_id = $_GET['unarchive_id'];
        try {
            $stmt = $pdo->prepare("UPDATE board SET is_archived = 0 WHERE id = ?");
            $stmt->execute([$unarchive_id]);
            header("Location: daftar_board.php?show_archived=true"); // Redirect to refresh the page, staying on archived view
            exit;
        } catch (PDOException $e) {
            echo "<p>Error unarchiving board: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }

    try {
        $sql = "SELECT id, jenis, dibuat, is_archived FROM board";
        if (isset($_GET['show_archived']) && $_GET['show_archived'] == 'true') {
            $sql .= " WHERE is_archived = 1"; // Show only archived
        } else {
            $sql .= " WHERE is_archived = 0"; // Show only active
        }
        $stmt = $pdo->query($sql);
        $boards = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($boards) {
            echo "<div class=\"overflow-x-auto bg-white shadow-lg\">";
            echo "<table class=\"min-w-full divide-y divide-gray-200\">";
            echo "<thead><tr>";
            // Define a mapping for column names to display names
            $column_display_names = [
                'id' => 'ID',
                'jenis' => 'Jenis Board',
                'dibuat' => 'Dibuat',
                'is_archived' => 'Archived'
            ];

            // Dynamically create table headers from column names, using display names if available
            foreach (array_keys($boards[0]) as $columnName) {
                if ($columnName == 'is_archived') continue;
                echo "<th class=\"px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider\">" . htmlspecialchars($column_display_names[$columnName] ?? $columnName) . "</th>";
            }
            if ($is_admin) {
                echo "<th class=\"px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider\">Actions</th>";
            }
            echo "</tr></thead>";
            echo "<tbody class=\"bg-white divide-y divide-gray-200\">";
            // Populate table rows with data
            foreach ($boards as $board) {
                echo "<tr>";
                foreach ($board as $columnName => $value) {
                    if ($columnName == 'is_archived') continue;
                    echo "<td class=\"px-6 py-4 whitespace-nowrap text-sm text-gray-900\">" . htmlspecialchars($value) . "</td>";
                }
                if ($is_admin) {
                    echo "<td class=\"px-6 py-4 whitespace-nowrap text-sm font-medium flex items-center space-x-2\">";
                    echo "<a href=\"edit_board.php?id=" . htmlspecialchars($board['id']) . "\" class=\"text-indigo-600 hover:text-indigo-900\">Edit</a>";
                    if (isset($_GET['show_archived']) && $_GET['show_archived'] == 'true') {
                        echo "<a href=\"daftar_board.php?unarchive_id=" . htmlspecialchars($board['id']) . "\" onclick=\"return confirm('Are you sure you want to unarchive this board?');\" class=\"text-green-600 hover:text-green-900\">Unarchive</a>";
                    } else {
                        echo "<a href=\"daftar_board.php?archive_id=" . htmlspecialchars($board['id']) . "\" onclick=\"return confirm('Are you sure you want to archive this board?');\" class=\"text-red-600 hover:text-red-900\">Archive</a>";
                    }
                }
                echo "</td>";
                echo "</tr>";
            }
            echo "</tbody>";
            echo "</table>";
            echo "</div>";
        } else {
            echo "<p class=\"mt-4 text-gray-600\">No boards found in the database.</p>";
        }
    } catch (PDOException $e) {
        echo "<p class=\"mt-4 text-red-600\">Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    ?>

</body>
</html>