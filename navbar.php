<!-- Menu Bar (Top Navigation) -->
<nav class="bg-white text-gray-800 p-4 shadow-lg fixed w-full top-0 left-0">
    <div class="container mx-auto flex items-center">
        <div class="text-xl font-bold text-gray-800"><a href="index.php">gracia box form</a></div>
        <ul class="flex space-x-6 ml-6">
            <li class="relative">
                <button onclick="toggleDropdown('database-menu')" class="hover:bg-gray-200 px-3 py-2">tabel data</button>
                <ul id="database-menu" class="absolute bg-white mt-2 shadow-lg" style="display: none;">
                    <li><a href="daftar_customer.php" class="block px-4 py-2 hover:bg-gray-200">tabel customer</a></li>
                    <li><a href="daftar_barang.php" class="block px-4 py-2 hover:bg-gray-200">tabel barang</a></li>
                    <li><a href="daftar_model_box.php" class="block px-4 py-2 hover:bg-gray-200">tabel model box</a></li>
                    <li><a href="daftar_board.php" class="block px-4 py-2 hover:bg-gray-200">tabel board</a></li>
                    <li><a href="daftar_kertas.php" class="block px-4 py-2 hover:bg-gray-200">tabel kertas</a></li>
                    <li><a href="daftar_karyawan_sales.php" class="block px-4 py-2 hover:bg-gray-200">tabel karyawan sales</a></li>
                </ul>
            </li>
            
        </ul>
    </div>
</nav>