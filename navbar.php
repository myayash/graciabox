<?php if (session_status() === PHP_SESSION_NONE) { session_start(); } ?>
<!-- Menu Bar (Top Navigation) -->
<nav class="bg-white text-gray-800 p-4 shadow-lg fixed w-full top-0 left-0 z-30">
    <div class="container mx-auto flex items-center">
        <div class="text-xl font-bold text-gray-800"><a href="index.php">gracia box form</a></div>
        <ul class="flex space-x-6 ml-6">
            <li class="relative">
                <button onclick="toggleDropdown(event, 'database-menu')" class="hover:bg-gray-200 px-3 py-2 dropdown-toggle">tabel data</button>
                <ul id="database-menu" class="absolute bg-white mt-2 shadow-lg dropdown-menu p-4 w-max" style="display: none; z-index: 50;">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <li><a href="daftar_customer.php" class="block px-4 py-2 hover:bg-gray-200">tabel customer</a></li>
                            <li><a href="daftar_barang.php" class="block px-4 py-2 hover:bg-gray-200">tabel barang</a></li>
                            <li><a href="daftar_model_box.php" class="block px-4 py-2 hover:bg-gray-200">tabel model box</a></li>
                            <li><a href="daftar_board.php" class="block px-4 py-2 hover:bg-gray-200">tabel board</a></li>
                            <li><a href="daftar_spk.php" class="block px-4 py-2 hover:bg-gray-200">tabel spk dudukan</a></li>
                          </div>
                          <div>
                            <li><a href="daftar_karyawan_sales.php" class="block px-4 py-2 hover:bg-gray-200">tabel karyawan sales</a></li>
                            <li><a href="daftar_aksesoris.php" class="block px-4 py-2 hover:bg-gray-200">tabel aksesoris</a></li>
                            <li><a href="daftar_dudukan.php" class="block px-4 py-2 hover:bg-gray-200">tabel dudukan</a></li>
                            <li><a href="daftar_kertas.php" class="block px-4 py-2 hover:bg-gray-200">tabel kertas</a></li>
                            <li><a href="daftar_spk_logo.php" class="block px-4 py-2 hover:bg-gray-200">tabel spk logo</a></li>
                        </div>
                    </div>
                </ul>
            </li>
            <li><button onclick="window.location.href='daftar_fo.php'" class="hover:bg-gray-200 px-3 py-2">daftar fo</button></li>
        </ul>
        <div class="ml-auto">
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="logout.php" class="hover:bg-gray-200 px-3 py-2">Logout (<?php echo htmlspecialchars($_SESSION['username']); ?>)</a>
            <?php else: ?>
                <a href="login.php" class="hover:bg-gray-200 px-3 py-2">Login</a>
            <?php endif; ?>
        </div>
    </div>
</nav>