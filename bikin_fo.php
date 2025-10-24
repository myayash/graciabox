<?php
include 'config.php';
session_start();

// Ensure CSRF token exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}

// Handle direct save: if form posted without from_shipping flag, process and save to DB
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['from_shipping'])) {
    // CSRF validation
    if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['flash_error'] = 'Invalid CSRF token. Please refresh the page and try again.';
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }
    
    $form_errors = [];
    // merge POST into a local array
    $post = $_POST;

    // Basic required checks
    if (empty($post['nama_customer'])) {
        $form_errors['nama_customer'] = 'Nama Customer is required.';
    }
    if (empty($post['kode_pisau'])) {
        $form_errors['kode_pisau'] = 'Kode Pisau is required.';
    }
    if (empty($post['jenis_board'])) {
        $form_errors['jenis_board'] = 'Jenis Board is required.';
    }

    if (!empty($post['nama_customer'])) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM customer WHERE nama = ?");
        $stmt->execute([$post['nama_customer']]);
        if ($stmt->fetchColumn() == 0) {
            $form_errors['nama_customer'] = 'Customer tidak ditemukan. Silakan pilih dari daftar atau buat customer baru.';
        }
    }

    // Kode pisau specific validation
    if (isset($post['kode_pisau']) && $post['kode_pisau'] === 'baru') {
        if (empty($post['model_box_baru'])) {
            $form_errors['model_box_baru'] = 'Model Box is required for new kode pisau.';
        }
        if (empty($post['length']) || empty($post['width']) || empty($post['height'])) {
            $form_errors['ukuran'] = 'Lenght, Width and Height are required for new kode pisau.';
        }
        if (empty($post['dibuat_oleh'])) {
            $form_errors['dibuat_oleh'] = 'Dibuat Oleh is required.';
        }
    } elseif (isset($post['kode_pisau']) && $post['kode_pisau'] === 'lama') {
        if (empty($post['barang_lama'])) {
            $form_errors['barang_lama'] = 'Barang must be selected for existing kode pisau.';
        }
        if (empty($post['dibuat_oleh'])) {
            $form_errors['dibuat_oleh'] = 'Dibuat Oleh is required.';
        }
    }

    // If there are validation errors, save and redirect back to form
    if (!empty($form_errors)) {
        $_SESSION['form_errors'] = $form_errors;
        $_SESSION['order_form'] = $post;
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }

    $temp_dudukan_img_data = []; // To store temporary filenames and extensions
    $upload_dir = 'uploads/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    if (isset($_FILES['dudukan_img']) && !empty(array_filter($_FILES['dudukan_img']['name']))) {
        $image_files = $_FILES['dudukan_img'];
        $file_count = count($image_files['name']);

        if ($file_count > 3) {
            $form_errors['dudukan_img'] = 'You can upload a maximum of 3 images.';
        } else {
            for ($i = 0; $i < $file_count; $i++) {
                if ($image_files['error'][$i] === UPLOAD_ERR_OK) {
                    $file_tmp_name = $image_files['tmp_name'][$i];
                    $file_name = $image_files['name'][$i];
                    $file_size = $image_files['size'][$i];
                    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                    $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];

                    if (in_array($file_ext, $allowed_ext)) {
                        if ($file_size <= 2097152) { // 2MB
                            $temp_file_name = uniqid('', true) . '.' . $file_ext; // Generate a temporary unique name
                            $destination = $upload_dir . $temp_file_name;
                            if (move_uploaded_file($file_tmp_name, $destination)) {
                                $temp_dudukan_img_data[] = ['temp_filename' => $temp_file_name, 'ext' => $file_ext];
                            } else {
                                $form_errors['dudukan_img'] = 'Failed to move uploaded file.';
                            }
                        } else {
                            $form_errors['dudukan_img'] = 'File size must be 2MB or less.';
                        }
                    } else {
                        $form_errors['dudukan_img'] = 'Invalid file type. Only JPG, JPEG, PNG, and GIF are allowed.';
                    }
                } elseif ($image_files['error'][$i] !== UPLOAD_ERR_NO_FILE) {
                    $form_errors['dudukan_img'] = 'Error uploading file: ' . $image_files['error'][$i];
                }
            }
        }
    }
    // Initialize $dudukan_img_str as empty for now, it will be updated after order insertion
    $dudukan_img_str = '';

    $temp_logo_img_data = []; // To store temporary filenames and extensions for logo images
    if (isset($_FILES['logo_img']) && !empty(array_filter($_FILES['logo_img']['name']))) {
        $image_files = $_FILES['logo_img'];
        $file_count = count($image_files['name']);

        if ($file_count > 3) {
            $form_errors['logo_img'] = 'You can upload a maximum of 3 logo images.';
        } else {
            for ($i = 0; $i < $file_count; $i++) {
                if ($image_files['error'][$i] === UPLOAD_ERR_OK) {
                    $file_tmp_name = $image_files['tmp_name'][$i];
                    $file_name = $image_files['name'][$i];
                    $file_size = $image_files['size'][$i];
                    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                    $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];

                    if (in_array($file_ext, $allowed_ext)) {
                        if ($file_size <= 2097152) { // 2MB
                            $temp_file_name = uniqid('', true) . '.' . $file_ext; // Generate a temporary unique name
                            $destination = $upload_dir . $temp_file_name;
                            if (move_uploaded_file($file_tmp_name, $destination)) {
                                $temp_logo_img_data[] = ['temp_filename' => $temp_file_name, 'ext' => $file_ext];
                            } else {
                                $form_errors['logo_img'] = 'Failed to move uploaded logo file.';
                            }
                        } else {
                            $form_errors['logo_img'] = 'Logo file size must be 2MB or less.';
                        }
                    } else {
                        $form_errors['logo_img'] = 'Invalid logo file type. Only JPG, JPEG, PNG, and GIF are allowed.';
                    }
                } elseif ($image_files['error'][$i] !== UPLOAD_ERR_NO_FILE) {
                    $form_errors['logo_img'] = 'Error uploading logo file: ' . $image_files['error'][$i];
                }
            }
        }
    }
    // Initialize $logo_img_str as empty for now, it will be updated after order insertion
    $logo_img_str = '';

    $temp_poly_img_data = []; // To store temporary filenames and extensions for poly images
    if (isset($_FILES['poly_img']) && !empty(array_filter($_FILES['poly_img']['name']))) {
        $image_files = $_FILES['poly_img'];
        $file_count = count($image_files['name']);

        if ($file_count > 3) {
            $form_errors['poly_img'] = 'You can upload a maximum of 3 poly images.';
        } else {
            for ($i = 0; $i < $file_count; $i++) {
                if ($image_files['error'][$i] === UPLOAD_ERR_OK) {
                    $file_tmp_name = $image_files['tmp_name'][$i];
                    $file_name = $image_files['name'][$i];
                    $file_size = $image_files['size'][$i];
                    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                    $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];

                    if (in_array($file_ext, $allowed_ext)) {
                        if ($file_size <= 2097152) { // 2MB
                            $temp_file_name = uniqid('', true) . '.' . $file_ext; // Generate a temporary unique name
                            $destination = $upload_dir . $temp_file_name;
                            if (move_uploaded_file($file_tmp_name, $destination)) {
                                $temp_poly_img_data[] = ['temp_filename' => $temp_file_name, 'ext' => $file_ext];
                            } else {
                                $form_errors['poly_img'] = 'Failed to move uploaded poly file.';
                            }
                        } else {
                            $form_errors['poly_img'] = 'Poly file size must be 2MB or less.';
                        }
                    } else {
                        $form_errors['poly_img'] = 'Invalid poly file type. Only JPG, JPEG, PNG, and GIF are allowed.';
                    }
                } elseif ($image_files['error'][$i] !== UPLOAD_ERR_NO_FILE) {
                    $form_errors['poly_img'] = 'Error uploading poly file: ' . $image_files['error'][$i];
                }
            }
        }
    }
    // Initialize $poly_img_str as empty for now, it will be updated after order insertion
    $poly_img_str = '';

    // If there are validation errors from logo file upload, save and redirect back to form
    if (!empty($form_errors['logo_img'])) {
        $_SESSION['form_errors'] = $form_errors;
        $_SESSION['order_form'] = $post;
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }

    // If there are validation errors from file upload, save and redirect back to form
    if (!empty($form_errors['dudukan_img'])) {
        $_SESSION['form_errors'] = $form_errors;
        $_SESSION['order_form'] = $post;
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }

    // Prepare values
    $nama = $post['nama_customer'];
    $kode_pisau = $post['kode_pisau'];
    $jenis_board = $post['jenis_board'];

    // Build cover_dlm: store only supplier and warna per table requirement
    // (Cover Dalam field below Jenis Board should populate these)
    $cover_dlm_supplier = $post['cover_dalam_supplier'] ?? '';
    $cover_dlm_warna = $post['cover_dalam_warna'] ?? '';
    // store only supplier and warna (e.g. "SupplierName - WarnaName")
    $cover_dlm = trim("{$cover_dlm_supplier} - {$cover_dlm_warna}", " -");

    // Build cover_luar values. Support row-specific inputs (Box Luar / Box Dalam)
    $cover_luar_radio = $post['cover_luar_radio'] ?? '';
    // row-specific (preferred)
    $cover_luar_supplier_luar = $post['cover_luar_supplier_luar'] ?? $post['cover_luar_supplier'] ?? '';
    $cover_luar_warna_luar = $post['cover_luar_warna_luar'] ?? $post['cover_luar_warna'] ?? '';
    $cover_luar_supplier_dlm = $post['cover_luar_supplier_dlm'] ?? '';
    $cover_luar_warna_dlm = $post['cover_luar_warna_dlm'] ?? '';
    // legacy/generic fields (kept for tolerance)
    $cover_luar_jenis = $post['cover_luar_jenis'] ?? '';
    $cover_luar_gsm = $post['cover_luar_gsm'] ?? '';
    $cover_luar_ukuran = $post['cover_luar_ukuran'] ?? '';

    // Determine label for cover luar based on selected model box (the dropdown labels change per model)
    // Resolve $model_box early so server can use model-specific labels when composing cover_lr
    $model_box = '';
    if (isset($post['kode_pisau']) && $post['kode_pisau'] === 'baru') {
        $model_box = $post['model_box_baru'] ?? '';
    } elseif (isset($post['kode_pisau']) && $post['kode_pisau'] === 'lama') {
        $barang_id_temp = $post['barang_lama'] ?? null;
        if ($barang_id_temp) {
            $stmtTmp = $pdo->prepare("SELECT model_box, ukuran, nama FROM barang WHERE id = ? LIMIT 1");
            $stmtTmp->execute([$barang_id_temp]);
            $brTmp = $stmtTmp->fetch(PDO::FETCH_ASSOC);
            if ($brTmp) {
                $model_box = $brTmp['model_box'] ?? '';
                // preload ukuran/nama_box_lama if available so later logic can reuse
                $ukuran = $brTmp['ukuran'] ?? ($ukuran ?? '');
                $nama_box_lama_value = $brTmp['nama'] ?? ($nama_box_lama_value ?? null);
            }
        }
    }

    $box_luar_label = 'Box Luar';
    $box_dlm_label = 'Box Dalam';
    if (!empty($model_box)) {
        // Try to lookup the model_box row to get the labels (box_luar / box_dlm)
        $stmt = $pdo->prepare("SELECT box_luar, box_dlm FROM model_box WHERE nama = ? LIMIT 1");
        $stmt->execute([$model_box]);
        $mbRow = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($mbRow) {
            if (!empty($mbRow['box_luar'])) $box_luar_label = $mbRow['box_luar'];
            if (!empty($mbRow['box_dlm'])) $box_dlm_label = $mbRow['box_dlm'];
        }
    }

    // Compose the cover_luar string using both rows (Box Luar and Box Dalam)
    $part_luar = trim("{$box_luar_label}: {$cover_luar_supplier_luar} - {$cover_luar_warna_luar}", " -");
    $part_dlm = trim("{$box_dlm_label}: {$cover_luar_supplier_dlm} - {$cover_luar_warna_dlm}", " -");
    $cover_luar_str = trim($part_luar . "\n" . $part_dlm, "\n ");

    if ($kode_pisau === 'baru') {
        // insert into barang
        $model_box = $post['model_box_baru'] ?? '';
        $ukuran = trim((($post['length'] ?? '') . ' x ' . ($post['width'] ?? '') . ' x ' . ($post['height'] ?? '')), ' x ');
        $stmt = $pdo->prepare("INSERT INTO barang (model_box, ukuran, nama) VALUES (?, ?, ?)");
        $stmt->execute([$model_box, $ukuran, $nama]);
        $barang_id = $pdo->lastInsertId();
    } else {
        $barang_id = $post['barang_lama'] ?? null;
        if ($barang_id) {
            $stmt = $pdo->prepare("SELECT * FROM barang WHERE id = ?");
            $stmt->execute([$barang_id]);
            $barang_row = $stmt->fetch(PDO::FETCH_ASSOC);
            $ukuran = $barang_row['ukuran'] ?? '';
            $model_box = $barang_row['model_box'] ?? '';
            $nama_box_lama_value = $barang_row['nama'] ?? null;
        }
    }

    // box string
    $box_supplier = $post['box_supplier'] ?? '';
    $box_jenis = $post['box_jenis'] ?? ($post['model_box_baru'] ?? '');
    $box_warna = $post['box_warna'] ?? '';
    $box_gsm = $post['box_gsm'] ?? '';
    $box_ukuran = $post['box_ukuran'] ?? ($ukuran ?? '');
    $box_str = trim("(box) {$box_supplier} - {$box_jenis} - {$box_warna} - {$box_gsm} - {$box_ukuran}", " -");

    // dudukan
    $dudukan_id = $post['dudukan'] ?? null;
    $dudukan_jenis = null;
    if ($dudukan_id) {
        $stmt = $pdo->prepare("SELECT jenis FROM dudukan WHERE id = ?");
        $stmt->execute([$dudukan_id]);
        $drow = $stmt->fetch(PDO::FETCH_ASSOC);
        $dudukan_jenis = $drow['jenis'] ?? null;
    } else {
        $dudukan_jenis = 'Tidak ada';
    }

    // other fields
    $sales_pj = $post['dibuat_oleh'] ?? null;
    $lokasi = $post['lokasi'] ?? null;
    $quantity = (isset($post['quantity']) && $post['quantity'] !== '') ? ($post['quantity'] . ' pcs') : null;
    $keterangan = $post['keterangan'] ?? null;
    // feedback customer
    $feedback_cust = $post['feedback_cust'] ?? null;
    $aksesoris = 'jenis:' . ($post['aksesoris_jenis'] ?? '') . ' - ukuran:' . ($post['aksesoris_ukuran'] ?? '') . ' - warna:' . ($post['aksesoris_warna'] ?? '');
    $ket_aksesoris = $post['ket_aksesoris'] ?? null;
    $jumlah_layer = (isset($post['jumlah_layer']) && is_numeric($post['jumlah_layer'])) ? (int)$post['jumlah_layer'] : null;
    $logo = !empty($post['logo']) ? $post['logo'] : 'Tidak ada';
    $ukuran_poly = $post['ukuran_poly'] ?? null;
    $lokasi_poly = $post['lokasi_poly'] ?? null;
    $klise = $post['klise'] ?? null;

    // shipping/payment fields (optional)
    $tanggal_kirim = $post['tanggal_kirim'] ?? null;
    $jam_kirim = $post['jam_kirim'] ?? null;
    $dikirim_dari = $post['dikirim_dari'] ?? null;
    $tujuan_kirim = $post['tujuan_kirim'] ?? null;
    $tanggal_dp = $post['tanggal_dp'] ?? null;
    $pelunasan = $post['pelunasan'] ?? null;
    $ongkir = $post['ongkir'] ?? null;
    $packing = $post['packing'] ?? null;
    // biaya
    $biaya = $post['biaya'] ?? null;

    // Store only the cover luar lines (Box Luar and Box Dalam) in cover_lr as requested
    $cover_lr = $cover_luar_str;

    // Insert into orders (include feedback_cust before keterangan)
    $stmt = $pdo->prepare("INSERT INTO orders (nama, kode_pisau, ukuran, model_box, jenis_board, cover_dlm, sales_pj, nama_box_lama, lokasi, quantity, feedback_cust, keterangan, cover_lr, aksesoris, ket_aksesoris, dudukan, dudukan_img, jumlah_layer, logo, logo_img, ukuran_poly, lokasi_poly, poly_img, klise, tanggal_kirim, jam_kirim, dikirim_dari, tujuan_kirim, tanggal_dp, pelunasan, ongkir, packing, biaya) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$nama, $kode_pisau, $box_ukuran, $box_jenis, $jenis_board, $cover_dlm, $sales_pj, $nama_box_lama_value ?? null, $lokasi, $quantity, $feedback_cust, $keterangan, $cover_lr, $aksesoris, $ket_aksesoris, $dudukan_jenis, $dudukan_img_str, $jumlah_layer, $logo, $logo_img_str, $ukuran_poly, $lokasi_poly, $poly_img_str, $klise, $tanggal_kirim, $jam_kirim, $dikirim_dari, $tujuan_kirim, $tanggal_dp, $pelunasan, $ongkir, $packing, $biaya]);

    $order_id = $pdo->lastInsertId(); // Get the ID of the newly inserted order

    $final_dudukan_img_filenames = [];
    if (!empty($temp_dudukan_img_data)) {
        // Sanitize nama and dibuat_oleh for filename
        $sanitized_nama = preg_replace('/[^a-zA-Z0-9_-]/', '', str_replace(' ', '-', $nama));

        // Fetch the 'dibuat' timestamp from the newly inserted order
        $stmt_dibuat = $pdo->prepare("SELECT dibuat FROM orders WHERE id = ?");
        $stmt_dibuat->execute([$order_id]);
        $order_data = $stmt_dibuat->fetch(PDO::FETCH_ASSOC);
        $dibuat_timestamp = $order_data['dibuat'] ?? date('Y-m-d H:i:s'); // Fallback to current time if not found

        // Sanitize the timestamp for filename: replace spaces and colons with hyphens
        $sanitized_dibuat = preg_replace('/[^a-zA-Z0-9_-]/', '', str_replace([' ', ':'], '-', $dibuat_timestamp));

        $file_index = 0; // Initialize file index for unique filenames
        foreach ($temp_dudukan_img_data as $file_data) {
            $temp_filename = $file_data['temp_filename'];
            $file_ext = $file_data['ext'];
            $file_index++; // Increment index for each file

            // Construct the new filename
            // Format: "dudukan" - id from project_form.orders, nama from project_form.orders - dibuat from project_form.orders - index
            $new_filename = "dudukan-{$order_id}-{$sanitized_nama}-{$sanitized_dibuat}-{$file_index}.{$file_ext}";
            $old_path = $upload_dir . $temp_filename;
            $new_path = $upload_dir . $new_filename;

            if (rename($old_path, $new_path)) {
                $final_dudukan_img_filenames[] = $new_filename;
            } else {
                // Handle error if rename fails, though unlikely if move_uploaded_file succeeded
                error_log("Failed to rename file from {$old_path} to {$new_path}");
                // Optionally, you might want to revert the order insertion or mark it as failed
            }
        }
        $dudukan_img_str = implode(',', $final_dudukan_img_filenames);

        // Update the dudukan_img column in the orders table with the new filenames
        $stmt_update_img = $pdo->prepare("UPDATE orders SET dudukan_img = ? WHERE id = ?");
        $stmt_update_img->execute([$dudukan_img_str, $order_id]);
    }

    $final_logo_img_filenames = [];
    if (!empty($temp_logo_img_data)) {
        // $sanitized_nama and $sanitized_dibuat are already available from dudukan_img processing

        $file_index = 0; // Initialize file index for unique filenames
        foreach ($temp_logo_img_data as $file_data) {
            $temp_filename = $file_data['temp_filename'];
            $file_ext = $file_data['ext'];
            $file_index++; // Increment index for each file

            // Construct the new filename
            // Format: "logo" - id from project_form.orders, nama from project_form.orders - dibuat from project_form.orders - index
            $new_filename = "logo-{$order_id}-{$sanitized_nama}-{$sanitized_dibuat}-{$file_index}.{$file_ext}";
            $old_path = $upload_dir . $temp_filename;
            $new_path = $upload_dir . $new_filename;

            if (rename($old_path, $new_path)) {
                $final_logo_img_filenames[] = $new_filename;
            } else {
                error_log("Failed to rename logo file from {$old_path} to {$new_path}");
            }
        }
        $logo_img_str = implode(',', $final_logo_img_filenames);

        // Update the logo_img column in the orders table with the new filenames
        $stmt_update_logo_img = $pdo->prepare("UPDATE orders SET logo_img = ? WHERE id = ?");
        $stmt_update_logo_img->execute([$logo_img_str, $order_id]);
    }

    $final_poly_img_filenames = [];
    if (!empty($temp_poly_img_data)) {
        // $sanitized_nama and $sanitized_dibuat are already available from dudukan_img processing

        $file_index = 0; // Initialize file index for unique filenames
        foreach ($temp_poly_img_data as $file_data) {
            $temp_filename = $file_data['temp_filename'];
            $file_ext = $file_data['ext'];
            $file_index++; // Increment index for each file

            // Construct the new filename
            // Format: "poly" - id from project_form.orders, nama from project_form.orders - dibuat from project_form.orders - index
            $new_filename = "poly-{$order_id}-{$sanitized_nama}-{$sanitized_dibuat}-{$file_index}.{$file_ext}";
            $old_path = $upload_dir . $temp_filename;
            $new_path = $upload_dir . $new_filename;

            if (rename($old_path, $new_path)) {
                $final_poly_img_filenames[] = $new_filename;
            } else {
                error_log("Failed to rename poly file from {$old_path} to {$new_path}");
            }
        }
        $poly_img_str = implode(',', $final_poly_img_filenames);

        // Update the poly_img column in the orders table with the new filenames
        $stmt_update_poly_img = $pdo->prepare("UPDATE orders SET poly_img = ? WHERE id = ?");
        $stmt_update_poly_img->execute([$poly_img_str, $order_id]);
    }

    // Optionally clear session order form
    unset($_SESSION['order_form']);
    $_SESSION['flash_success'] = 'FO SUKSES disimpan';
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

// Check if the user is logged in at all.
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Check if the user has the 'admin' role.
if ($_SESSION['role'] !== 'admin') {
    die('Access Denied: You do not have permission to create new orders.');
}

// Handle POST from shipping (back navigation)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['from_shipping'])) {
    $_SESSION['order_form'] = array_merge($_SESSION['order_form'] ?? [], $_POST);
}

