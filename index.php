<?php
include 'config.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>home</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="scripts.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" integrity="sha512-Fo3rlrZj/k7ujTnHg4CGR2D7kSs0v4LLanw2qksYuRlEzO+tcaEPQogQ0KaoGN26/zrn20ImR1DfuLWnOo7aBA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>
<body class="bg-gray-100 text-gray-900 pt-24 px-8 pb-8 font-mono">

    <?php include 'navbar.php'; ?>

    <main class="container mx-auto mt-10">
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <a href="bikin_fo.php" class="block bg-white p-6 rounded-lg shadow-md hover:shadow-xl transition-shadow">
                <div class="text-center">
                    <i class="fa-solid fa-plus fa-5x mx-auto mb-4 text-blue-600"></i>
                    <h2 class="text-xl font-semibold mb-2">bikin FO baru</h2>
                </div>
            </a>

            <a href="daftar_fo.php" class="block bg-white p-6 rounded-lg shadow-md hover:shadow-xl transition-shadow">
                <div class="text-center">
                    <i class="fa-solid fa-file fa-5x mx-auto mb-4 text-blue-600"></i>
                    <h2 class="text-xl font-semibold mb-2">daftar FO</h2>
                </div>
            </a>

            <a href="daftar_customer.php" class="block bg-white p-6 rounded-lg shadow-md hover:shadow-xl transition-shadow">
                <div class="text-center">
                    <i class="fa-solid fa-users fa-5x mx-auto mb-4 text-blue-600"></i>
                    <h2 class="text-xl font-semibold mb-2">daftar customer</h2>
                </div>
            </a>

            <a href="daftar_spk.php" class="block bg-white p-6 rounded-lg shadow-md hover:shadow-xl transition-shadow">
                <div class="text-center">
                    <i class="fa-solid fa-briefcase fa-5x mx-auto mb-4 text-blue-600"></i>
                    <h2 class="text-xl font-semibold mb-2">daftar SPK</h2>
                </div>
            </a>
        </div>
        <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <a href="daftar_fo.php" class="block bg-white p-6 rounded-lg shadow-md hover:shadow-xl transition-shadow">
                <div class="text-center">
                    <i class="fa-solid fa-list-ol fa-5x mx-auto mb-4 text-blue-600"></i>
                    <h2 class="text-xl font-semibold mb-2">daftar FO</h2>
                </div>
            </a>
        </div>
        <?php endif; ?>
    </main>

</body>
</html>