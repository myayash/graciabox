<?php
$is_admin = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');

// Handle archive action
if (isset($_GET['archive_id']) && !empty($_GET['archive_id'])) {
    if (!$is_admin) { // Only allow admin to archive
        header("Location: " . BASE_URL . "/daftar_customer");
        exit;
    }
    $archive_id = $_GET['archive_id'];
    try {
        $stmt = $pdo->prepare("UPDATE customer SET is_archived = 1 WHERE id = ?");
        $stmt->execute([$archive_id]);
        header("Location: " . BASE_URL . "/daftar_customer"); // Redirect to refresh the page
        exit;
    } catch (PDOException $e) {
        echo "<p>Error archiving customer: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}

// Handle unarchive action
if (isset($_GET['unarchive_id']) && !empty($_GET['unarchive_id'])) {
    if (!$is_admin) { // Only allow admin to unarchive
        header("Location: " . BASE_URL . "/daftar_customer");
        exit;
    }
    $unarchive_id = $_GET['unarchive_id'];
    try {
        $stmt = $pdo->prepare("UPDATE customer SET is_archived = 0 WHERE id = ?");
        $stmt->execute([$unarchive_id]);
        header("Location: " . BASE_URL . "/daftar_customer?show_archived=true"); // Redirect to refresh the page, staying on archived view
        exit;
    } catch (PDOException $e) {
        echo "<p>Error unarchiving customer: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}
?>
<h1 class="text-2xl font-bold mb-6 text-gray-800">daftar customer
    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
    <div class="inline-flex ml-4">
        <a href="bikin_customer" class="px-4 py-2 bg-blue-600 text-white font-bold hover:bg-blue-700 transition duration-150 ease-in-out">+ Customer</a>
        <?php if (isset($_GET['show_archived']) && $_GET['show_archived'] == 'true'): ?>
            <a href="daftar_customer" class="ml-2 px-4 py-2 bg-gray-600 text-white font-bold hover:bg-gray-700 transition duration-150 ease-in-out">Active Customers</a>
        <?php else: ?>
            <a href="daftar_customer?show_archived=true" class="ml-2 px-4 py-2 bg-gray-600 text-white font-bold hover:bg-gray-700 transition duration-150 ease-in-out">Archived Customers</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</h1>

<form action="daftar_customer" method="get" class="mb-4">
    <input type="text" name="search" placeholder="cari data customer" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" class="p-2 border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500">
    <button type="submit" class="ml-2 px-4 py-2 border border-blue-600 text-blue-600 font-bold hover:bg-blue-100 transition duration-150 ease-in-out">Search</button>
    <?php if (isset($_GET['show_archived'])):
        ?><input type="hidden" name="show_archived" value="<?php echo htmlspecialchars($_GET['show_archived']); ?>">
    <?php endif; ?>
</form>

<?php
try {
    $sql = "SELECT id, nama, perusahaan, no_telp, dibuat, is_archived FROM customer";
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
        $searchable_columns = ['id', 'nama', 'perusahaan', 'no_telp', 'dibuat'];
        
        $conditions[] = "(" . implode(" LIKE ? OR ", $searchable_columns) . " LIKE ?)";
        
        $params = array_merge($params, array_fill(0, count($searchable_columns), $searchTerm));
    }

    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(" AND ", $conditions);
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($customers) {
        echo "<div class=\"overflow-x-auto bg-white shadow-lg\">";
        echo "<table class=\"min-w-full divide-y divide-gray-200\">";
        echo "<thead><tr>";
        // Define a mapping for column names to display names
        $column_display_names = [
            'id' => 'ID',
            'nama' => 'Nama Customer',
            'perusahaan' => 'Perusahaan',
            'no_telp' => 'No. Telepon',
            'dibuat' => 'Dibuat',
            'is_archived' => 'Archived'
        ];

        // Dynamically create table headers from column names, using display names if available
        foreach (array_keys($customers[0]) as $columnName) {
            if ($columnName == 'is_archived') continue;
            echo "<th class=\"px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider\">" . htmlspecialchars($column_display_names[$columnName] ?? $columnName) . "</th>";
        }
        if ($is_admin) {
            echo "<th class=\"px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider\">Actions</th>";
        }
        echo "</tr></thead>";
        echo "<tbody class=\"bg-white divide-y divide-gray-200\">";
        // Populate table rows with data
        foreach ($customers as $customer) {
            echo "<tr>";
            foreach ($customer as $columnName => $value) {
                if ($columnName == 'is_archived') continue;
                echo "<td class=\"px-6 py-4 whitespace-nowrap text-sm text-gray-900\">" . htmlspecialchars($value) . "</td>";
            }
            if ($is_admin) {
                echo "<td class=\"px-6 py-4 whitespace-nowrap text-sm font-medium flex items-center space-x-2\">";
                echo "<a href=\"edit_customer?id=" . htmlspecialchars($customer['id']) . "\" class=\"text-indigo-600 hover:text-indigo-900\">Edit</a>";
                if (isset($_GET['show_archived']) && $_GET['show_archived'] == 'true') {
                    echo "<a href=\"daftar_customer?unarchive_id=" . htmlspecialchars($customer['id']) . "\" onclick=\"return confirm('Are you sure you want to unarchive this customer?');\" class=\"text-green-600 hover:text-green-900\">Unarchive</a>";
                } else {
                    echo "<a href=\"daftar_customer?archive_id=" . htmlspecialchars($customer['id']) . "\" onclick=\"return confirm('Are you sure you want to archive this customer?');\" class=\"text-red-600 hover:text-red-900\">Archive</a>";
                }
            }
            echo "</td>";
            echo "</tr>";
        }
        echo "</tbody>";
        echo "</table>";
        echo "</div>";
    } else {
        echo "<p class=\"mt-4 text-gray-600\">No customers found in the database.</p>";
    }
} catch (PDOException $e) {
    echo "<p class=\"mt-4 text-red-600\">Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>