// Fetch data for dropdowns
$order_form = $_SESSION['order_form'] ?? [];
$customers = $pdo->query("SELECT * FROM customer WHERE is_archived = 0")->fetchAll(PDO::FETCH_ASSOC);
$model_boxes = $pdo->query("SELECT * FROM model_box WHERE is_archived = 0")->fetchAll(PDO::FETCH_ASSOC);
$boards = $pdo->query("SELECT * FROM board WHERE is_archived = 0")->fetchAll(PDO::FETCH_ASSOC);
$distinct_suppliers = $pdo->query("SELECT DISTINCT supplier FROM kertas WHERE is_archived = 0")->fetchAll(PDO::FETCH_ASSOC);
$sales_reps = $pdo->query("SELECT * FROM empl_sales WHERE is_archived = 0")->fetchAll(PDO::FETCH_ASSOC);
$barangs = $pdo->query("SELECT * FROM barang WHERE is_archived = 0")->fetchAll(PDO::FETCH_ASSOC);
$aksesoris_jenis = $pdo->query("SELECT DISTINCT jenis FROM aksesoris")->fetchAll(PDO::FETCH_ASSOC);
$dudukan_options = $pdo->query("SELECT * FROM dudukan")->fetchAll(PDO::FETCH_ASSOC);
$logo_options = $pdo->query("SELECT DISTINCT jenis FROM logo")->fetchAll(PDO::FETCH_ASSOC);
$logo_uk_poly_options = $pdo->query("SELECT DISTINCT uk_poly FROM logo")->fetchAll(PDO::FETCH_ASSOC);

