function toggleDropdown(event, id) {
  event.stopPropagation();
  const dropdown = document.getElementById(id);
  if (dropdown) {
    if (dropdown.style.display === "block") {
      dropdown.style.display = "none";
    } else {
      dropdown.style.display = "block";
    }
  }
}

document.addEventListener("DOMContentLoaded", function () {
  const coverDalamSupplier = document.getElementById("cover_dalam_supplier");
  const coverDalamJenis = document.getElementById("cover_dalam_jenis");
  const coverDalamWarna = document.getElementById("cover_dalam_warna");
  const coverDalamGsm = document.getElementById("cover_dalam_gsm");
  const coverDalamUkuran = document.getElementById("cover_dalam_ukuran");

  const coverLuarSupplier = document.getElementById("cover_luar_supplier");
  const coverLuarJenis = document.getElementById("cover_luar_jenis");
  const coverLuarWarna = document.getElementById("cover_luar_warna");
  const coverLuarGsm = document.getElementById("cover_luar_gsm");
  const coverLuarUkuran = document.getElementById("cover_luar_ukuran");

  const boxSupplier = document.getElementById("box_supplier");
  const boxJenis = document.getElementById("box_jenis");
  const boxWarna = document.getElementById("box_warna");
  const boxGsm = document.getElementById("box_gsm");
  const boxUkuran = document.getElementById("box_ukuran");

  const dudukanSupplier = document.getElementById("dudukan_supplier");
  const dudukanJenis = document.getElementById("dudukan_jenis");
  const dudukanWarna = document.getElementById("dudukan_warna");
  const dudukanGsm = document.getElementById("dudukan_gsm");
  const dudukanUkuran = document.getElementById("dudukan_ukuran");

  function populateDropdown(dropdown, options, defaultText) {
    // Safety: if the dropdown element doesn't exist, do nothing
    if (!dropdown) return;

    const currentlySelected = dropdown.value;
    dropdown.innerHTML = `<option value="" disabled selected>${defaultText}</option>`;
    if (options && options.length > 0) {
      options.forEach((option) => {
        const opt = document.createElement("option");
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

    const selectedSupplier = supplierDropdown ? supplierDropdown.value : "";

    if (selectedSupplier) {
      fetch(
        `get_kertas_filtered_options.php?supplier=${encodeURIComponent(
          selectedSupplier
        )}`
      )
        .then((response) => {
          if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
          }
          return response.json();
        })
        .then((data) => {
          // Only populate dropdowns that exist on the page
          populateDropdown(jenisDropdown, data.jenis, "Jenis");
          populateDropdown(warnaDropdown, data.warna, "Warna");
          populateDropdown(gsmDropdown, data.gsm, "GSM");
          populateDropdown(ukuranDropdown, data.ukuran, "Ukuran");
        })
        .catch((error) => {
          console.error("Error fetching filtered kertas options:", error);
          if (jenisDropdown) jenisDropdown.disabled = true;
          if (warnaDropdown) warnaDropdown.disabled = true;
          if (gsmDropdown) gsmDropdown.disabled = true;
          if (ukuranDropdown) ukuranDropdown.disabled = true;
        });
    } else {
      populateDropdown(jenisDropdown, [], "Jenis");
      populateDropdown(warnaDropdown, [], "Warna");
      populateDropdown(gsmDropdown, [], "GSM");
      populateDropdown(ukuranDropdown, [], "Ukuran");
    }
  }

  // Update kertas options for a specific supplier select element's row
  function updateKertasOptionsForRow(supplierEl) {
    if (!supplierEl) return;
    const selectedSupplier = supplierEl.value;
    // Find the closest row that contains the supplier and related selects
    const row = supplierEl.closest("div.flex");
    // Use contains selectors so names with suffixes (e.g. _warna_luar) are matched
    const jenisDropdown = row
      ? row.querySelector('select[name*="_jenis"]')
      : null;
    const warnaDropdown = row
      ? row.querySelector('select[name*="_warna"]')
      : null;
    const gsmDropdown = row ? row.querySelector('select[name*="_gsm"]') : null;
    const ukuranDropdown = row
      ? row.querySelector('select[name*="_ukuran"]')
      : null;

    if (!selectedSupplier) {
      populateDropdown(jenisDropdown, [], "Jenis");
      populateDropdown(warnaDropdown, [], "Warna");
      populateDropdown(gsmDropdown, [], "GSM");
      populateDropdown(ukuranDropdown, [], "Ukuran");
      return;
    }

    fetch(
      `get_kertas_filtered_options.php?supplier=${encodeURIComponent(
        selectedSupplier
      )}`
    )
      .then((response) => {
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
      })
      .then((data) => {
        populateDropdown(jenisDropdown, data.jenis, "Jenis");
        populateDropdown(warnaDropdown, data.warna, "Warna");
        populateDropdown(gsmDropdown, data.gsm, "GSM");
        populateDropdown(ukuranDropdown, data.ukuran, "Ukuran");
      })
      .catch((error) => {
        console.error("Error fetching filtered kertas options for row:", error);
        if (jenisDropdown) jenisDropdown.disabled = true;
        if (warnaDropdown) warnaDropdown.disabled = true;
        if (gsmDropdown) gsmDropdown.disabled = true;
        if (ukuranDropdown) ukuranDropdown.disabled = true;
      });
  }

  // Attach change listeners to every supplier select on the page so the matching
  // 'Warna' (and other) dropdowns in the same row are populated/enabled.
  // Match any name that contains '_supplier' to support row-specific suffixes.
  const allSupplierSelects = document.querySelectorAll(
    'select[name*="_supplier"]'
  );
  allSupplierSelects.forEach((s) => {
    s.addEventListener("change", function () {
      // Clear any selects in the same row before fetching
      const row = s.closest("div.flex");
      if (row) {
        const clears = row.querySelectorAll(
          'select[name*="_jenis"], select[name*="_warna"], select[name*="_gsm"], select[name*="_ukuran"]'
        );
        clears.forEach((c) => (c.value = ""));
      }
      updateKertasOptionsForRow(s);
    });
  });

  // The code supports two styles:
  // 1) legacy single selects with IDs like cover_luar_supplier (kept for backward-compat)
  // 2) row-specific selects that include suffixes, e.g. cover_luar_supplier_luar, cover_luar_supplier_dlm
  // If the legacy ID exists, keep its handler. Otherwise, ensure any select whose name contains
  // the prefix '_supplier' will trigger the row-aware updater.
  if (coverLuarSupplier) {
    coverLuarSupplier.addEventListener("change", function () {
      if (coverLuarJenis) coverLuarJenis.value = "";
      if (coverLuarWarna) coverLuarWarna.value = "";
      if (coverLuarGsm) coverLuarGsm.value = "";
      if (coverLuarUkuran) coverLuarUkuran.value = "";
      updateKertasOptions("cover_luar");
    });
  } else {
    // No single ID; attach handlers to any supplier selects that belong to cover_luar rows
    const coverLuarRowSuppliers = document.querySelectorAll(
      'select[name^="cover_luar_supplier"]'
    );
    coverLuarRowSuppliers.forEach((s) => {
      s.addEventListener("change", function () {
        // clear the row's selects first
        const row = s.closest("div.flex");
        if (row) {
          const clears = row.querySelectorAll(
            'select[name*="_jenis"], select[name*="_warna"], select[name*="_gsm"], select[name*="_ukuran"]'
          );
          clears.forEach((c) => {
            c.value = "";
            c.disabled = true;
          });
        }
        // Use the row-aware updater
        updateKertasOptionsForRow(s);
      });
    });
  }

  if (boxSupplier) {
    boxSupplier.addEventListener("change", function () {
      if (boxJenis) boxJenis.value = "";
      if (boxWarna) boxWarna.value = "";
      if (boxGsm) boxGsm.value = "";
      if (boxUkuran) boxUkuran.value = "";
      updateKertasOptions("box");
    });
  }

  if (dudukanSupplier) {
    dudukanSupplier.addEventListener("change", function () {
      if (dudukanJenis) dudukanJenis.value = "";
      if (dudukanWarna) dudukanWarna.value = "";
      if (dudukanGsm) dudukanGsm.value = "";
      if (dudukanUkuran) dudukanUkuran.value = "";
      updateKertasOptions("dudukan");
    });
  }

  // Initial call to set up dropdowns if a supplier is pre-selected
  // Initialize all supplier rows that already have a selected supplier
  allSupplierSelects.forEach((s) => {
    if (s && s.value) {
      updateKertasOptionsForRow(s);
    }
  });
});

document.addEventListener("click", function (event) {
  const dropdowns = document.querySelectorAll(".dropdown-menu");
  dropdowns.forEach((dropdown) => {
    const toggleButton = dropdown.previousElementSibling; // Assuming the toggle button is the previous sibling
    if (
      dropdown.style.display === "block" &&
      !dropdown.contains(event.target) &&
      (!toggleButton || !toggleButton.contains(event.target))
    ) {
      dropdown.style.display = "none";
    }
  });
});
