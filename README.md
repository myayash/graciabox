# GRACIA INTERNAL PORTAL 27.10-alpha.1

## Deskripsi:

- Internal portal sistem informasi Gracia Box, terutama untuk manajemen FO

## Status:

- Release 27 Oktober 2025 Alpha
- Internal environment

## Fitur:

1. Pembuatan FO
2. Manajemen data customer, FO, barang, bahan, dan sejenisnya.
3. Feedback (testing only)

_Issue (masih dalam debugging):_

1. Image rendering in PDF

## Fitur upcoming:

- Absensi

## TUTORIAL:

### A) LOGIN

1. **username:** tester; **password:** tester
2. Isi data (Tabel Data -> Isi tabel customer, barang, model box, dll (kecuali SPK) sebagai data dan dipilih saat pembuatan FO)

### A.2) Pembuatan FO

1. Daftar FO -> + Order -> Isi semua isian (jika ada yang kosong, isi di Tabel Data (di Navigation Bar atas)) -> Save
2. FO yang disimpan bisa dilihat di Daftar FO (Navigation Bar atas); bisa di-drag ke kanan
3. Fitur lain: Export ke CSV (excel); Arsip data FO yang tidak diinginkan (check data yang diarsip di Archived Orders)
4. Untuk kembali melihat data yang tidak diarsip, klik Unarchived Orders
5. Cari FO (bisa ketik nama customer, id, tanggal, tanggal dibuat, sales yang membuat FO, dll. yang ada di kolom daftar)

### A.3) Pengeditan FO

1. Daftar FO -> ujung kanan tabel -> Edit

### A.4) Print / Download/ Save FO

1. Daftar FO -> pilih data yang diinginkan -> klik View (ujung kanan)

### B) Daftar SPK

1. Tabel Data (Navigation Bar di atas) -> Tabel SPK Dudukan atau Tabel SPK Logo
2. Untuk berpindah di antara daftar SPK Dudukan dan Logo, klik tombol SPK Logo / SPK Dudukan (ujung kanan atas; tergantung di halaman SPK apa)

_\*Daftar SPK diambil otomatis dari data FO di Daftar FO (Navigation Bar di atas)_

### B.2) Print / Download / Save SPK

1. Tabel Data -> Tabel SPK Dudukan atau Tabel SPK Logo -> klik View

### C) Feedback / Reporting

- Jika ada saran masukan bisa diketik dan disertakan gambarnya (opsional) di Feedback (Navigation Bar di atas)
- Klik Kirim Feedback

## Logging (internal alpha)

This project uses Monolog for application logging during internal alpha testing. Logs are written to the `logs/` directory and rotated daily (14-day retention by default).

Setup summary (already applied):

- `composer require monolog/monolog` (installed in the project)
- `lib/logger.php` provides `get_logger()` which returns a PSR-3 logger.
- `config.php` initializes the logger as `$logger = get_logger('gbox')` and registers global error/exception handlers.

How to test logging:

1. Trigger a test log from CLI:

```bash
cd /Applications/XAMPP/xamppfiles/htdocs/gbox-internal
php -r "require 'lib/logger.php'; \$l = get_logger('gbox'); \$l->info('manual test', ['from' => 'cli']);"
```

2. Check today's log file:

```bash
tail -n 200 logs/gbox-$(date +%F).log
```

3. Trigger errors from the web UI (e.g. submit feedback with oversized image) and then inspect the logs.

Permissions note:

- The `logs/` directory must be writable by the webserver user. For local dev you can run:

```bash
sudo chown -R _www:staff /Applications/XAMPP/xamppfiles/htdocs/gbox-internal/logs
sudo chmod -R 0755 /Applications/XAMPP/xamppfiles/htdocs/gbox-internal/logs
```

Security and housekeeping:

- Avoid logging sensitive PII. Redact or omit fields like passwords or full credit card numbers.
- Adjust retention in `lib/logger.php` (the RotatingFileHandler second argument controls days to keep).

#### Kontak:

- my4hya@gmail.com
- 085872375420 (Ayas)

#### NOTES:

- Internal alpha - do not share outside company
