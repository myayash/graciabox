function toggleDropdown(event, id) {
    event.stopPropagation();
    const dropdown = document.getElementById(id);
    if (dropdown) {
        dropdown.style.display = 'block'; // Always set to block for debugging
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const coverDalamSupplier = document.getElementById('cover_dalam_supplier');
    const coverDalamJenis = document.getElementById('cover_dalam_jenis');
    const coverDalamWarna = document.getElementById('cover_dalam_warna');
    const coverDalamGsm = document.getElementById('cover_dalam_gsm');
    const coverDalamUkuran = document.getElementById('cover_dalam_ukuran');

    const boxSupplier = document.getElementById('box_supplier');
    const boxJenis = document.getElementById('box_jenis');
    const boxWarna = document.getElementById('box_warna');
    const boxGsm = document.getElementById('box_gsm');
    const boxUkuran = document.getElementById('box_ukuran');

    const dudukanSupplier = document.getElementById('dudukan_supplier');
    const dudukanJenis = document.getElementById('dudukan_jenis');
    const dudukanWarna = document.getElementById('dudukan_warna');
    const dudukanGsm = document.getElementById('dudukan_gsm');
    const dudukanUkuran = document.getElementById('dudukan_ukuran');

    function populateDropdown(dropdown, options, defaultText) {
        const currentlySelected = dropdown.value;
        dropdown.innerHTML = `<option value="" disabled selected>${defaultText}</option>`;
        if (options && options.length > 0) {
            options.forEach(option => {
                const opt = document.createElement('option');
                opt.value = option;
                opt.textContent = option;
                dropdown.appendChild(opt);
            });
            dropdown.disabled = false;

            if (options.includes(currentlySelected)) {
                dropdown.value = currentlySelected;
            }
        } else {
            dropdown.disabled = true;
        }
    }

    function updateKertasOptions(prefix) {
        const supplierDropdown = document.getElementById(`${prefix}_supplier`);
        const jenisDropdown = document.getElementById(`${prefix}_jenis`);
        const warnaDropdown = document.getElementById(`${prefix}_warna`);
        const gsmDropdown = document.getElementById(`${prefix}_gsm`);
        const ukuranDropdown = document.getElementById(`${prefix}_ukuran`);

        const selectedSupplier = supplierDropdown.value;

        if (selectedSupplier) {
            fetch(`get_kertas_filtered_options.php?supplier=${encodeURIComponent(selectedSupplier)}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    populateDropdown(jenisDropdown, data.jenis, 'Jenis');
                    populateDropdown(warnaDropdown, data.warna, 'Warna');
                    populateDropdown(gsmDropdown, data.gsm, 'GSM');
                    populateDropdown(ukuranDropdown, data.ukuran, 'Ukuran');
                })
                .catch(error => {
                    console.error('Error fetching filtered kertas options:', error);
                    jenisDropdown.disabled = true;
                    warnaDropdown.disabled = true;
                    gsmDropdown.disabled = true;
                    ukuranDropdown.disabled = true;
                });
        } else {
            populateDropdown(jenisDropdown, [], 'Jenis');
            populateDropdown(warnaDropdown, [], 'Warna');
            populateDropdown(gsmDropdown, [], 'GSM');
            populateDropdown(ukuranDropdown, [], 'Ukuran');
        }
    }

    if (coverDalamSupplier) {
        coverDalamSupplier.addEventListener('change', function() {
            coverDalamJenis.value = '';
            coverDalamWarna.value = '';
            coverDalamGsm.value = '';
            coverDalamUkuran.value = '';
            updateKertasOptions('cover_dalam');
        });
    }

    if (boxSupplier) {
        boxSupplier.addEventListener('change', function() {
            boxJenis.value = '';
            boxWarna.value = '';
            boxGsm.value = '';
            boxUkuran.value = '';
            updateKertasOptions('box');
        });
    }

    if (dudukanSupplier) {
        dudukanSupplier.addEventListener('change', function() {
            dudukanJenis.value = '';
            dudukanWarna.value = '';
            dudukanGsm.value = '';
            dudukanUkuran.value = '';
            updateKertasOptions('dudukan');
        });
    }

    // Add event listener for cover_luar_supplier
    const coverLuarSupplier = document.getElementById('cover_luar_supplier');
    const coverLuarJenis = document.getElementById('cover_luar_jenis');
    const coverLuarWarna = document.getElementById('cover_luar_warna');
    const coverLuarGsm = document.getElementById('cover_luar_gsm');
    const coverLuarUkuran = document.getElementById('cover_luar_ukuran');

    if (coverLuarSupplier) {
        coverLuarSupplier.addEventListener('change', function() {
            coverLuarJenis.value = '';
            coverLuarWarna.value = '';
            coverLuarGsm.value = '';
            coverLuarUkuran.value = '';
            updateKertasOptions('cover_luar');
        });
    }

    // Initial call to set up dropdowns if a supplier is pre-selected
    if (coverDalamSupplier && coverDalamSupplier.value) {
        updateKertasOptions('cover_dalam');
    }

    if (boxSupplier && boxSupplier.value) {
        updateKertasOptions('box');
    }

    if (dudukanSupplier && dudukanSupplier.value) {
        updateKertasOptions('dudukan');
    }

    if (coverLuarSupplier && coverLuarSupplier.value) {
        updateKertasOptions('cover_luar');
    }
});

document.addEventListener('click', function(event) {
    const dropdowns = document.querySelectorAll('.dropdown-menu');
    dropdowns.forEach(dropdown => {
        const toggleButton = dropdown.previousElementSibling; // Assuming the toggle button is the previous sibling
        if (dropdown.style.display === 'block' && !dropdown.contains(event.target) && (!toggleButton || !toggleButton.contains(event.target))) {
            dropdown.style.display = 'none';
        }
    });
});