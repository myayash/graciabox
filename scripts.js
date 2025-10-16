let openDropdown = null;

function toggleDropdown(id) {
    const dropdown = document.getElementById(id);
    const button = dropdown.previousElementSibling; // Assuming the button is the previous sibling

    if (openDropdown && openDropdown !== dropdown) {
        openDropdown.style.display = 'none';
    }

    if (window.getComputedStyle(dropdown).display === 'none') {
        dropdown.style.display = 'block';
        openDropdown = dropdown;
    } else {
        dropdown.style.display = 'none';
        openDropdown = null;
    }
}

document.addEventListener('click', function(event) {
    if (openDropdown) {
        const button = openDropdown.previousElementSibling;
        if (!openDropdown.contains(event.target) && !button.contains(event.target)) {
            openDropdown.style.display = 'none';
            openDropdown = null;
        }
    }
});

document.addEventListener('DOMContentLoaded', function() {
    const supplierDropdown = document.getElementById('cover_dalam_supplier');
    const jenisDropdown = document.getElementById('cover_dalam_jenis');
    const warnaDropdown = document.getElementById('cover_dalam_warna');
    const gsmDropdown = document.getElementById('cover_dalam_gsm');
    const ukuranDropdown = document.getElementById('cover_dalam_ukuran');

    function populateDropdown(dropdown, data) {
        dropdown.innerHTML = '<option value="" disabled selected>Pilih ' + dropdown.id.split('_')[2] + '</option>';
        if (data && data.length > 0) {
            data.forEach(item => {
                const option = document.createElement('option');
                option.value = item;
                option.textContent = item;
                dropdown.appendChild(option);
            });
            dropdown.disabled = false;
        } else {
            dropdown.disabled = true;
        }
    }

    async function fetchKertasOptions(targetField, supplier = '', jenis = '', warna = '', gsm = '') {
        let url = `get_kertas_filtered_options.php?target_field=${targetField}`;
        if (supplier) url += `&supplier=${supplier}`;
        if (jenis) url += `&jenis=${jenis}`;
        if (warna) url += `&warna=${warna}`;
        if (gsm) url += `&gsm=${gsm}`;

        const response = await fetch(url);
        return await response.json();
    }

    supplierDropdown.addEventListener('change', async function() {
        const selectedSupplier = this.value;
        jenisDropdown.disabled = true;
        warnaDropdown.disabled = true;
        gsmDropdown.disabled = true;
        ukuranDropdown.disabled = true;

        populateDropdown(jenisDropdown, null);
        populateDropdown(warnaDropdown, null);
        populateDropdown(gsmDropdown, null);
        populateDropdown(ukuranDropdown, null);

        if (selectedSupplier) {
            const jenisOptions = await fetchKertasOptions('jenis', selectedSupplier);
            populateDropdown(jenisDropdown, jenisOptions);
        }
    });

    jenisDropdown.addEventListener('change', async function() {
        const selectedSupplier = supplierDropdown.value;
        const selectedJenis = this.value;
        warnaDropdown.disabled = true;
        gsmDropdown.disabled = true;
        ukuranDropdown.disabled = true;

        populateDropdown(warnaDropdown, null);
        populateDropdown(gsmDropdown, null);
        populateDropdown(ukuranDropdown, null);

        if (selectedSupplier && selectedJenis) {
            const warnaOptions = await fetchKertasOptions('warna', selectedSupplier, selectedJenis);
            populateDropdown(warnaDropdown, warnaOptions);
        }
    });

    warnaDropdown.addEventListener('change', async function() {
        const selectedSupplier = supplierDropdown.value;
        const selectedJenis = jenisDropdown.value;
        const selectedWarna = this.value;
        gsmDropdown.disabled = true;
ukuranDropdown.disabled = true;

        populateDropdown(gsmDropdown, null);
        populateDropdown(ukuranDropdown, null);

        if (selectedSupplier && selectedJenis && selectedWarna) {
            const gsmOptions = await fetchKertasOptions('gsm', selectedSupplier, selectedJenis, selectedWarna);
            populateDropdown(gsmDropdown, gsmOptions);
        }
    });

    gsmDropdown.addEventListener('change', async function() {
        const selectedSupplier = supplierDropdown.value;
        const selectedJenis = jenisDropdown.value;
        const selectedWarna = warnaDropdown.value;
        const selectedGsm = this.value;
        ukuranDropdown.disabled = true;

        populateDropdown(ukuranDropdown, null);

        if (selectedSupplier && selectedJenis && selectedWarna && selectedGsm) {
            const ukuranOptions = await fetchKertasOptions('ukuran', selectedSupplier, selectedJenis, selectedWarna, selectedGsm);
            populateDropdown(ukuranDropdown, ukuranOptions);
        }
    });
});