<!-- Menu Bar (Top Navigation) -->
<nav class="bg-white md:bg-white/90 md:backdrop-blur-md text-gray-800 px-4 py-3 shadow-sm fixed w-full top-0 left-0 z-30 border-b border-gray-200">
    <div class="container mx-auto flex items-center justify-between h-12">
        <div class="text-lg font-semibold text-gray-800"><a href="/gbox-deploy/" class="hover:text-blue-600 transition-colors duration-200">gracia box internal</a></div>

        <!-- Hamburger menu button -->
        <button id="mobile-menu-button" class="md:hidden p-2 rounded-md text-gray-600 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors duration-200">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
        </button>

        <!-- Desktop Menu -->
        <div class="hidden md:flex md:items-center md:w-auto">
            <ul class="flex md:flex-row md:space-x-4 lg:space-x-6">
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
            <li class="relative">
                <button onclick="toggleDropdown(event, 'database-menu')" class="flex items-center justify-between w-full md:w-auto text-gray-700 hover:bg-gray-100 px-3 py-2 rounded-md transition-colors duration-200 dropdown-toggle">
                    tabel data
                    <svg class="ml-2 w-4 h-4 transform transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </button>
                <ul id="database-menu" class="absolute bg-white mt-2 shadow-md rounded-lg dropdown-menu p-2 w-max" style="display: none; z-index: 50;">
                    <li><a href="daftar_customer" class="block px-3 py-2 hover:bg-gray-100 rounded-md transition-colors duration-200">tabel customer</a></li>
                    <li><a href="daftar_barang" class="block px-3 py-2 hover:bg-gray-100 rounded-md transition-colors duration-200">tabel barang</a></li>
                    <li><a href="daftar_model_box" class="block px-3 py-2 hover:bg-gray-100 rounded-md transition-colors duration-200">tabel model box</a></li>
                    <li><a href="daftar_board" class="block px-3 py-2 hover:bg-gray-100 rounded-md transition-colors duration-200">tabel board</a></li>
                    <li><a href="daftar_spk" class="block px-3 py-2 hover:bg-gray-100 rounded-md transition-colors duration-200">tabel spk dudukan</a></li>
                    <li><a href="daftar_karyawan_sales" class="block px-3 py-2 hover:bg-gray-100 rounded-md transition-colors duration-200">tabel karyawan sales</a></li>
                    <li><a href="daftar_aksesoris" class="block px-3 py-2 hover:bg-gray-100 rounded-md transition-colors duration-200">tabel aksesoris</a></li>
                    <li><a href="daftar_dudukan" class="block px-3 py-2 hover:bg-gray-100 rounded-md transition-colors duration-200">tabel dudukan</a></li>
                    <li><a href="daftar_kertas" class="block px-3 py-2 hover:bg-gray-100 rounded-md transition-colors duration-200">tabel kertas</a></li>
                    <li><a href="daftar_spk_logo" class="block px-3 py-2 hover:bg-gray-100 rounded-md transition-colors duration-200">tabel spk logo</a></li>
                </ul>
            </li>
            <?php endif; ?>
            <li class="relative">
                <button onclick="toggleDropdown(event, 'fo-menu')" class="flex items-center justify-between w-full md:w-auto text-gray-700 hover:bg-gray-100 px-3 py-2 rounded-md transition-colors duration-200 dropdown-toggle">
                    FO
                    <svg class="ml-2 w-4 h-4 transform transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </button>
                <ul id="fo-menu" class="absolute bg-white mt-2 shadow-md rounded-lg dropdown-menu p-2 w-max" style="display: none; z-index: 50;">
                    <li><a href="http://localhost/gbox-deploy/daftar_fo" class="block px-3 py-2 hover:bg-gray-100 rounded-md transition-colors duration-200">daftar fo</a></li>
                    <li><a href="http://localhost/gbox-deploy/bikin_fo" class="block px-3 py-2 hover:bg-gray-100 rounded-md transition-colors duration-200">bikin fo</a></li>
                </ul>
            </li>
        </ul>
        <div class="ml-auto flex md:flex-row space-x-4 lg:space-x-6">
            <?php if (isset($_SESSION['user_id'])): ?>
                <?php if (isset($_SESSION['username']) && $_SESSION['username'] === 'tester'): ?>
                    <a href="feedback" class="block text-gray-700 hover:bg-gray-100 px-3 py-2 rounded-md transition-colors duration-200 border-b border-gray-200 pb-4 mb-4 md:border-b-0 md:pb-0 md:mb-0">feedback</a>
                <?php endif; ?>
                <a href="index.php?page=logout" class="block text-gray-700 hover:bg-gray-100 px-3 py-2 rounded-md transition-colors duration-200">Logout (<?php echo htmlspecialchars($_SESSION['username']); ?>)</a>
            <?php else: ?>
                <a href="login" class="block text-gray-700 hover:bg-gray-100 px-3 py-2 rounded-md transition-colors duration-200">Login</a>
            <?php endif; ?>
        </div>
        </div> <!-- Close desktop menu -->

        <!-- Mobile Sidebar Overlay -->
        <div id="mobile-menu-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden"></div>

        <!-- Mobile Sidebar -->
        <div id="navbar-menu" class="fixed top-0 right-0 w-1/3 h-full bg-white shadow-lg z-50 transform translate-x-full transition-transform duration-300 ease-in-out md:hidden">
            <div class="p-4">
                <button id="mobile-menu-close-button" class="absolute top-4 right-4 p-2 rounded-md text-gray-600 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors duration-200">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
                <ul class="flex flex-col space-y-2 mt-10">
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                <li class="relative">
                    <button onclick="toggleDropdown(event, 'database-menu-mobile')" class="flex items-center justify-between w-full text-gray-700 hover:bg-gray-100 px-3 py-2 rounded-md transition-colors duration-200 dropdown-toggle">
                        tabel data
                        <svg class="ml-2 w-4 h-4 transform transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    <ul id="database-menu-mobile" class="bg-white mt-2 dropdown-menu p-2 w-full" style="display: none; z-index: 50;">
                        <li><a href="daftar_customer" class="block px-3 py-2 hover:bg-gray-100 rounded-md transition-colors duration-200">tabel customer</a></li>
                        <li><a href="daftar_barang" class="block px-3 py-2 hover:bg-gray-100 rounded-md transition-colors duration-200">tabel barang</a></li>
                        <li><a href="daftar_model_box" class="block px-3 py-2 hover:bg-gray-100 rounded-md transition-colors duration-200">tabel model box</a></li>
                        <li><a href="daftar_board" class="block px-3 py-2 hover:bg-gray-100 rounded-md transition-colors duration-200">tabel board</a></li>
                        <li><a href="daftar_spk" class="block px-3 py-2 hover:bg-gray-100 rounded-md transition-colors duration-200">tabel spk dudukan</a></li>
                        <li><a href="daftar_karyawan_sales" class="block px-3 py-2 hover:bg-gray-100 rounded-md transition-colors duration-200">tabel karyawan sales</a></li>
                        <li><a href="daftar_aksesoris" class="block px-3 py-2 hover:bg-gray-100 rounded-md transition-colors duration-200">tabel aksesoris</a></li>
                        <li><a href="daftar_dudukan" class="block px-3 py-2 hover:bg-gray-100 rounded-md transition-colors duration-200">tabel dudukan</a></li>
                        <li><a href="daftar_kertas" class="block px-3 py-2 hover:bg-gray-100 rounded-md transition-colors duration-200">tabel kertas</a></li>
                        <li><a href="daftar_spk_logo" class="block px-3 py-2 hover:bg-gray-100 rounded-md transition-colors duration-200">tabel spk logo</a></li>
                    </ul>
                </li>
                <?php endif; ?>
                <li class="border-t border-gray-200 pt-4 mt-4 relative">
                    <button onclick="toggleDropdown(event, 'fo-menu-mobile')" class="flex items-center justify-between w-full text-gray-700 hover:bg-gray-100 px-3 py-2 rounded-md transition-colors duration-200 dropdown-toggle">
                        FO
                        <svg class="ml-2 w-4 h-4 transform transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    <ul id="fo-menu-mobile" class="bg-white mt-2 dropdown-menu p-2 w-full" style="display: none; z-index: 50;">
                        <li><a href="http://localhost/gbox-deploy/daftar_fo" class="block px-3 py-2 hover:bg-gray-100 rounded-md transition-colors duration-200">daftar fo</a></li>
                        <li><a href="http://localhost/gbox-deploy/bikin_fo" class="block px-3 py-2 hover:bg-gray-100 rounded-md transition-colors duration-200">bikin fo</a></li>
                    </ul>
                </li>
                </ul>
                <div class="flex flex-col space-y-2 mt-4">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php if (isset($_SESSION['username']) && $_SESSION['username'] === 'tester'): ?>
                        <a href="feedback" class="block text-gray-700 hover:bg-gray-100 px-3 py-2 rounded-md transition-colors duration-200">feedback</a>
                    <?php endif; ?>
                    <a href="index.php?page=logout" class="block text-gray-700 hover:bg-gray-100 px-3 py-2 rounded-md transition-colors duration-200 border-t border-gray-200 pt-4 mt-4">Logout (<?php echo htmlspecialchars($_SESSION['username']); ?>)</a>
                <?php else: ?>
                    <a href="login" class="block text-gray-700 hover:bg-gray-100 px-3 py-2 rounded-md transition-colors duration-200">Login</a>
                <?php endif; ?>
                </div>
            </div>
        </div>
    </div> <!-- Close container -->