$aksesoris_ukuran_options = [];
$aksesoris_warna_options = [];
$aksesoris_dropdown_disabled = 'disabled';

if (!empty($order_form['aksesoris_jenis'])) {
    $jenis = $order_form['aksesoris_jenis'];
    $stmt_ukuran = $pdo->prepare("SELECT DISTINCT ukuran FROM aksesoris WHERE jenis = ? AND ukuran IS NOT NULL AND ukuran != '' ORDER BY ukuran ASC");
    $stmt_ukuran->execute([$jenis]);
    $aksesoris_ukuran_options = $stmt_ukuran->fetchAll(PDO::FETCH_COLUMN);

    $stmt_warna = $pdo->prepare("SELECT DISTINCT warna FROM aksesoris WHERE jenis = ? AND warna IS NOT NULL AND warna != '' ORDER BY warna ASC");
    $stmt_warna->execute([$jenis]);
    $aksesoris_warna_options = $stmt_warna->fetchAll(PDO::FETCH_COLUMN);
    
    if(count($aksesoris_ukuran_options) > 0 || count($aksesoris_warna_options) > 0) {
        $aksesoris_dropdown_disabled = '';
    }
}

// Fetch alamat_pengirim for shipping select (used when embedding shipping fields)
$alamat_pengirim = $pdo->query("SELECT * FROM alamat_pengirim")->fetchAll(PDO::FETCH_ASSOC);

