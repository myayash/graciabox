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
  const jumlahLayer = document.getElementById("jumlah_layer");
  const logoSelect = document.getElementById("logo");
  const ukuranPoly = document.getElementById("ukuran_poly");

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
      // Restore keyboard focusability and visual state
      dropdown.removeAttribute("tabindex");
      dropdown.removeAttribute("aria-disabled");
      dropdown.style.backgroundColor = "#ffffff";
      dropdown.style.pointerEvents = "auto";

      if (options.includes(currentlySelected)) {
        dropdown.value = currentlySelected;
      }
    } else {
      dropdown.disabled = true;
      // Prevent keyboard focus and gray out when there are no options
      dropdown.setAttribute("tabindex", "-1");
      dropdown.setAttribute("aria-disabled", "true");
      dropdown.style.backgroundColor = "#f3f4f6";
      dropdown.style.pointerEvents = "none";
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
          if (jenisDropdown) {
            jenisDropdown.disabled = true;
            jenisDropdown.setAttribute("tabindex", "-1");
            jenisDropdown.setAttribute("aria-disabled", "true");
            jenisDropdown.style.backgroundColor = "#f3f4f6";
            jenisDropdown.style.pointerEvents = "none";
          }
          if (warnaDropdown) {
            warnaDropdown.disabled = true;
            warnaDropdown.setAttribute("tabindex", "-1");
            warnaDropdown.setAttribute("aria-disabled", "true");
            warnaDropdown.style.backgroundColor = "#f3f4f6";
            warnaDropdown.style.pointerEvents = "none";
          }
          if (gsmDropdown) {
            gsmDropdown.disabled = true;
            gsmDropdown.setAttribute("tabindex", "-1");
            gsmDropdown.setAttribute("aria-disabled", "true");
            gsmDropdown.style.backgroundColor = "#f3f4f6";
            gsmDropdown.style.pointerEvents = "none";
          }
          if (ukuranDropdown) {
            ukuranDropdown.disabled = true;
            ukuranDropdown.setAttribute("tabindex", "-1");
            ukuranDropdown.setAttribute("aria-disabled", "true");
            ukuranDropdown.style.backgroundColor = "#f3f4f6";
            ukuranDropdown.style.pointerEvents = "none";
          }
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
        if (jenisDropdown) {
          jenisDropdown.disabled = true;
          jenisDropdown.setAttribute("tabindex", "-1");
          jenisDropdown.setAttribute("aria-disabled", "true");
          jenisDropdown.style.backgroundColor = "#f3f4f6";
          jenisDropdown.style.pointerEvents = "none";
        }
        if (warnaDropdown) {
          warnaDropdown.disabled = true;
          warnaDropdown.setAttribute("tabindex", "-1");
          warnaDropdown.setAttribute("aria-disabled", "true");
          warnaDropdown.style.backgroundColor = "#f3f4f6";
          warnaDropdown.style.pointerEvents = "none";
        }
        if (gsmDropdown) {
          gsmDropdown.disabled = true;
          gsmDropdown.setAttribute("tabindex", "-1");
          gsmDropdown.setAttribute("aria-disabled", "true");
          gsmDropdown.style.backgroundColor = "#f3f4f6";
          gsmDropdown.style.pointerEvents = "none";
        }
        if (ukuranDropdown) {
          ukuranDropdown.disabled = true;
          ukuranDropdown.setAttribute("tabindex", "-1");
          ukuranDropdown.setAttribute("aria-disabled", "true");
          ukuranDropdown.style.backgroundColor = "#f3f4f6";
          ukuranDropdown.style.pointerEvents = "none";
        }
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
        // If the user explicitly selected "Tidak ada", disable + gray out the related selects
        if (this.value === "Tidak ada") {
          clears.forEach((c) => {
            c.value = "";
            c.disabled = true;
            c.style.backgroundColor = "#f3f4f6";
            c.style.pointerEvents = "none";
            // make unfocusable by keyboard
            try {
              c.setAttribute("tabindex", "-1");
            } catch (e) {}
            try {
              c.setAttribute("aria-disabled", "true");
            } catch (e) {}
            Array.from(c.options).forEach((opt) => {
              opt.style.backgroundColor = "#f3f4f6";
              opt.style.color = "#9ca3af";
            });
          });
          return; // don't fetch options for 'Tidak ada'
        }

        // Otherwise just clear values and let the fetch populate/enabled them
        clears.forEach((c) => {
          c.value = "";
          // while fetching, keep them unfocusable until populateDropdown decides
          c.disabled = true;
          try {
            c.setAttribute("tabindex", "-1");
          } catch (e) {}
          try {
            c.setAttribute("aria-disabled", "true");
          } catch (e) {}
          c.style.pointerEvents = "none";
          c.style.backgroundColor = "#f3f4f6";
        });
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
      // If the supplier is explicitly set to 'Tidak ada', disable/gray related selects
      if (this.value === "Tidak ada") {
        if (coverLuarJenis) {
          coverLuarJenis.value = "";
          coverLuarJenis.disabled = true;
          coverLuarJenis.style.backgroundColor = "#f3f4f6";
          coverLuarJenis.style.pointerEvents = "none";
          coverLuarJenis.setAttribute("tabindex", "-1");
          coverLuarJenis.setAttribute("aria-disabled", "true");
          Array.from(coverLuarJenis.options).forEach((opt) => {
            opt.style.backgroundColor = "#f3f4f6";
            opt.style.color = "#9ca3af";
          });
        }
        if (coverLuarWarna) {
          coverLuarWarna.value = "";
          coverLuarWarna.disabled = true;
          coverLuarWarna.style.backgroundColor = "#f3f4f6";
          coverLuarWarna.style.pointerEvents = "none";
          coverLuarWarna.setAttribute("tabindex", "-1");
          coverLuarWarna.setAttribute("aria-disabled", "true");
          Array.from(coverLuarWarna.options).forEach((opt) => {
            opt.style.backgroundColor = "#f3f4f6";
            opt.style.color = "#9ca3af";
          });
        }
        if (coverLuarGsm) {
          coverLuarGsm.value = "";
          coverLuarGsm.disabled = true;
          coverLuarGsm.style.backgroundColor = "#f3f4f6";
          coverLuarGsm.style.pointerEvents = "none";
          coverLuarGsm.setAttribute("tabindex", "-1");
          coverLuarGsm.setAttribute("aria-disabled", "true");
          Array.from(coverLuarGsm.options).forEach((opt) => {
            opt.style.backgroundColor = "#f3f4f6";
            opt.style.color = "#9ca3af";
          });
        }
        if (coverLuarUkuran) {
          coverLuarUkuran.value = "";
          coverLuarUkuran.disabled = true;
          coverLuarUkuran.style.backgroundColor = "#f3f4f6";
          coverLuarUkuran.style.pointerEvents = "none";
          coverLuarUkuran.setAttribute("tabindex", "-1");
          coverLuarUkuran.setAttribute("aria-disabled", "true");
          Array.from(coverLuarUkuran.options).forEach((opt) => {
            opt.style.backgroundColor = "#f3f4f6";
            opt.style.color = "#9ca3af";
          });
        }
        return;
      }
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
          if (this.value === "Tidak ada") {
            clears.forEach((c) => {
              c.value = "";
              c.disabled = true;
              c.style.backgroundColor = "#f3f4f6";
              c.style.pointerEvents = "none";
              // remove from tab order and mark aria-disabled
              try {
                c.setAttribute("tabindex", "-1");
              } catch (e) {}
              try {
                c.setAttribute("aria-disabled", "true");
              } catch (e) {}
              Array.from(c.options).forEach((opt) => {
                opt.style.backgroundColor = "#f3f4f6";
                opt.style.color = "#9ca3af";
              });
            });
            return;
          }

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
      // If user selected explicit 'Tidak ada', disable and gray the jumlah_layer input
      if (this.value === "Tidak ada") {
        if (jumlahLayer) {
          jumlahLayer.value = "";
          jumlahLayer.disabled = true;
          jumlahLayer.style.backgroundColor = "#f3f4f6";
          jumlahLayer.style.pointerEvents = "none";
          jumlahLayer.setAttribute("tabindex", "-1");
          jumlahLayer.setAttribute("aria-disabled", "true");
        }
      } else {
        if (jumlahLayer) {
          jumlahLayer.disabled = false;
          jumlahLayer.style.backgroundColor = "#ffffff";
          jumlahLayer.style.pointerEvents = "auto";
          jumlahLayer.removeAttribute("tabindex");
          jumlahLayer.removeAttribute("aria-disabled");
        }
      }

      if (dudukanJenis) dudukanJenis.value = "";
      if (dudukanWarna) dudukanWarna.value = "";
      if (dudukanGsm) dudukanGsm.value = "";
      if (dudukanUkuran) dudukanUkuran.value = "";
      updateKertasOptions("dudukan");
    });

    // initial check on load
    if (dudukanSupplier.value === "Tidak ada" && jumlahLayer) {
      jumlahLayer.value = "";
      jumlahLayer.disabled = true;
      jumlahLayer.style.backgroundColor = "#f3f4f6";
      jumlahLayer.style.pointerEvents = "none";
      jumlahLayer.setAttribute("tabindex", "-1");
      jumlahLayer.setAttribute("aria-disabled", "true");
    }
  }

  // Initial call to set up dropdowns if a supplier is pre-selected
  // Initialize all supplier rows that already have a selected supplier
  allSupplierSelects.forEach((s) => {
    if (s && s.value) {
      updateKertasOptionsForRow(s);
    }
  });

  // Handle logo -> ukuran_poly interactivity: populate, disable/gray ukuran_poly when logo is 'Tidak ada'
  if (logoSelect) {
    function updateUkuranPolyForLogo(logo) {
      if (!ukuranPoly) return;
      if (!logo || logo === "Tidak ada") {
        // clear and disable
        ukuranPoly.innerHTML =
          '<option value="" disabled selected>Pilih Ukuran Poly</option>';
        ukuranPoly.value = "";
        ukuranPoly.disabled = true;
        ukuranPoly.style.backgroundColor = "#f3f4f6";
        ukuranPoly.style.pointerEvents = "none";
        ukuranPoly.setAttribute("tabindex", "-1");
        ukuranPoly.setAttribute("aria-disabled", "true");
        return;
      }

      // fetch options for the selected logo
      fetch(`get_logo_uk_poly_options.php?logo=${encodeURIComponent(logo)}`)
        .then((res) => {
          if (!res.ok) throw new Error("Network error");
          return res.json();
        })
        .then((data) => {
          // data.uk_poly should be an array
          populateDropdown(ukuranPoly, data.uk_poly || [], "Pilih Ukuran Poly");
        })
        .catch((err) => {
          console.error("Error fetching logo uk poly options:", err);
          // fallback: disable
          ukuranPoly.innerHTML =
            '<option value="" disabled selected>Pilih Ukuran Poly</option>';
          ukuranPoly.disabled = true;
          ukuranPoly.setAttribute("tabindex", "-1");
          ukuranPoly.setAttribute("aria-disabled", "true");
          ukuranPoly.style.backgroundColor = "#f3f4f6";
          ukuranPoly.style.pointerEvents = "none";
        });
    }

    logoSelect.addEventListener("change", function () {
      updateUkuranPolyForLogo(this.value);
    });

    // initial check on load: populate or disable
    updateUkuranPolyForLogo(logoSelect.value);
  }

  const aksesorisJenis = document.getElementById("aksesoris_jenis");
  const aksesorisUkuran = document.getElementById("aksesoris_ukuran");
  const aksesorisWarna = document.getElementById("aksesoris_warna");

  if (aksesorisJenis) {
    aksesorisJenis.addEventListener("change", function () {
      if (this.value === "Tidak ada") {
        aksesorisUkuran.disabled = true;
        aksesorisWarna.disabled = true;
        aksesorisUkuran.style.backgroundColor = "#f3f4f6";
        aksesorisWarna.style.backgroundColor = "#f3f4f6";
        aksesorisUkuran.style.pointerEvents = "none";
        aksesorisWarna.style.pointerEvents = "none";
        // make unfocusable by keyboard
        aksesorisUkuran.setAttribute("tabindex", "-1");
        aksesorisWarna.setAttribute("tabindex", "-1");
        aksesorisUkuran.setAttribute("aria-disabled", "true");
        aksesorisWarna.setAttribute("aria-disabled", "true");
        // Style all options
        Array.from(aksesorisUkuran.options).forEach((option) => {
          option.style.backgroundColor = "#f3f4f6";
          option.style.color = "#9ca3af";
        });
        Array.from(aksesorisWarna.options).forEach((option) => {
          option.style.backgroundColor = "#f3f4f6";
          option.style.color = "#9ca3af";
        });
        // Reset values
        aksesorisUkuran.value = "";
        aksesorisWarna.value = "";
      } else {
        aksesorisUkuran.disabled = false;
        aksesorisWarna.disabled = false;
        aksesorisUkuran.style.backgroundColor = "#ffffff";
        aksesorisWarna.style.backgroundColor = "#ffffff";
        aksesorisUkuran.style.pointerEvents = "auto";
        aksesorisWarna.style.pointerEvents = "auto";
        // Restore keyboard focusability
        aksesorisUkuran.removeAttribute("tabindex");
        aksesorisWarna.removeAttribute("tabindex");
        aksesorisUkuran.removeAttribute("aria-disabled");
        aksesorisWarna.removeAttribute("aria-disabled");
        // Reset option styles
        Array.from(aksesorisUkuran.options).forEach((option) => {
          option.style.backgroundColor = "#ffffff";
          option.style.color = "#1f2937";
        });
        Array.from(aksesorisWarna.options).forEach((option) => {
          option.style.backgroundColor = "#ffffff";
          option.style.color = "#1f2937";
        });
      }
    });

    // Initial check on page load
    if (aksesorisJenis.value === "Tidak ada") {
      aksesorisUkuran.disabled = true;
      aksesorisWarna.disabled = true;
      aksesorisUkuran.style.backgroundColor = "#f3f4f6";
      aksesorisWarna.style.backgroundColor = "#f3f4f6";
      aksesorisUkuran.style.pointerEvents = "none";
      aksesorisWarna.style.pointerEvents = "none";
      aksesorisUkuran.setAttribute("tabindex", "-1");
      aksesorisWarna.setAttribute("tabindex", "-1");
      aksesorisUkuran.setAttribute("aria-disabled", "true");
      aksesorisWarna.setAttribute("aria-disabled", "true");
      // Style all options
      Array.from(aksesorisUkuran.options).forEach((option) => {
        option.style.backgroundColor = "#f3f4f6";
        option.style.color = "#9ca3af";
      });
      Array.from(aksesorisWarna.options).forEach((option) => {
        option.style.backgroundColor = "#f3f4f6";
        option.style.color = "#9ca3af";
      });
      aksesorisUkuran.value = "";
      aksesorisWarna.value = "";
    }
  }
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