</nav>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const mobileMenuButton = document.getElementById('mobile-menu-button');
        const mobileMenuCloseButton = document.getElementById('mobile-menu-close-button');
        const navbarMenu = document.getElementById('navbar-menu'); // This is now the sidebar
        const mobileMenuOverlay = document.getElementById('mobile-menu-overlay');

        function openSidebar() {
            navbarMenu.classList.remove('translate-x-full');
            navbarMenu.classList.add('translate-x-0');
            mobileMenuOverlay.classList.remove('hidden');
        }

        function closeSidebar() {
            navbarMenu.classList.remove('translate-x-0');
            navbarMenu.classList.add('translate-x-full');
            mobileMenuOverlay.classList.add('hidden');
        }

        mobileMenuButton.addEventListener('click', openSidebar);
        mobileMenuCloseButton.addEventListener('click', closeSidebar);
        mobileMenuOverlay.addEventListener('click', closeSidebar);


        // Close dropdowns when clicking outside
        document.addEventListener('click', function (event) {
            if (!event.target.closest('.dropdown-toggle') && !event.target.closest('.dropdown-menu')) {
                const dropdowns = document.querySelectorAll('.dropdown-menu');
                dropdowns.forEach(dropdown => {
                    dropdown.style.display = 'none';
                });
            }
        });
    });

    function toggleDropdown(event, menuId) {
        event.stopPropagation(); // Prevent document click from immediately closing

        // Close all other dropdowns
        const allDropdowns = document.querySelectorAll('.dropdown-menu');
        allDropdowns.forEach(dropdown => {
            if (dropdown.id !== menuId) { // Don't close the current dropdown
                dropdown.style.display = 'none';
            }
        });

        const dropdown = document.getElementById(menuId);
        dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
    }
</script>