// Prepare options for each prefix
$prefixes = ['cover_dalam', 'cover_luar', 'box', 'dudukan'];
$options = [];
foreach ($prefixes as $prefix) {
    $options[$prefix] = [
        'jenis' => [],
        'warna' => [],
        'gsm' => [],
        'ukuran' => [],
        'disabled' => 'disabled'
    ];
    if (isset($order_form[$prefix . '_supplier']) && !empty($order_form[$prefix . '_supplier'])) {
        $supplier = $order_form[$prefix . '_supplier'];
        $base_params = [$supplier];

        $stmt = $pdo->prepare("SELECT DISTINCT jenis FROM kertas WHERE is_archived = 0 AND supplier = ? ORDER BY jenis ASC");
        $stmt->execute($base_params);
        $options[$prefix]['jenis'] = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $stmt = $pdo->prepare("SELECT DISTINCT warna FROM kertas WHERE is_archived = 0 AND supplier = ? ORDER BY warna ASC");
        $stmt->execute($base_params);
        $options[$prefix]['warna'] = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $stmt = $pdo->prepare("SELECT DISTINCT gsm FROM kertas WHERE is_archived = 0 AND supplier = ? ORDER BY gsm ASC");
        $stmt->execute($base_params);
        $options[$prefix]['gsm'] = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $stmt = $pdo->prepare("SELECT DISTINCT ukuran FROM kertas WHERE is_archived = 0 AND supplier = ? ORDER BY ukuran ASC");
        $stmt->execute($base_params);
        $options[$prefix]['ukuran'] = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $options[$prefix]['disabled'] = '';
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>bikin form order</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="scripts.js"></script>
<style>
.searchable-dropdown {
    position: relative;
    display: inline-block;
    width: 100%;
}
.dropdown-content {
    display: none;
    position: absolute;
    background-color: #f9f9f9;
    min-width: 100%;
    box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
    z-index: 1;
    max-height: 200px;
    overflow-y: auto;
}
.dropdown-content a {
    color: black;
    padding: 12px 16px;
    text-decoration: none;
    display: block;
}
.dropdown-content a:hover {background-color: #f1f1f1}
.show {display: block;}
#customer_search {
    background-image: url('data:image/svg+xml;utf8,<svg fill="black" height="24" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M7 10l5 5 5-5z"/><path d="M0 0h24v24H0z" fill="none"/></svg>');
    background-repeat: no-repeat;
    background-position: right 0.7em top 50%, 0 0;
    background-size: 1.2em auto, 100%;
}
</style>
</head>
<body class="bg-gray-100 text-gray-900 pt-24 px-8 pb-8 font-mono">

    <?php include 'navbar.php'; ?>
    <h1 class="text-sm font-bold mb-6 text-gray-800">> bikin FO</h1>

    <?php if (!empty($_SESSION['flash_error'])): ?>
        <div class="mb-4 p-3 bg-red-100 border border-red-300 text-red-800">
            <?= htmlspecialchars($_SESSION['flash_error']) ?>
        </div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['flash_success'])): ?>
        <?php $flash_msg = $_SESSION['flash_success']; ?>
        <div id="server_flash_success" class="mb-4 p-3 bg-green-100 border border-green-300 text-green-800">
            <?= htmlspecialchars($flash_msg) ?>
        </div>
        <script>window.__flashSuccess = <?= json_encode($flash_msg) ?>;</script>
        <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>

    <form action="" method="post" enctype="multipart/form-data" class="bg-white p-8 shadow-lg">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
    <?php $order_form = $_SESSION['order_form'] ?? []; ?>
      
      <!-- Two-column layout: left = BOX fields, right = SPK fields -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Left column: BOX fields (lines 89-225) -->
        <div>
          <div class="mb-8">
              <label for="nama_customer" class="block text-gray-800 text-xl font-semibold mb-2">Nama Customer</label>
              <?php $errors = $_SESSION['form_errors'] ?? []; unset($_SESSION['form_errors']); ?>
              <div class="searchable-dropdown">
                  <input type="text" id="customer_search" placeholder="Pilih atau cari customer" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" value="<?php echo htmlspecialchars($order_form['nama_customer'] ?? ''); ?>">
                  <div id="customer_dropdown" class="dropdown-content">
                      <?php foreach ($customers as $customer): ?>
                          <a href="#" data-value="<?= $customer['nama'] ?>"><?= $customer['nama'] ?></a>
                      <?php endforeach; ?>
                  </div>
                  <input type="hidden" name="nama_customer" id="nama_customer" value="<?php echo htmlspecialchars($order_form['nama_customer'] ?? ''); ?>">
              </div>
              <?php if (!empty($errors['nama_customer'])): ?>
                  <div class="text-red-600 text-sm mt-1"><?= htmlspecialchars($errors['nama_customer']) ?></div>
              <?php endif; ?>
          </div>
          <h2 class="text-xl font-bold mb-4 text-gray-400">BOX</h2>
          <div class="border-b-2 border-gray-300 mb-6"></div>
          <div class="mb-4">
            <label class="block text-gray-800 text-xl font-semibold mb-2">Ukuran (cm)</label>
            <div class="flex space-x-2">
              <input type="text" name="length" placeholder="panjang" value="<?php echo htmlspecialchars($order_form['length'] ?? ''); ?>" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out">
              <input type="text" name="width" placeholder="lebar" value="<?php echo htmlspecialchars($order_form['width'] ?? ''); ?>" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out">
              <input type="text" name="height" placeholder="tinggi" value="<?php echo htmlspecialchars($order_form['height'] ?? ''); ?>" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out">
            </div>
          </div>
          
                <div class="mb-4">
                    <label class="block text-gray-800 text-xl font-semibold mb-2">Kode Pisau</label>
                    <div class="mt-2">
                        <label class="inline-flex items-center">
                            <input type="radio" name="kode_pisau" value="baru" onchange="handleKodePisauChange(this.value)" class="form-radio h-4 w-4 text-blue-600 transition duration-150 ease-in-out" required <?php echo (isset($order_form['kode_pisau']) && $order_form['kode_pisau'] === 'baru') ? 'checked' : ''; ?>>
                            <span class="ml-2 text-gray-800">Baru</span>
                        </label>
                        <label class="inline-flex items-center ml-6">
                            <input type="radio" name="kode_pisau" value="lama" onchange="handleKodePisauChange(this.value)" class="form-radio h-4 w-4 text-blue-600 transition duration-150 ease-in-out" required <?php echo (isset($order_form['kode_pisau']) && $order_form['kode_pisau'] === 'lama') ? 'checked' : ''; ?>>
                            <span class="ml-2 text-gray-800">Lama</span>
                        </label>
                        <?php if (!empty($errors['kode_pisau'])): ?>
                            <div class="text-red-600 text-sm mt-1"><?= htmlspecialchars($errors['kode_pisau']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="quantity" class="block text-gray-800 text-xl font-semibold mb-2">Quantity</label>
                    <input type="number" name="quantity" id="quantity" step="1" value="<?php echo htmlspecialchars($order_form['quantity'] ?? ''); ?>" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required>
                </div>

                <div id="kode_pisau_baru_fields" style="display:none;" class="mb-4">
                    <div class="mb-4">
                        <label for="model_box_baru" class="block text-gray-800 text-xl font-semibold mb-2">Model Box</label>
                        <select name="model_box_baru" id="model_box_baru" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required>
                            <option value="" disabled <?php echo !isset($order_form['model_box_baru']) ? 'selected' : ''; ?>>Pilih Model Box</option>
                            <?php foreach ($model_boxes as $model_box): ?>
                                <!-- include box_luar and box_dlm as data attributes so JS can read labels -->
                                <option value="<?= $model_box['nama'] ?>" data-box-luar="<?= htmlspecialchars($model_box['box_luar'] ?? '') ?>" data-box-dlm="<?= htmlspecialchars($model_box['box_dlm'] ?? '') ?>" <?php echo (isset($order_form['model_box_baru']) && $order_form['model_box_baru'] === $model_box['nama']) ? 'selected' : ''; ?>><?= $model_box['nama'] ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (!empty($errors['model_box_baru'])): ?>
                            <div class="text-red-600 text-sm mt-1"><?= htmlspecialchars($errors['model_box_baru']) ?></div>
                        <?php endif; ?>
                    </div>

                </div>

                <div id="kode_pisau_lama_fields" style="display:none;" class="mb-4">
                    <label for="barang_lama" class="block text-gray-800 text-xl font-semibold mb-2">Barang</label>
                    <select name="barang_lama" id="barang_lama" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out">
                        <option value="" disabled <?php echo !isset($order_form['barang_lama']) ? 'selected' : ''; ?>>Pilih Barang</option>
                        <?php foreach ($barangs as $barang): ?>
                            <option value="<?= $barang['id'] ?>" <?php echo (isset($order_form['barang_lama']) && $order_form['barang_lama'] == $barang['id']) ? 'selected' : ''; ?>><?= $barang['model_box'] . ' - ' . $barang['ukuran'] . ' - ' . $barang['nama'] ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!empty($errors['barang_lama'])): ?>
                        <div class="text-red-600 text-sm mt-1"><?= htmlspecialchars($errors['barang_lama']) ?></div>
                    <?php endif; ?>
                </div>

                <div id="shared_fields" style="display:none;" class="mb-4">
                    <div class="mb-4">
                        <label for="jenis_board" class="block text-gray-800 text-xl font-semibold mb-2">Jenis Board</label>
                        <select name="jenis_board" id="jenis_board" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required>
                            <option value="" disabled <?php echo !isset($order_form['jenis_board']) ? 'selected' : ''; ?>>Pilih Jenis Board</option>
                            <?php foreach ($boards as $board): ?>
                                <option value="<?= $board['jenis'] ?>" <?php echo (isset($order_form['jenis_board']) && $order_form['jenis_board'] === $board['jenis']) ? 'selected' : ''; ?>><?= $board['jenis'] ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (!empty($errors['jenis_board'])): ?>
                            <div class="text-red-600 text-sm mt-1"><?= htmlspecialchars($errors['jenis_board']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-800 text-sm font-semibold mb-2">Cover Dalam</label>
                        <div class="flex space-x-2">
                            <select name="cover_dalam_supplier" id="cover_dalam_supplier" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required onchange="updateKertasOptions('cover_dalam')">
                                <option value="" disabled <?php echo !isset($order_form['cover_dalam_supplier']) ? 'selected' : ''; ?>>Supplier</option>
                                <?php foreach ($distinct_suppliers as $supplier): ?>
                                    <option value="<?= $supplier['supplier'] ?>" <?php echo (isset($order_form['cover_dalam_supplier']) && $order_form['cover_dalam_supplier'] === $supplier['supplier']) ? 'selected' : ''; ?>><?= $supplier['supplier'] ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select name="cover_dalam_warna" id="cover_dalam_warna" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required <?php echo $options['cover_dalam']['disabled']; ?>>
                                <option value="" disabled <?php echo !isset($order_form['cover_dalam_warna']) ? 'selected' : ''; ?>>Warna</option>
                                <?php foreach ($options['cover_dalam']['warna'] as $warna): ?>
                                    <option value="<?= $warna ?>" <?php echo (isset($order_form['cover_dalam_warna']) && $order_form['cover_dalam_warna'] === $warna) ? 'selected' : ''; ?>><?= $warna ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-800 text-sm font-semibold mb-2">Cover Luar</label>
                        <!-- Labels that will be updated based on selected model box -->
                        <div class="flex space-x-2 mt-1 pl-4">
                            <label id="cover_luar_row1_label" class="w-1/2 text-gray-700">Box Luar</label>
                        </div>
                                                <div class="flex space-x-2 mt-2 pl-4">
                                                    <select name="cover_luar_supplier_luar" id="cover_luar_supplier_luar" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required onchange="updateKertasOptions('cover_luar')">
                                                        <option value="" disabled <?php echo !isset($order_form['cover_luar_supplier_luar']) ? 'selected' : ''; ?>>Supplier</option>
                                                        <?php foreach ($distinct_suppliers as $supplier): ?>
                                                            <option value="<?= $supplier['supplier'] ?>" <?php echo (isset($order_form['cover_luar_supplier_luar']) && $order_form['cover_luar_supplier_luar'] === $supplier['supplier']) ? 'selected' : ''; ?>><?= $supplier['supplier'] ?></option>
                                                            <?php endforeach; ?>
                                                        </select>
                            
                                                        <select name="cover_luar_warna_luar" id="cover_luar_warna_luar" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required <?php echo $options['cover_luar']['disabled']; ?>>
                                                                <option value="" disabled <?php echo !isset($order_form['cover_luar_warna_luar']) ? 'selected' : ''; ?>>Warna</option>
                                                                <?php foreach ($options['cover_luar']['warna'] as $warna): ?>
                                                                        <option value="<?= $warna ?>" <?php echo (isset($order_form['cover_luar_warna_luar']) && $order_form['cover_luar_warna_luar'] === $warna) ? 'selected' : ''; ?>><?= $warna ?></option>
                                                                <?php endforeach; ?>
                                                        </select>
                            
                                                </div>
                        <div class="flex space-x-2 mt-1 pl-4">
                            <label id="cover_luar_row2_label" class="w-1/2 text-gray-700">Box Dalam</label>
                        </div>
                        <div class="flex space-x-2 mt-2 pl-4">
                            <select name="cover_luar_supplier_dlm" id="cover_luar_supplier_dlm" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required onchange="updateKertasOptions('cover_luar')">
                                <option value="" disabled <?php echo !isset($order_form['cover_luar_supplier_dlm']) ? 'selected' : ''; ?>>Supplier</option>
                                <?php foreach ($distinct_suppliers as $supplier): ?>
                                    <option value="<?= $supplier['supplier'] ?>" <?php echo (isset($order_form['cover_luar_supplier_dlm']) && $order_form['cover_luar_supplier_dlm'] === $supplier['supplier']) ? 'selected' : ''; ?>><?= $supplier['supplier'] ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select name="cover_luar_warna_dlm" id="cover_luar_warna_dlm" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" required <?php echo $options['cover_luar']['disabled']; ?>>
                                <option value="" disabled <?php echo !isset($order_form['cover_luar_warna_dlm']) ? 'selected' : ''; ?>>Warna</option>
                                <?php foreach ($options['cover_luar']['warna'] as $warna): ?>
                                    <option value="<?= $warna ?>" <?php echo (isset($order_form['cover_luar_warna_dlm']) && $order_form['cover_luar_warna_dlm'] === $warna) ? 'selected' : ''; ?>><?= $warna ?></option>
                                <?php endforeach; ?>
                            </select>
                            
                          </div>
                        </div>
                        <div class="mb-4">
                          <label class="block text-gray-800 text-sm font-semibold mb-2">Aksesoris</label>
                          <div class="flex space-x-2">
                            <select name="aksesoris_jenis" id="aksesoris_jenis" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out">
                              <option value="" disabled <?php echo !isset($order_form['aksesoris_jenis']) ? 'selected' : ''; ?>>Pilih Jenis</option>
                              <?php foreach ($aksesoris_jenis as $jenis): ?>
                                <option value="<?= $jenis['jenis'] ?>" <?php echo (isset($order_form['aksesoris_jenis']) && $order_form['aksesoris_jenis'] === $jenis['jenis']) ? 'selected' : ''; ?>><?= $jenis['jenis'] ?></option>
                                <?php endforeach; ?>
                              </select>
                              <select name="aksesoris_ukuran" id="aksesoris_ukuran" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" <?php echo $aksesoris_dropdown_disabled; ?>>
                                <option value="" disabled <?php echo !isset($order_form['aksesoris_ukuran']) ? 'selected' : ''; ?>>Pilih Ukuran</option>
                                <?php foreach ($aksesoris_ukuran_options as $ukuran): ?>
                                  <option value="<?= $ukuran ?>" <?php echo (isset($order_form['aksesoris_ukuran']) && $order_form['aksesoris_ukuran'] === $ukuran) ? 'selected' : ''; ?>><?= $ukuran ?></option>
                                  <?php endforeach; ?>
                                </select>
                                <select name="aksesoris_warna" id="aksesoris_warna" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" <?php echo $aksesoris_dropdown_disabled; ?>>
                                  <option value="" disabled <?php echo !isset($order_form['aksesoris_warna']) ? 'selected' : ''; ?>>Pilih Warna</option>
                                  <?php foreach ($aksesoris_warna_options as $warna): ?>
                                    <option value="<?= $warna ?>" <?php echo (isset($order_form['aksesoris_warna']) && $order_form['aksesoris_warna'] === $warna) ? 'selected' : ''; ?>><?= $warna ?></option>
                                    <?php endforeach; ?>
                                  </select>
                                </div>
                              </div>
                              <div class="mb-4">
                                <label for="ket_aksesoris" class="block text-gray-800 text-sm font-semibold mb-2">Keterangan Aksesoris</label>
                                <textarea name="ket_aksesoris" id="ket_aksesoris" rows="3" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out"><?php echo htmlspecialchars($order_form['ket_aksesoris'] ?? ''); ?></textarea>
                              </div>

                </div>
            </div>

            <!-- Right column: SPK fields (lines 227-324) -->
            <div>
              <h2 class="text-xl font-bold mb-4 text-gray-400">PENGIRIMAN</h2>
                <div class="border-b-2 border-gray-300 mb-6"></div>

                <div class="mb-4">
                    <label for="tanggal_kirim" class="block text-gray-800 text-sm font-semibold mb-2">Tanggal Kirim</label>
                    <input type="date" name="tanggal_kirim" id="tanggal_kirim" value="<?php echo htmlspecialchars($order_form['tanggal_kirim'] ?? ''); ?>" min="<?php echo date('Y-m-d'); ?>" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out">
                </div>

                <div class="mb-4">
                    <label for="jam_kirim" class="block text-gray-800 text-sm font-semibold mb-2">Jam Kirim</label>
                    <input type="time" name="jam_kirim" id="jam_kirim" value="<?php echo htmlspecialchars($order_form['jam_kirim'] ?? ''); ?>" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out">
                </div>

                <div class="mb-4">
                    <label for="dikirim_dari" class="block text-gray-800 text-sm font-semibold mb-2">Dikirim Dari</label>
                    <select name="dikirim_dari" id="dikirim_dari" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out">
                        <option value="" disabled <?php echo !isset($order_form['dikirim_dari']) ? 'selected' : ''; ?>>Pilih Lokasi</option>
                        <?php foreach ($alamat_pengirim as $alamat): ?>
                            <option value="<?= $alamat['lokasi'] ?>" <?php echo (isset($order_form['dikirim_dari']) && $order_form['dikirim_dari'] === $alamat['lokasi']) ? 'selected' : ''; ?>><?= $alamat['lokasi'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-4">
                    <label for="tujuan_kirim" class="block text-gray-800 text-sm font-semibold mb-2">Tujuan Kirim</label>
                    <input type="text" name="tujuan_kirim" id="tujuan_kirim" value="<?php echo htmlspecialchars($order_form['tujuan_kirim'] ?? ''); ?>" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out">
                </div>

                <!-- Insert PEMBAYARAN fields -->
                <h2 class="text-xl font-bold mb-4 text-gray-400">PEMBAYARAN</h2>
                <div class="border-b-2 border-gray-300 mb-6"></div>

                <div class="mb-4">
                    <label for="tanggal_dp" class="block text-gray-800 text-sm font-semibold mb-2">Tanggal DP</label>
                    <input type="date" name="tanggal_dp" id="tanggal_dp" value="<?php echo htmlspecialchars($order_form['tanggal_dp'] ?? ''); ?>" min="<?php echo date('Y-m-d'); ?>" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out">
                </div>

                <div class="mb-4">
                    <label class="block text-gray-800 text-sm font-semibold mb-2">Pelunasan</label>
                    <div class="mt-2">
                        <label class="inline-flex items-center">
                            <input type="radio" name="pelunasan" value="Harus Lunas" class="form-radio h-4 w-4 text-blue-600" <?php echo (isset($order_form['pelunasan']) && $order_form['pelunasan'] === 'Harus lunas') ? 'checked' : ''; ?>>
                            <span class="ml-2 text-gray-800">Harus lunas</span>
                        </label>
                        <label class="inline-flex items-center ml-6">
                            <input type="radio" name="pelunasan" value="Setelah dikirim" class="form-radio h-4 w-4 text-blue-600" <?php echo (isset($order_form['pelunasan']) && $order_form['pelunasan'] === 'Setelah dikirim') ? 'checked' : ''; ?>>
                            <span class="ml-2 text-gray-800">Setelah dikirim</span>
                        </label>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="block text-gray-800 text-sm font-semibold mb-2">Ongkir</label>
                    <div class="mt-2">
                        <label class="inline-flex items-center">
                            <input type="radio" name="ongkir" value="Gracia" class="form-radio h-4 w-4 text-blue-600" <?php echo (isset($order_form['ongkir']) && $order_form['ongkir'] === 'Gracia') ? 'checked' : ''; ?>>
                            <span class="ml-2 text-gray-800">Gracia</span>
                        </label>
                        <label class="inline-flex items-center ml-6">
                            <input type="radio" name="ongkir" value="Customer" class="form-radio h-4 w-4 text-blue-600" <?php echo (isset($order_form['ongkir']) && $order_form['ongkir'] === 'Customer') ? 'checked' : ''; ?>>
                            <span class="ml-2 text-gray-800">Customer</span>
                        </label>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="block text-gray-800 text-sm font-semibold mb-2">Packing</label>
                    <div class="mt-2">
                        <label class="inline-flex items-center">
                            <input type="radio" name="packing" value="Luar kota (Ekspedisi)" class="form-radio h-4 w-4 text-blue-600" <?php echo (isset($order_form['packing']) && $order_form['packing'] === 'Luar kota (Ekspedisi)') ? 'checked' : ''; ?>>
                            <span class="ml-2 text-gray-800">Luar kota (Ekspedisi)</span>
                        </label>
                        <label class="inline-flex items-center ml-6">
                            <input type="radio" name="packing" value="Dalam kota" class="form-radio h-4 w-4 text-blue-600" <?php echo (isset($order_form['packing']) && $order_form['packing'] === 'Dalam kota') ? 'checked' : ''; ?>>
                            <span class="ml-2 text-gray-800">Dalam kota</span>
                        </label>
                    </div>
                </div>
                <!-- Biaya field -->
                <div class="mb-4">
                    <label for="biaya" class="block text-gray-800 text-sm font-semibold mb-2">Biaya</label>
                    <input type="text" name="biaya" id="biaya" value="<?php echo htmlspecialchars($order_form['biaya'] ?? ''); ?>" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" placeholder="0">
                </div>
                    
                
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-bold text-gray-400">SPK</h2>
                    <button type="button" id="reset_spk_button" class="text-sm font-semibold text-blue-600 hover:text-blue-800 focus:outline-none">reset</button>
                </div>
                <div class="border-b-2 border-gray-300 mb-6"></div>
                <div class="mb-4">
                    <div class="flex space-x-4">
                        <div class="w-1/2">
                            <label for="dudukan" class="block text-gray-800 text-sm font-semibold mb-2">Dudukan</label>
                            <select name="dudukan" id="dudukan" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out">
                                <option value="" disabled <?php echo !isset($order_form['dudukan']) ? 'selected' : ''; ?>>Pilih Dudukan</option>
                                <?php foreach ($dudukan_options as $dudukan): ?>
                                    <option value="<?= $dudukan['id'] ?>" <?php echo (isset($order_form['dudukan']) && $order_form['dudukan'] == $dudukan['id']) ? 'selected' : ''; ?>><?= $dudukan['jenis'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="w-1/2">
                            <label for="jumlah_layer" class="block text-gray-800 text-sm font-semibold mb-2">Jumlah layer</label>
                            <input type="number" name="jumlah_layer" id="jumlah_layer" value="<?php echo htmlspecialchars($order_form['jumlah_layer'] ?? ''); ?>" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out" min="0" step="1">
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="dudukan_img" class="block text-gray-800 text-sm font-semibold mb-2">Dudukan Images (max 3)</label>
                    <input type="file" name="dudukan_img[]" id="dudukan_img" multiple accept="image/*" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out">
                    <div id="image_preview_container" class="flex flex-wrap gap-2 mt-2"></div>
                    <?php if (!empty($errors['dudukan_img'])): ?>
                        <div class="text-red-600 text-sm mt-1"><?= htmlspecialchars($errors['dudukan_img']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="mb-4">
                    <div class="flex space-x-4">
                        <div class="w-1/2">
                            <label for="logo" class="block text-gray-800 text-sm font-semibold mb-2">Logo</label>
                            <select name="logo" id="logo" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out">
                                <option value="" disabled <?php echo !isset($order_form['logo']) ? 'selected' : ''; ?>>Pilih Logo</option>
                                <?php foreach ($logo_options as $logo): ?>
                                    <option value="<?= $logo['jenis'] ?>" <?php echo (isset($order_form['logo']) && $order_form['logo'] === $logo['jenis']) ? 'selected' : ''; ?>><?= $logo['jenis'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="w-1/2 mb-4">
                            <label for="ukuran_poly" class="block text-gray-800 text-sm font-semibold mb-2">Ukuran Poly</label>
                            <select name="ukuran_poly" id="ukuran_poly" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out">
                                <option value="" disabled <?php echo !isset($order_form['ukuran_poly']) ? 'selected' : ''; ?>>Pilih Ukuran Poly</option>
                                <?php foreach ($logo_uk_poly_options as $uk_poly): ?>
                                    <option value="<?= $uk_poly['uk_poly'] ?>" <?php echo (isset($order_form['ukuran_poly']) && $order_form['ukuran_poly'] === $uk_poly['uk_poly']) ? 'selected' : ''; ?>><?= $uk_poly['uk_poly'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                      </div>
                      <div class="mb-4">
                        <label for="logo_img" class="block text-gray-800 text-sm font-semibold mb-2">Logo Images (max 3)</label>
                        <input type="file" name="logo_img[]" id="logo_img" multiple accept="image/*" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out">
                        <div id="logo_image_preview_container" class="flex flex-wrap gap-2 mt-2"></div>
                          <?php if (!empty($errors['logo_img'])): ?>
                              <div class="text-red-600 text-sm mt-1"><?= htmlspecialchars($errors['logo_img']) ?></div>
                          <?php endif; ?>
                      </div>
                </div>

                <div class="mb-4">
                    <div class="flex space-x-4">
                        <div class="w-1/2">
                            <label class="block text-gray-800 text-sm font-semibold mb-2">Lokasi Poly</label>
                            <div class="mt-2">
                                <label class="inline-flex items-center">
                                    <input type="radio" name="lokasi_poly" value="Pabrik" class="form-radio h-4 w-4 text-blue-600 transition duration-150 ease-in-out"<?php echo (isset($order_form['lokasi_poly']) && $order_form['lokasi_poly'] === 'Pabrik') ? 'checked' : ''; ?>>
                                    <span class="ml-2 text-gray-800">Pabrik</span>
                                </label>
                                <label class="inline-flex items-center ml-6">
                                    <input type="radio" name="lokasi_poly" value="Luar" class="form-radio h-4 w-4 text-blue-600 transition duration-150 ease-in-out"<?php echo (isset($order_form['lokasi_poly']) && $order_form['lokasi_poly'] === 'Luar') ? 'checked' : ''; ?>>
                                    <span class="ml-2 text-gray-800">Luar</span>
                                </label>
                            </div>
                        </div>
                        <div class="w-1/2">
                            <label class="block text-gray-800 text-sm font-semibold mb-2">Klise</label>
                            <div class="mt-2">
                                <label class="inline-flex items-center">
                                    <input type="radio" name="klise" value="In Stock" class="form-radio h-4 w-4 text-blue-600 transition duration-150 ease-in-out" <?php echo (isset($order_form['klise']) && $order_form['klise'] === 'In Stock') ? 'checked' : ''; ?>>
                                    <span class="ml-2 text-gray-800">In Stock</span>
                                </label>
                                <label class="inline-flex items-center ml-6">
                                    <input type="radio" name="klise" value="Bikin baru" class="form-radio h-4 w-4 text-blue-600 transition duration-150 ease-in-out"<?php echo (isset($order_form['klise']) && $order_form['klise'] === 'Bikin baru') ? 'checked' : ''; ?>>
                                    <span class="ml-2 text-gray-800">Bikin baru</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <label for="poly_img" class="block text-gray-800 text-sm font-semibold mb-2">Poly Images (max 3)</label>
                    <input type="file" name="poly_img[]" id="poly_img" multiple accept="image/*" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out">
                    <div id="poly_image_preview_container" class="flex flex-wrap gap-2 mt-2"></div>
                    <?php if (!empty($errors['poly_img'])): ?>
                        <div class="text-red-600 text-sm mt-1"><?= htmlspecialchars($errors['poly_img']) ?></div>
                    <?php endif; ?>
                </div>

                
                
            </div>
        </div>

        <div class="border-b-2 border-gray-300 mt-6 mb-6"></div>
        <div class="mb-4">
            <label for="feedback_cust" class="block text-gray-800 text-sm font-semibold mb-2">Feedback Customer</label>
            <textarea name="feedback_cust" id="feedback_cust" rows="3" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out"><?php echo htmlspecialchars($order_form['feedback_cust'] ?? ''); ?></textarea>
        </div>

        <div class="mb-4">
            <label for="keterangan" class="block text-gray-800 text-sm font-semibold mb-2">Keterangan</label>
            <textarea name="keterangan" id="keterangan" rows="3" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out"><?php echo htmlspecialchars($order_form['keterangan'] ?? ''); ?></textarea>
        </div>

        <div class="mb-4">
            <label for="dibuat_oleh" class="block text-gray-800 text-sm font-semibold mb-2">Dibuat Oleh</label>
            <select name="dibuat_oleh" id="dibuat_oleh" class="appearance-none bg-white border border-gray-300 w-full py-2 px-3 text-gray-800 leading-tight focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-150 ease-in-out">
                <option value="" disabled <?php echo !isset($order_form['dibuat_oleh']) ? 'selected' : ''; ?>>Pilih Karyawan Sales</option>
                <?php foreach ($sales_reps as $sales_rep): ?>
                    <option value="<?= $sales_rep['nama'] ?>" <?php echo (isset($order_form['dibuat_oleh']) && $order_form['dibuat_oleh'] === $sales_rep['nama']) ? 'selected' : ''; ?>><?= $sales_rep['nama'] ?></option>
                <?php endforeach; ?>
            </select>
            <?php if (!empty($errors['dibuat_oleh'])): ?>
                <div class="text-red-600 text-sm mt-1"><?= htmlspecialchars($errors['dibuat_oleh']) ?></div>
            <?php endif; ?>
        </div>

        <div class="mb-4">
            <label class="block text-gray-800 text-sm font-semibold mb-2">Lokasi Retail</label>
            <div class="mt-2">
                <label class="inline-flex items-center">
                    <input type="radio" name="lokasi" value="BSD" class="form-radio h-4 w-4 text-blue-600 transition duration-150 ease-in-out" required <?php echo (isset($order_form['lokasi']) && $order_form['lokasi'] === 'BSD') ? 'checked' : ''; ?>>
                    <span class="ml-2 text-gray-800">BSD</span>
                </label>
                <label class="inline-flex items-center ml-6">
                    <input type="radio" name="lokasi" value="Pondok Aren" class="form-radio h-4 w-4 text-blue-600 transition duration-150 ease-in-out" required <?php echo (isset($order_form['lokasi']) && $order_form['lokasi'] === 'Pondok Aren') ? 'checked' : ''; ?>>
                    <span class="ml-2 text-gray-800">Pondok Aren</span>
                </label>
            </div>
        </div>

        <div class="flex items-center justify-start space-x-4">
            <input type="submit" value="Save" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition duration-150 ease-in-out">
            <a href="daftar_fo.php" class="inline-block align-baseline font-semibold text-sm text-blue-600 hover:text-blue-800">
                Back
            </a>
        </div>
    </form>

    <!-- Client-side error container -->
    <div id="client_errors" class="hidden mb-4 p-3 bg-red-100 border border-red-300 text-red-800"></div>

    <!-- Success Modal -->
    <div id="successModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50 hidden">
        <div class="bg-white rounded-lg shadow-lg p-6 w-11/12 max-w-md">
            <h3 class="text-lg font-semibold mb-2">notif</h3>
            <p id="successModalMessage" class="mb-4 text-gray-700"></p>
            <div class="flex justify-end">
                <button id="successModalClose" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">OK</button>
            </div>
        </div>
    </div>

    <script>
        function handleKodePisauChange(value) {
            const modelBoxBaru = document.getElementById('model_box_baru');
            const dibuatOleh = document.getElementById('dibuat_oleh');
            const barangLama = document.getElementById('barang_lama');
            const sharedFields = document.getElementById('shared_fields');
            const kodePisauBaruFields = document.getElementById('kode_pisau_baru_fields');
            const kodePisauLamaFields = document.getElementById('kode_pisau_lama_fields');

            // Hide all conditional fields initially
            kodePisauBaruFields.style.display = 'none';
            kodePisauLamaFields.style.display = 'none';
            sharedFields.style.display = 'none';

            // Disable all inputs within the conditional fields
            modelBoxBaru.required = false;
            barangLama.required = false;
            dibuatOleh.required = false;

            if (value === 'baru') {
                kodePisauBaruFields.style.display = 'block';
                sharedFields.style.display = 'block';
                modelBoxBaru.required = true;
                dibuatOleh.required = true;
            } else if (value === 'lama') {
                kodePisauLamaFields.style.display = 'block';
                sharedFields.style.display = 'block';
                barangLama.required = true;
                dibuatOleh.required = true;
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const kodePisauValue = "<?php echo addslashes($order_form['kode_pisau'] ?? ''); ?>";
            if (kodePisauValue) {
                handleKodePisauChange(kodePisauValue);
            }
            // Initialize cover luar labels based on selected model box (if any)
            const modelBoxSelect = document.getElementById('model_box_baru');
            const row1Label = document.getElementById('cover_luar_row1_label');
            const row2Label = document.getElementById('cover_luar_row2_label');

            function updateCoverLuarLabelsFromModel() {
                if (!modelBoxSelect) return;
                const opt = modelBoxSelect.options[modelBoxSelect.selectedIndex];
                if (opt) {
                    const boxLuar = opt.getAttribute('data-box-luar') || 'Box Luar';
                    const boxDlm = opt.getAttribute('data-box-dlm') || 'Box Dalam';
                    // Set labels
                    if (row1Label) row1Label.textContent = boxLuar;
                    if (row2Label) row2Label.textContent = boxDlm;
                }
            }

            if (modelBoxSelect) {
                modelBoxSelect.addEventListener('change', updateCoverLuarLabelsFromModel);
                // Call once to set initial labels
                updateCoverLuarLabelsFromModel();
            }
        });
    </script>
    <script>
        // Simple client-side validation before submit
        (function(){
            document.getElementById('aksesoris_jenis').addEventListener('change', updateAksesorisOptions);

            function updateAksesorisOptions() {
                const jenisDropdown = document.getElementById('aksesoris_jenis');
                const ukuranDropdown = document.getElementById('aksesoris_ukuran');
                const warnaDropdown = document.getElementById('aksesoris_warna');
                const selectedJenis = jenisDropdown.value;

                // Disable and clear dependent dropdowns
                ukuranDropdown.disabled = true;
                warnaDropdown.disabled = true;
                ukuranDropdown.innerHTML = '<option value="" disabled selected>Pilih Ukuran</option>';
                warnaDropdown.innerHTML = '<option value="" disabled selected>Pilih Warna</option>';

                if (selectedJenis) {
                    fetch(`get_aksesoris_options.php?jenis=${encodeURIComponent(selectedJenis)}`)
                        .then(response => response.json())
                        .then(data => {
                            // Populate ukuran dropdown
                            if (data.ukuran && data.ukuran.length > 0) {
                                data.ukuran.forEach(function(ukuran) {
                                    const option = new Option(ukuran, ukuran);
                                    ukuranDropdown.add(option);
                                });
                                ukuranDropdown.disabled = false;
                            }

                            // Populate warna dropdown
                            if (data.warna && data.warna.length > 0) {
                                data.warna.forEach(function(warna) {
                                    const option = new Option(warna, warna);
                                    warnaDropdown.add(option);
                                });
                                warnaDropdown.disabled = false;
                            }
                        })
                        .catch(error => console.error('Error fetching aksesoris options:', error));
                }
            }
            const resetButton = document.getElementById('reset_spk_button');
            if(resetButton) {
                resetButton.addEventListener('click', function() {
                    // Reset Dudukan dropdown
                    document.getElementById('dudukan').selectedIndex = 0;

                    // Reset Jumlah layer
                    document.getElementById('jumlah_layer').value = '';

                    // Reset Dudukan Images
                    const dudukanImgInput = document.getElementById('dudukan_img');
                    dudukanImgInput.value = '';
                    document.getElementById('image_preview_container').innerHTML = '';

                    // Reset Logo dropdown
                    document.getElementById('logo').selectedIndex = 0;

                    // Reset Ukuran Poly dropdown
                    document.getElementById('ukuran_poly').selectedIndex = 0;

                    // Reset Lokasi Poly radio buttons
                    const lokasiPolyRadios = document.getElementsByName('lokasi_poly');
                    for (let i = 0; i < lokasiPolyRadios.length; i++) {
                        lokasiPolyRadios[i].checked = false;
                    }

                    // Reset Klise radio buttons
                    const kliseRadios = document.getElementsByName('klise');
                    for (let i = 0; i < kliseRadios.length; i++) {
                        kliseRadios[i].checked = false;
                    }

                    // Reset Logo Images
                    const logoImgInput = document.getElementById('logo_img');
                    logoImgInput.value = '';
                    document.getElementById('logo_image_preview_container').innerHTML = '';
                });
            }

            const form = document.querySelector('form');
            const clientErrors = document.getElementById('client_errors');
            const successModal = document.getElementById('successModal');
            const successModalMessage = document.getElementById('successModalMessage');
            const successModalClose = document.getElementById('successModalClose');

            function showErrors(errors) {
                clientErrors.innerHTML = errors.map(e => '<div>' + e.msg + '</div>').join('');
                clientErrors.classList.remove('hidden');
                clientErrors.scrollIntoView({behavior: 'smooth', block: 'center'});

                // highlight invalid fields
                errors.forEach(function(err){
                    if (!err.field) return;
                    const el = form.querySelector('[name="' + err.field + '"]');
                    if (el) {
                        el.classList.add('border-red-600', 'ring-1', 'ring-red-400');
                    }
                });
            }

            function clearErrors() {
                clientErrors.innerHTML = '';
                clientErrors.classList.add('hidden');
                // remove highlighting
                const invalidMarked = form.querySelectorAll('.border-red-600');
                invalidMarked.forEach(function(el){
                    el.classList.remove('border-red-600', 'ring-1', 'ring-red-400');
                });
            }

            form.addEventListener('submit', function(ev){
                clearErrors();
                const data = new FormData(form);
                const errors = [];
                const mappedErrors = []; // {msg, field}

                const nama = data.get('nama_customer') || '';
                const kode_pisau = data.get('kode_pisau') || '';
                const jenis_board = data.get('jenis_board') || '';
                const dibuat_oleh = data.get('dibuat_oleh') || '';

                if (!nama.trim()) mappedErrors.push({msg: 'Nama Customer is required.', field: 'nama_customer'});
                if (!kode_pisau) mappedErrors.push({msg: 'Kode Pisau is required.', field: 'kode_pisau'});
                if (!jenis_board) mappedErrors.push({msg: 'Jenis Board is required.', field: 'jenis_board'});

                if (kode_pisau === 'baru') {
                    const mb = data.get('model_box_baru') || '';
                    const length = data.get('length') || '';
                    const width = data.get('width') || '';
                    const height = data.get('height') || '';
                    if (!mb) mappedErrors.push({msg: 'Model Box is required for new kode pisau.', field: 'model_box_baru'});
                    if (!length || !width || !height) mappedErrors.push({msg: 'Lenght, Width and Height are required for new kode pisau.', field: 'length'});
                    if (!dibuat_oleh) mappedErrors.push({msg: 'Dibuat Oleh is required.', field: 'dibuat_oleh'});
                }
                if (kode_pisau === 'lama') {
                    const barangLama = data.get('barang_lama') || '';
                    if (!barangLama) mappedErrors.push({msg: 'Please select an existing Barang for kode pisau lama.', field: 'barang_lama'});
                    if (!dibuat_oleh) mappedErrors.push({msg: 'Dibuat Oleh is required.', field: 'dibuat_oleh'});
                }

                const qty = data.get('quantity');
                if (qty && qty.trim() !== '') {
                    const n = parseInt(qty, 10);
                    if (isNaN(n) || n <= 0) mappedErrors.push({msg: 'Quantity must be a positive integer.', field: 'quantity'});
                }

                if (mappedErrors.length) {
                    ev.preventDefault();
                    // Focus first field if possible
                    showErrors(mappedErrors);
                    const firstFieldName = mappedErrors[0].field;
                    const firstEl = form.querySelector('[name="' + firstFieldName + '"]');
                    if (firstEl) {
                        firstEl.focus({preventScroll: false});
                        // scroll more smoothly
                        setTimeout(function(){ firstEl.scrollIntoView({behavior: 'smooth', block: 'center'}); }, 80);
                    }
                    return false;
                }

                // let the form submit
            });

            // Show success modal if server told us so
            if (window.__flashSuccess) {
                // hide native server flash box if present
                const serverFlash = document.getElementById('server_flash_success');
                if (serverFlash) serverFlash.style.display = 'none';
                successModalMessage.textContent = window.__flashSuccess;
                successModal.classList.remove('hidden');
            }

            successModalClose && successModalClose.addEventListener('click', function(){
                successModal.classList.add('hidden');
            });

            const dudukanImgInput = document.getElementById('dudukan_img');
            const imagePreviewContainer = document.getElementById('image_preview_container');

            if (dudukanImgInput && imagePreviewContainer) {
                dudukanImgInput.addEventListener('change', function() {
                    imagePreviewContainer.innerHTML = ''; // Clear previous previews

                    const files = this.files;
                    if (files.length > 3) {
                        // Display an error message if more than 3 files are selected
                        imagePreviewContainer.innerHTML = '<div class="text-red-600 text-sm">You can upload a maximum of 3 images.</div>';
                        this.value = ''; // Clear selected files
                        return;
                    }

                    for (let i = 0; i < files.length; i++) {
                        const file = files[i];
                        if (file.type.startsWith('image/')) {
                            const reader = new FileReader();
                            reader.onload = function(e) {
                                const img = document.createElement('img');
                                img.src = e.target.result;
                                img.classList.add('w-24', 'h-24', 'object-cover', 'border', 'border-gray-300', 'rounded');
                                imagePreviewContainer.appendChild(img);
                            };
                            reader.readAsDataURL(file);
                        }
                    }
                });
            }

            const logoImgInput = document.getElementById('logo_img');
            const logoImagePreviewContainer = document.getElementById('logo_image_preview_container');

            if (logoImgInput && logoImagePreviewContainer) {
                logoImgInput.addEventListener('change', function() {
                    logoImagePreviewContainer.innerHTML = ''; // Clear previous previews

                    const files = this.files;
                    if (files.length > 3) {
                        // Display an error message if more than 3 files are selected
                        logoImagePreviewContainer.innerHTML = '<div class="text-red-600 text-sm">You can upload a maximum of 3 logo images.</div>';
                        this.value = ''; // Clear selected files
                        return;
                    }

                    for (let i = 0; i < files.length; i++) {
                        const file = files[i];
                        if (file.type.startsWith('image/')) {
                            const reader = new FileReader();
                            reader.onload = function(e) {
                                const img = document.createElement('img');
                                img.src = e.target.result;
                                img.classList.add('w-24', 'h-24', 'object-cover', 'border', 'border-gray-300', 'rounded');
                                logoImagePreviewContainer.appendChild(img);
                            };
                            reader.readAsDataURL(file);
                        }
                    }
                });
            }
            const polyImgInput = document.getElementById('poly_img');
            const polyImagePreviewContainer = document.getElementById('poly_image_preview_container');

            if (polyImgInput && polyImagePreviewContainer) {
                polyImgInput.addEventListener('change', function() {
                    polyImagePreviewContainer.innerHTML = ''; // Clear previous previews

                    const files = this.files;
                    if (files.length > 3) {
                        // Display an error message if more than 3 files are selected
                        polyImagePreviewContainer.innerHTML = '<div class="text-red-600 text-sm">You can upload a maximum of 3 poly images.</div>';
                        this.value = ''; // Clear selected files
                        return;
                    }

                    for (let i = 0; i < files.length; i++) {
                        const file = files[i];
                        if (file.type.startsWith('image/')) {
                            const reader = new FileReader();
                            reader.onload = function(e) {
                                const img = document.createElement('img');
                                img.src = e.target.result;
                                img.classList.add('w-24', 'h-24', 'object-cover', 'border', 'border-gray-300', 'rounded');
                                polyImagePreviewContainer.appendChild(img);
                            };
                            reader.readAsDataURL(file);
                        }
                    }
                });
            }
        })();
    </script>
    <script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('customer_search');
    const dropdown = document.getElementById('customer_dropdown');
    const hiddenInput = document.getElementById('nama_customer');
    const dropdownOptions = Array.from(dropdown.getElementsByTagName('a'));
    let activeOption = -1;

    searchInput.addEventListener('click', function() {
        dropdown.classList.toggle('show');
    });

    searchInput.addEventListener('input', function() {
        const filter = searchInput.value.toUpperCase();
        let hasVisibleOptions = false;
        for (let i = 0; i < dropdownOptions.length; i++) {
            const txtValue = dropdownOptions[i].textContent || dropdownOptions[i].innerText;
            if (txtValue.toUpperCase().indexOf(filter) > -1) {
                dropdownOptions[i].style.display = "";
                hasVisibleOptions = true;
            } else {
                dropdownOptions[i].style.display = "none";
            }
        }
        if(hasVisibleOptions) {
            dropdown.classList.add('show');
        } else {
            dropdown.classList.remove('show');
        }
        activeOption = -1;
    });

    searchInput.addEventListener('keydown', function(e) {
        if (e.keyCode === 40) { // ArrowDown
            e.preventDefault();
            activeOption++;
            addActive();
        } else if (e.keyCode === 38) { // ArrowUp
            e.preventDefault();
            activeOption--;
            addActive();
        } else if (e.keyCode === 13) { // Enter
            e.preventDefault();
            if (activeOption > -1) {
                dropdownOptions.find(opt => opt.style.display !== 'none' && dropdownOptions.indexOf(opt) >= activeOption)?.click();
            }
        } else if (e.keyCode === 9) { // Tab
            if (activeOption > -1) {
                e.preventDefault();
                dropdownOptions.find(opt => opt.style.display !== 'none' && dropdownOptions.indexOf(opt) >= activeOption)?.click();
            }
            dropdown.classList.remove('show');
        }
    });

    function addActive() {
        const visibleOptions = dropdownOptions.filter(opt => opt.style.display !== 'none');
        if (visibleOptions.length === 0) return;
        removeActive();
        if (activeOption >= visibleOptions.length) activeOption = 0;
        if (activeOption < 0) activeOption = (visibleOptions.length - 1);
        
        visibleOptions[activeOption].style.backgroundColor = "#ddd";
    }

    function removeActive() {
        for (let i = 0; i < dropdownOptions.length; i++) {
            dropdownOptions[i].style.backgroundColor = "";
        }
    }

    dropdownOptions.forEach(function(option) {
        option.addEventListener('click', function(e) {
            e.preventDefault();
            searchInput.value = this.getAttribute('data-value');
            hiddenInput.value = this.getAttribute('data-value');
            dropdown.classList.remove('show');
        });
    });

    document.addEventListener('click', function (e) {
        if (!e.target.matches('#customer_search')) {
            dropdown.classList.remove('show');
        }
    });
});
</script>
</body>
</html>