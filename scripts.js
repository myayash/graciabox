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

    function updateCoverDalamOptions() {
        const selectedSupplier = coverDalamSupplier.value;

        if (selectedSupplier) {
            fetch(`get_kertas_filtered_options.php?supplier=${encodeURIComponent(selectedSupplier)}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    populateDropdown(coverDalamJenis, data.jenis, 'Jenis');
                    populateDropdown(coverDalamWarna, data.warna, 'Warna');
                    populateDropdown(coverDalamGsm, data.gsm, 'GSM');
                    populateDropdown(coverDalamUkuran, data.ukuran, 'Ukuran');
                })
                .catch(error => {
                    console.error('Error fetching filtered kertas options:', error);
                    coverDalamJenis.disabled = true;
                    coverDalamWarna.disabled = true;
                    coverDalamGsm.disabled = true;
                    coverDalamUkuran.disabled = true;
                });
        } else {
            // If no supplier is selected, disable and clear all dependent dropdowns
            populateDropdown(coverDalamJenis, [], 'Jenis');
            populateDropdown(coverDalamWarna, [], 'Warna');
            populateDropdown(coverDalamGsm, [], 'GSM');
            populateDropdown(coverDalamUkuran, [], 'Ukuran');
        }
    }

    if (coverDalamSupplier) {
        coverDalamSupplier.addEventListener('change', function() {
            // When supplier changes, reset all subsequent dropdowns and update options
            coverDalamJenis.value = '';
            coverDalamWarna.value = '';
            coverDalamGsm.value = '';
            coverDalamUkuran.value = '';
            updateCoverDalamOptions();
        });
    }

    // Initial call to set up dropdowns if a supplier is pre-selected
    if (coverDalamSupplier && coverDalamSupplier.value) {
        updateCoverDalamOptions();
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