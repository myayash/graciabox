<?php

$is_admin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');

// Handle archive action
if (isset($_GET['archive_id']) && !empty($_GET['archive_id'])) {
    if (!$is_admin) { // Only allow admin to archive
        header("Location: " . BASE_URL . "/daftar_fo");
        exit;
    }
    $archive_id = $_GET['archive_id'];
    try {
        $stmt = $pdo->prepare("UPDATE orders SET is_archived = 1 WHERE id = ?");
        $stmt->execute([$archive_id]);
        header("Location: " . BASE_URL . "/daftar_fo"); // Redirect to refresh the page
        exit;
    } catch (PDOException $e) {
        echo "<p>Error archiving order: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}

// Handle unarchive action
if (isset($_GET['unarchive_id']) && !empty($_GET['unarchive_id'])) {
    if (!$is_admin) { // Only allow admin to unarchive
        header("Location: " . BASE_URL . "/daftar_fo");
        exit;
    }
    $unarchive_id = $_GET['unarchive_id'];
    try {
        $stmt = $pdo->prepare("UPDATE orders SET is_archived = 0 WHERE id = ?");
        $stmt->execute([$unarchive_id]);
        header("Location: " . BASE_URL . "/daftar_fo?show_archived=true"); // Redirect to refresh the page, staying on archived view
        exit;
    } catch (PDOException $e) {
        echo "<p>Error unarchiving order: " . htmlspecialchars($e->getMessage()) . "</p>";
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
    .table-container {
        cursor: grab;
    }
    .table-container.active {
        cursor: grabbing;
        cursor: -webkit-grabbing;
    }
</style>
<h1 class="text-xl sm:text-2xl font-bold mb-6 text-gray-800 flex flex-col sm:flex-row sm:items-center">
    <span class="mr-4">daftar FO</span>
    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
    <div class="flex flex-col sm:flex-row sm:inline-flex mt-2 sm:mt-0 sm:ml-4 space-y-2 sm:space-y-0 sm:space-x-2">
        <a href="bikin_fo" class="px-4 py-2 bg-blue-600 text-white font-bold hover:bg-blue-700 transition duration-150 ease-in-out rounded-md">+ Order</a>
        <?php if (isset($_GET['show_archived']) && $_GET['show_archived'] == 'true'): ?>
            <a href="daftar_fo" class="px-4 py-2 bg-gray-600 text-white font-bold hover:bg-gray-700 transition duration-150 ease-in-out rounded-md">Active Orders</a>
        <?php else: ?>
            <a href="daftar_fo?show_archived=true" class="px-4 py-2 bg-gray-600 text-white font-bold hover:bg-gray-700 transition duration-150 ease-in-out rounded-md">Archived Orders</a>
        <?php endif; ?>
    </div>
    <div class="sm:ml-auto mt-2 sm:mt-0">
        <a href="export_fo_excel<?php echo isset($_GET['search']) ? '?search=' . htmlspecialchars($_GET['search']) : ''; echo isset($_GET['show_archived']) ? (isset($_GET['search']) ? '&' : '?') . 'show_archived=' . htmlspecialchars($_GET['show_archived']) : ''; ?>" class="block sm:inline-block px-4 py-2 bg-green-600 text-white font-bold hover:bg-green-700 transition duration-150 ease-in-out rounded-md">Export to Excel</a>
    </div>
    <?php endif; ?>
</h1>

<?php if (!empty($_SESSION['flash_success'])):
    $flash_msg = $_SESSION['flash_success']; ?>
    <div id="server_flash_success" class="mb-4 p-3 bg-green-100 border border-green-300 text-green-800">
        <?= htmlspecialchars($flash_msg) ?>
    </div>
    <script>window.__flashSuccess = <?= json_encode($flash_msg) ?>;</script>
    <?php unset($_SESSION['flash_success']);
endif; ?>

<form action="daftar_fo" method="get" class="mb-4 flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-2">
    <input type="text" name="search" placeholder="cari FO" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" class="p-2 border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 rounded-md w-full sm:w-auto">
    <button type="submit" class="px-4 py-2 border border-blue-600 text-blue-600 font-bold hover:bg-blue-100 transition duration-150 ease-in-out rounded-md w-full sm:w-auto">Search</button>
    <?php if (isset($_GET['show_archived'])): 
        ?><input type="hidden" name="show_archived" value="<?php echo htmlspecialchars($_GET['show_archived']); ?>">
    <?php endif; ?>
</form>

<?php

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
        $searchable_columns = [
            'id', 'nama', 'kode_pisau', 'ukuran', 'model_box', 'jenis_board', 
            'cover_dlm', 'sales_pj', 'nama_box_lama', 'lokasi', 'cover_lr', 
            'keterangan', 'quantity', 'feedback_cust', 'aksesoris', 
            'ket_aksesoris', 'dudukan', 'jumlah_layer', 'logo', 'ukuran_poly', 
            'lokasi_poly', 'klise', 'tanggal_kirim', 'jam_kirim', 
            'dikirim_dari', 'tujuan_kirim', 'tanggal_dp', 'pelunasan', 
            'ongkir', 'packing', 'biaya'
        ];
        
        $conditions[] = "(" . implode(" LIKE ? OR ", $searchable_columns) . " LIKE ?)";
        
        $params = array_merge($params, array_fill(0, count($searchable_columns), $searchTerm));
    }

    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }
    $sql .= " ORDER BY dibuat DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($orders) {
        echo "<div class=\"overflow-x-auto bg-white shadow-lg table-container rounded-lg\">";
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
                echo "<td class=\"px-6 py-4 whitespace-nowrap text-sm font-medium flex flex-col sm:flex-row sm:items-center space-y-2 sm:space-y-0 sm:space-x-2\">";
                echo "<a href=\"bikin_fo?id=" . htmlspecialchars($order['id']) . "\" class=\"text-indigo-600 hover:text-indigo-900\">Edit</a>";
                echo "<a href=\"view_fo_pdf?id=" . htmlspecialchars($order['id']) . "\" class=\"text-blue-600 hover:text-blue-900\">View</a>";
                if (isset($_GET['show_archived']) && $_GET['show_archived'] == 'true') {
                    echo "<a href=\"daftar_fo?unarchive_id=" . htmlspecialchars($order['id']) . "\" onclick=\"return confirm('Are you sure you want to unarchive this order?');\" class=\"text-green-600 hover:text-green-900\">Unarchive</a>";
                } else {
                    echo "<a href=\"daftar_fo?archive_id=" . htmlspecialchars($order['id']) . "\" onclick=\"return confirm('Are you sure you want to archive this order?');\" class=\"text-red-600 hover:text-red-900\">Archive</a>";
                }
            echo "</td>";
        }
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

    <!-- Success Modal -->
    <div id="successModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50 hidden">
        <div class="bg-white rounded-lg shadow-lg p-6 w-11/12 max-w-md">
            <h3 class="text-lg font-semibold mb-2">notif</h3>
            <p id="successModalMessage" class="mb-4 text-gray-700"></p>
            <div class="flex justify-end">
                <button id="successModalClose" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-md">OK</button>
            </div>
        </div>
    </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tableContainer = document.querySelector('.table-container');

    if (tableContainer) {
        function updateShadows() {
            const scrollLeft = tableContainer.scrollLeft;
            const scrollWidth = tableContainer.scrollWidth;
            const clientWidth = tableContainer.clientWidth;
            const threshold = 15;

            if (scrollWidth > clientWidth) {
                if (scrollLeft > threshold) {
                    tableContainer.classList.add('scrolling-left');
                } else {
                    tableContainer.classList.remove('scrolling-left');
                }

                if (scrollLeft < scrollWidth - clientWidth - threshold) {
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

<script>
    // Success modal behavior (show when server set window.__flashSuccess)
    (function(){
        const successModal = document.getElementById('successModal');
        const successModalMessage = document.getElementById('successModalMessage');
        const successModalClose = document.getElementById('successModalClose');

        function showSuccessModal(message) {
            if (message) successModalMessage.textContent = message;
            successModal.classList.remove('hidden');
            successModalClose && successModalClose.focus();
        }

        function hideSuccessModal(){
            successModal.classList.add('hidden');
        }

        // Trigger if server set the global
        if (window.__flashSuccess) {
            showSuccessModal(window.__flashSuccess);
        }

        successModalClose && successModalClose.addEventListener('click', function() {
            hideSuccessModal();
            window.location.href = 'daftar_fo';
        });
        successModal && successModal.addEventListener('click', function(event){ if (event.target === successModal) hideSuccessModal(); });
        document.addEventListener('keydown', function(event){ if (event.key === 'Escape' && !successModal.classList.contains('hidden')) hideSuccessModal(); });
    })();
</script>