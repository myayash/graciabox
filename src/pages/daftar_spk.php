<?php
$is_admin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');

// Handle archive action
if (isset($_GET['archive_id']) && !empty($_GET['archive_id'])) {
    if (!$is_admin) { // Only allow admin to archive
        header("Location: " . BASE_URL . "/daftar_spk");
        exit;
    }
    $archive_id = $_GET['archive_id'];
    try {
        $stmt = $pdo->prepare("UPDATE spk_dudukan SET is_archived = 1 WHERE id = ?");
        $stmt->execute([$archive_id]);
        header("Location: " . BASE_URL . "/daftar_spk"); // Redirect to refresh the page
        exit;
    } catch (PDOException $e) {
        echo "<p>Error archiving SPK: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}

// Handle unarchive action
if (isset($_GET['unarchive_id']) && !empty($_GET['unarchive_id'])) {
    if (!$is_admin) { // Only allow admin to unarchive
        header("Location: " . BASE_URL . "/daftar_spk");
        exit;
    }
    $unarchive_id = $_GET['unarchive_id'];
    try {
        $stmt = $pdo->prepare("UPDATE spk_dudukan SET is_archived = 0 WHERE id = ?");
        $stmt->execute([$unarchive_id]);
        header("Location: " . BASE_URL . "/daftar_spk?show_archived=true"); // Redirect to refresh the page, staying on archived view
        exit;
    } catch (PDOException $e) {
        echo "<p>Error unarchiving SPK: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}
?>
<style>
    thead th {
        position: sticky;
        top: 0;
        z-index: 10;
    }
    tbody td:first-child {
        position: sticky;
        left: 0;
        z-index: 1;
        background-color: white;
    }
    thead th:first-child {
        left: 0;
        z-index: 20;
    }
    .table-container {
        position: relative;
        cursor: grab;
    }
    .table-container.active {
        cursor: grabbing;
        cursor: -webkit-grabbing;
    }
    .table-container::before,
    .table-container::after {
        content: '';
        position: absolute;
        top: 0;
        bottom: 0;
        width: 15px;
        pointer-events: none;
        transition: opacity 0.2s;
    }
    .table-container::before {
        left: 0;
        background: linear-gradient(to right, rgba(0,0,0,0.15), transparent);
        opacity: 0;
    }
    .table-container::after {
        right: 0;
        background: linear-gradient(to left, rgba(0,0,0,0.15), transparent);
        opacity: 0;
    }
    .table-container.scrolling-left::before {
        opacity: 1;
    }
    .table-container.scrolling-right::after {
        opacity: 1;
    }
</style>
<h1 class="text-xl sm:text-2xl font-bold mb-6 text-gray-800 flex flex-col sm:flex-row sm:items-center">
    <span class="mr-4">Daftar SPK</span>
    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
    <div class="flex flex-col sm:flex-row sm:inline-flex mt-2 sm:mt-0 space-y-2 sm:space-y-0 sm:space-x-2">
        <?php if (isset($_GET['show_archived']) && $_GET['show_archived'] == 'true'): ?>
            <a href="daftar_spk" class="px-4 py-2 bg-gray-600 text-white font-bold hover:bg-gray-700 transition duration-150 ease-in-out rounded-md">Active SPK Dudukan</a>
        <?php else:
?>
            <a href="daftar_spk?show_archived=true" class="sm:ml-2 px-4 py-2 bg-gray-600 text-white font-bold hover:bg-gray-700 transition duration-150 ease-in-out rounded-md">Archived SPK Dudukan</a>
        <?php endif;
?>
    </div>
    <a href="daftar_spk_logo" class="sm:ml-auto mt-2 sm:mt-0 px-4 py-2 bg-blue-600 text-white font-bold hover:bg-blue-700 transition duration-150 ease-in-out rounded-md">SPK Logo</a>
    <?php endif;
?>
</h1>

<form action="daftar_spk" method="get" class="mb-4 flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-2">
    <input type="text" name="search" placeholder="Cari data SPK" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" class="p-2 border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 rounded-md w-full sm:w-auto">
    <button type="submit" class="px-4 py-2 border border-blue-600 text-blue-600 font-bold hover:bg-blue-100 transition duration-150 ease-in-out rounded-md w-full sm:w-auto">Search</button>
    <?php if (isset($_GET['show_archived'])):
        ?><input type="hidden" name="show_archived" value="<?php echo htmlspecialchars($_GET['show_archived']); ?>">
    <?php endif;
?>
</form>

<?php
try {
    $sql = "SELECT id, nama, ukuran, quantity, dibuat, model_box, dudukan, jumlah_layer, dudukan_img, is_archived FROM spk_dudukan";
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
        $searchable_columns = ['id', 'nama', 'ukuran', 'quantity', 'dibuat', 'model_box', 'dudukan', 'jumlah_layer'];
        
        $conditions[] = "(" . implode(" LIKE ? OR ", $searchable_columns) . " LIKE ?)";
        
        $params = array_merge($params, array_fill(0, count($searchable_columns), $searchTerm));
    }

    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }
    
    $sql .= " ORDER BY id DESC";


    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $spk_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($spk_list) {
        echo "<div class=\"overflow-x-auto bg-white shadow-lg table-container rounded-lg\">";
        echo "<table class=\"min-w-full divide-y divide-gray-200\">";
        echo "<thead><tr>";
        
        $column_display_names = [
            'id' => 'ID',
            'nama' => 'Nama',
            'ukuran' => 'Ukuran',
            'quantity' => 'Quantity',
            'dibuat' => 'Dibuat',
            'model_box' => 'Model Box',
            'dudukan' => 'Dudukan',
            'jumlah_layer' => 'Jumlah Layer',
            'dudukan_img' => 'Gambar Dudukan',
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

                if ($key === 'dudukan_img') {
                    echo "<td class=\"px-6 py-4 whitespace-normal text-sm text-gray-900\">";
                    if (!empty($value)) {
                        $images = explode(',', $value);
                        echo '<div class="grid grid-cols-3 gap-2">';
                        foreach ($images as $image) {
                            echo "<img src=\"uploads/" . htmlspecialchars(trim($image)) . "\" alt=\"Gambar Dudukan\" class=\"h-16 w-16 object-cover\">";
                        }
                        echo '</div>';
                    }
                    echo "</td>";
                } else {
                    echo "<td class=\"px-6 py-4 whitespace-nowrap text-sm text-gray-900\">" . htmlspecialchars($value) . "</td>";
                }
            }
            if ($is_admin) {
                echo "<td class=\"px-6 py-4 whitespace-nowap text-sm font-medium flex items-center space-x-2\">";
                // Actions for SPK can be added here in the future, e.g., edit, view details
                // echo "<a href=\"edit_spk?id=" . htmlspecialchars($spk['id']) . "\" class=\"text-indigo-600 hover:text-indigo-900\">Edit</a>";
                echo "<a href=\"view_spk_pdf?id=" . htmlspecialchars($spk['id']) . "\" class=\"text-blue-600 hover:text-blue-900 mr-2\">View</a>";
                if (isset($_GET['show_archived']) && $_GET['show_archived'] == 'true') {
                    echo "<a href=\"daftar_spk?unarchive_id=" . htmlspecialchars($spk['id']) . "\" onclick=\"return confirm('Are you sure you want to unarchive this SPK?');\" class=\"text-green-600 hover:text-green-900\">Unarchive</a>";
                } else {
                    echo "<a href=\"daftar_spk?archive_id=" . htmlspecialchars($spk['id']) . "\" onclick=\"return confirm('Are you sure you want to archive this SPK?');\" class=\"text-red-600 hover:text-red-900\">Archive</a>";
                }
                echo "</td>";
            }
            echo "</tr>";
        }
        echo "</tbody>";
        echo "</table>";
        echo "</div>";
    } else {
        echo "<p class=\"mt-4 text-gray-600\">No SPK Dudukan found in the database. This page fetches data from saved FOs.</p>";
        echo "<p class=\"mt-4 text-gray-600\">Create a new one in FO —> Bikin FO</p>";
    }
} catch (PDOException $e) {
    echo "<p class=\"mt-4 text-red-600\">Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tableContainer = document.querySelector('.table-container');

    if (tableContainer) {
        function updateShadows() {
            const scrollLeft = tableContainer.scrollLeft;
            const scrollWidth = tableContainer.scrollWidth;
            const clientWidth = tableContainer.clientWidth;

            if (scrollWidth > clientWidth) {
                if (scrollLeft > 0) {
                    tableContainer.classList.add('scrolling-left');
                } else {
                    tableContainer.classList.remove('scrolling-left');
                }

                if (scrollLeft < scrollWidth - clientWidth - 1) { // -1 for precision
                    tableContainer.classList.add('scrolling-right');
                } else {
                    tableContainer.classList.remove('scrolling-right');
                }
            } else {
                tableContainer.classList.remove('scrolling-left', 'scrolling-right');
            }
        }

        tableContainer.addEventListener('scroll', updateShadows);
        window.addEventListener('resize', updateShadows);
        updateShadows(); // Initial check

        let isDown = false;
        let startX;
        let scrollLeft;

        tableContainer.addEventListener('mousedown', (e) => {
            isDown = true;
            tableContainer.classList.add('active');
            startX = e.pageX - tableContainer.offsetLeft;
            scrollLeft = tableContainer.scrollLeft;
        });
        tableContainer.addEventListener('mouseleave', () => {
            isDown = false;
            tableContainer.classList.remove('active');
        });
        tableContainer.addEventListener('mouseup', () => {
            isDown = false;
            tableContainer.classList.remove('active');
        });
        tableContainer.addEventListener('mousemove', (e) => {
            if(!isDown) return;
            e.preventDefault();
            const x = e.pageX - tableContainer.offsetLeft;
            const walk = (x - startX) * 2; // scroll-fast
            tableContainer.scrollLeft = scrollLeft - walk;
        });
    }
});
</script>