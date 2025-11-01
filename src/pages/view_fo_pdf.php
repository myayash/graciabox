<?php
ob_start();

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/config.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// --- Function Definitions ---

/**
 * Checks if the user is logged in and has the 'admin' role.
 * Terminates script if access is denied.
 */
function checkUserAccess() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit();
    }

    if ($_SESSION['role'] !== 'admin') {
        http_response_code(403);
        echo 'Access Denied: You do not have permission to view this page.';
        exit();
    }
}

/**
 * Fetches order data from the database.
 *
 * @param PDO $pdo The PDO database connection object.
 * @param int $order_id The ID of the order to fetch.
 * @return array The order data.
 */
function getOrderData($pdo, $order_id) {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        http_response_code(404);
        echo 'Error: Order not found.';
        exit();
    }
    return $order;
}

/**
 * Formats a key-value pair for display.
 *
 * @param string $key The field key.
 * @param string $value The field value.
 * @return array An array with 'display_key' and 'display_value'.
 */
function formatField($key, $value) {
    $display_key = ucwords(str_replace(['_', 'dlm', 'lr', 'pj'], [' ', 'Dalam', 'Luar', 'PJ'], $key));
    $display_value = htmlspecialchars($value);

    if ($key === 'nama') {
        $display_value = strtoupper(htmlspecialchars($value));
    } else if ($key === 'cover_dlm') {
        $display_value = nl2br(htmlspecialchars(preg_replace('/(supplier|jenis|warna|gsm|ukuran):\s*/i', '', $value)));
    } else if ($key === 'cover_lr') {
        $display_value = nl2br(htmlspecialchars(str_replace("\n", "; ", $value)));
    } else if ($key === 'quantity') {
        $display_value = htmlspecialchars(str_replace(' pcs', '', $value)) . 'pcs';
    } else if ($key === 'kode_pisau') {
        $display_value = strtoupper(htmlspecialchars($value));
    } else if ($key === 'aksesoris') {
        $display_value = nl2br(htmlspecialchars($value));
    } else if ($key === 'biaya') {
        $display_value = 'Rp. ' . number_format((float)$value, 0, ',', '.');
    }

    return ['display_key' => $display_key, 'display_value' => $display_value];
}

/**
 * Reads an image file and returns it as a base64 encoded data URI.
 *
 * @param string $path The absolute path to the image file.
 * @return string The base64 data URI, or an empty string if file not found.
 */
function getBase64Image($path) {
    if (!is_file($path) || !is_readable($path)) {
        return '';
    }
    $imageData = @file_get_contents($path);
    if ($imageData === false) {
        return '';
    }
    $type = pathinfo($path, PATHINFO_EXTENSION);
    return 'data:image/' . $type . ';base64,' . base64_encode($imageData);
}

/**
 * Generates the HTML content for the FO PDF.
 *
 * @param array $order The order data.
 * @return string The generated HTML.
 */
function generateFoHtml($order) {
    $logo_base64 = getBase64Image(__DIR__ . '/../../public/assets/images/graciabox_logo_gray.jpeg');

    $order_id_display = htmlspecialchars($order['id']);
    $dibuat_display = htmlspecialchars($order['dibuat']);

    // Row 1 - Column 1
    $row1_col1_html = '';
    if (isset($order['nama'])) {
        $formatted = formatField('nama', $order['nama']);
        $row1_col1_html .= '<tr><td width="50%"><strong>Customer</strong></td><td width="70%">: ' . $formatted['display_value'] . '</td></tr>';
    }
    if (isset($order['ukuran'])) {
        $formatted = formatField('ukuran', $order['ukuran']);
        $row1_col1_html .= '<tr><td width="50%"><strong>Ukuran</strong></td><td width="70%">: ' . $formatted['display_value'] . '</td></tr>';
    }
    if (isset($order['kode_pisau'])) {
        $formatted = formatField('kode_pisau', $order['kode_pisau']);
        $row1_col1_html .= '<tr><td width="50%"><strong>Kode Pisau</strong></td><td width="70%">: ' . $formatted['display_value'] . '</td></tr>';
    }
    if (isset($order['quantity'])) {
        $formatted = formatField('quantity', $order['quantity']);
        $row1_col1_html .= '<tr><td width="50%"><strong>Quantity</strong></td><td width="70%">: ' . $formatted['display_value'] . '</td></tr>';
    }

    // Row 1 - Column 2
    $row1_col2_html = '';
    if (isset($order['tanggal_kirim'])) {
        $formatted = formatField('tanggal_kirim', $order['tanggal_kirim']);
        $row1_col2_html .= '<tr><td width="50%"><strong>Tanggal Kirim</strong></td><td width="70%">: ' . $formatted['display_value'] . '</td></tr>';
    }
    if (isset($order['jam_kirim'])) {
        $formatted = formatField('jam_kirim', $order['jam_kirim']);
        $row1_col2_html .= '<tr><td width="50%"><strong>Jam Kirim</strong></td><td width="70%">: ' . $formatted['display_value'] . '</td></tr>';
    }
    if (isset($order['dikirim_dari'])) {
        $formatted = formatField('dikirim_dari', $order['dikirim_dari']);
        $row1_col2_html .= '<tr><td width="50%"><strong>Dikirim Dari</strong></td><td width="70%">: ' . $formatted['display_value'] . '</td></tr>';
    }
    if (isset($order['tujuan_kirim'])) {
        $formatted = formatField('tujuan_kirim', $order['tujuan_kirim']);
        $row1_col2_html .= '<tr><td width="50%"><strong>Tujuan Kirim</strong></td><td width="70%">: ' . $formatted['display_value'] . '</td></tr>';
    }

    // Row 2 - Column 1
    $row2_col1_html = '';
    if (isset($order['model_box'])) {
        $formatted = formatField('model_box', $order['model_box']);
        $row2_col1_html .= '<tr><td width="50%" style="font-size:24px;"><strong>Model Box</strong></td><td width="70%" style="font-size:24px;">: ' . $formatted['display_value'] . '</td></tr>';
    }
    if (isset($order['nama_box_lama'])) {
        $formatted = formatField('nama_box_lama', $order['nama_box_lama']);
        $row2_col1_html .= '<tr><td width="50%"><strong>Nama Pisau</strong></td><td width="70%">: ' . $formatted['display_value'] . '</td></tr>';
    }
    if (isset($order['jenis_board'])) {
        $formatted = formatField('jenis_board', $order['jenis_board']);
        $row2_col1_html .= '<tr><td width="50%" style="font-size:24px;"><strong>Board</strong></td><td width="70%" style="vertical-align:top; font-size:24px;">: ' . $formatted['display_value'] . '</td></tr>';
    }
    if (isset($order['cover_dlm'])) {
        $formatted = formatField('cover_dlm', $order['cover_dlm']);
        $row2_col1_html .= '<tr><td width="50%"><strong>Cover Dalam</strong></td><td width="70%" style="vertical-align:top;">: ' . $formatted['display_value'] . '</td></tr>';
    }

    // Row 2 - Column 2
    $row2_col2_html = '';
    if (isset($order['tanggal_dp'])) {
        $formatted = formatField('tanggal_dp', $order['tanggal_dp']);
        $row2_col2_html .= '<tr><td width="50%"><strong>Tanggal DP</strong></td><td width="70%">: ' . $formatted['display_value'] . '</td></tr>';
    }
    if (isset($order['pelunasan'])) {
        $formatted = formatField('pelunasan', $order['pelunasan']);
        $row2_col2_html .= '<tr><td width="50%"><strong>Pelunasan</strong></td><td width="70%">: ' . $formatted['display_value'] . '</td></tr>';
    }
    if (isset($order['ongkir'])) {
        $formatted = formatField('ongkir', $order['ongkir']);
        $row2_col2_html .= '<tr><td width="50%"><strong>Ongkir</strong></td><td width="70%">: ' . $formatted['display_value'] . '</td></tr>';
    }
    if (isset($order['packing'])) {
        $formatted = formatField('packing', $order['packing']);
        $row2_col2_html .= '<tr><td width="50%"><strong>Packing</strong></td><td width="70%">: ' . $formatted['display_value'] . '</td></tr>';
    }
    if (isset($order['biaya'])) {
        $formatted = formatField('biaya', $order['biaya']);
        $row2_col2_html .= '<tr><td width="50%"><strong>Biaya</strong></td><td width="70%">: ' . $formatted['display_value'] . '</td></tr>';
    }

    // Row 3 - Column 1 (Cover Luar)
    $row3_col1_html = '<tr><td colspan="2"><strong style="text-align:left; vertical-align:top; opacity:0.5;">Cover Luar</strong></td></tr>';
    if (isset($order['cover_lr'])) {
        $cover_lr_data = [];
        $parts = explode("\n", str_replace(['; ', ', '], "\n", $order['cover_lr']));
        foreach ($parts as $part) {
            $part = trim($part);
            if (empty($part)) continue;

            $colon_pos = strpos($part, ':');
            if ($colon_pos !== false) {
                $label = trim(substr($part, 0, $colon_pos));
                $value_item = trim(substr($part, $colon_pos + 1));
                $cover_lr_data[$label] = $value_item;
            } else {
                $cover_lr_data[] = $part;
            }
        }
        foreach ($cover_lr_data as $label => $value_item) {
            $display_label = is_numeric($label) ? 'Cover Luar Detail' : ucwords(str_replace('_', ' ', $label));
            $row3_col1_html .= '<tr><td width="50%"><strong>' . htmlspecialchars($display_label) . '</strong></td><td width="70%">: ' . htmlspecialchars($value_item) . '</td></tr>';
        }
    }

    // Row 3 - Column 2 (Aksesoris)
    $row3_col2_html = '<tr><td colspan="2"><strong style="text-align:left; vertical-align:top; opacity:0.5;">Aksesoris</strong></td></tr>';
    if (isset($order['aksesoris'])) {
        $aksesoris_data = [];
        $jenis = '';
        $ukuran = '';
        $warna = '';

        $parts = explode("\n", str_replace([";", "-"], "\n", $order['aksesoris']));
        foreach ($parts as $part) {
            $part = trim($part);
            if (empty($part)) continue;

            $colon_pos = strpos($part, ':');
            if ($colon_pos !== false) {
                $label = trim(substr($part, 0, $colon_pos));
                $value_item = trim(substr($part, $colon_pos + 1));

                if (strtolower($label) === 'jenis') {
                    $jenis = $value_item;
                } elseif (strtolower($label) === 'ukuran') {
                    $ukuran = $value_item;
                } elseif (strtolower($label) === 'warna') {
                    $warna = $value_item;
                } else {
                    $aksesoris_data[$label] = $value_item;
                }
            } else {
                $aksesoris_data[] = $part;
            }
        }

        if (!empty($jenis)) {
            $row3_col2_html .= '<tr><td colspan="2" style="vertical-align:top; font-size:24px;"><strong>' . strtoupper(htmlspecialchars($jenis)) . '</strong></td></tr>';
        }
        if (!empty($ukuran)) {
            $row3_col2_html .= '<tr><td width="50%" style="vertical-align:top; font-size:24px;"><strong>&nbsp;&nbsp;&nbsp;&nbsp;Ukuran</strong></td><td width="70%" style="vertical-align:top; font-size:24px;">: ' . htmlspecialchars($ukuran) . '</td></tr>';
        }
        if (!empty($warna)) {
            $row3_col2_html .= '<tr><td width="50%" style="vertical-align:top; font-size:24px;"><strong>&nbsp;&nbsp;&nbsp;&nbsp;Warna</strong></td><td width="70%" style="vertical-align:top; font-size:24px;">: ' . htmlspecialchars($warna) . '</td></tr>';
        }

        foreach ($aksesoris_data as $label => $value_item) {
            $display_label = is_numeric($label) ? 'Aksesoris Detail' : ucwords(str_replace('_', ' ', $label));
            $row3_col2_html .= '<tr><td width="50%" style="vertical-align:top; font-size:24px;"><strong>' . htmlspecialchars($display_label) . '</strong></td><td width="70%" style="vertical-align:top; font-size:24px;">: ' . htmlspecialchars($value_item) . '</td></tr>';
        }
    }
    if (isset($order['ket_aksesoris'])) {
        $formatted = formatField('ket_aksesoris', $order['ket_aksesoris']);
        $row3_col2_html .= '<tr><td width="50%"><strong>Keterangan</strong></td><td width="70%">: ' . $formatted['display_value'] . '</td></tr>';
    }

    // Row 4 - Column 2 (Dudukan, Logo, etc.)
    $row4_col2_html = '';
    if (isset($order['dudukan'])) {
        $formatted = formatField('dudukan', $order['dudukan']);
        $row4_col2_html .= '<tr><td width="50%" style="font-size:24px;"><strong>Dudukan</strong></td><td width="70%" style="font-size:24px;">: ' . $formatted['display_value'] . '</td></tr>';
    }
    if (isset($order['logo'])) {
        $formatted = formatField('logo', $order['logo']);
        $row4_col2_html .= '<tr><td width="50%" style="font-size:24px;"><strong>Logo</strong></td><td width="70%" style="font-size:24px;">: ' . $formatted['display_value'] . '</td></tr>';
    }
    if (isset($order['ukuran_poly'])) {
        $formatted = formatField('ukuran_poly', $order['ukuran_poly']);
        $row4_col2_html .= '<tr><td width="50%"><strong>Ukuran Poly</strong></td><td width="70%">: ' . $formatted['display_value'] . '</td></tr>';
    }
    if (isset($order['lokasi_poly'])) {
        $formatted = formatField('lokasi_poly', $order['lokasi_poly']);
        $row4_col2_html .= '<tr><td width="50%"><strong>Lokasi Poly</strong></td><td width="70%">: ' . $formatted['display_value'] . '</td></tr>';
    }
    if (isset($order['klise'])) {
        $formatted = formatField('klise', $order['klise']);
        $row4_col2_html .= '<tr><td width="50%"><strong>Klise</strong></td><td width="70%">: ' . $formatted['display_value'] . '</td></tr>';
    }

    // Row 7 (Feedback Customer)
    $row7_html = '';
    if (isset($order['feedback_cust'])) {
        $formatted = formatField('feedback_cust', $order['feedback_cust']);
        $row7_html .= '<tr><td width="30%"><strong>Feedback Customer</strong></td><td width="85%">: ' . $formatted['display_value'] . '</td></tr>';
    }

    // Row 7 (Keterangan)
    $row7_keterangan_html = '';
    if (isset($order['keterangan'])) {
        $formatted = formatField('keterangan', $order['keterangan']);
        $row7_keterangan_html .= '<tr><td width="30%"><strong>Keterangan</strong></td><td width="85%">: ' . $formatted['display_value'] . '</td></tr>';
    }

    // Row 8 (Sales PJ, Lokasi)
    $row8_html = '';
    if (isset($order['sales_pj'])) {
        $formatted = formatField('sales_pj', $order['sales_pj']);
        $row8_html .= '<tr><td width="30%"><strong></strong></td><td width="85%" style="text-align: right;">' . $formatted['display_value'] . ',</td></tr>';
    }
    if (isset($order['lokasi'])) {
        $formatted = formatField('lokasi', $order['lokasi']);
        $row8_html .= '<tr><td width="30%"><strong></strong></td><td width="85%" style="text-align: right;">' . $formatted['display_value'] . '</td></tr>';
    }

    return <<<HTML
<style>
    body { font-family: verdana, sans-serif; }
    .rounded-container {
        border: 1px solid #000;
        border-radius: 6.5px;
        overflow: hidden;
    }
    .rounded-container table {
        border-collapse: collapse;
        border-spacing: 0;
        width: 100%;
    }
    .rounded-container td {
        padding: 6px;
        vertical-align: top;
    }
</style>
<div style="width: 100%; overflow: auto; margin-bottom: 20px;">
    <div style="float: left;">
        <img src="$logo_base64" style="height: 50px; vertical-align: bottom; margin-right: 10px;">
        <h2 style="display: inline-block; vertical-align: bottom; margin: 0; font-size: 18px;">SALES CUSTOM ORDER (NO. $order_id_display)</h2>
    </div>
    <div style="float: right;">
        <p style="text-align: right; display: inline-block; vertical-align: bottom; margin: 0; font-size: 12px;">$dibuat_display</p>
    </div>
    <div style="clear: both;"></div>
</div>

<table width="100%" border="0" cellspacing="0" cellpadding="5">
    <tr>
        <td width="50%" valign="top" style="font-size:24px">
            <div class="rounded-container">
                <table width="100%" border="0" cellspacing="0" cellpadding="0">
                    $row1_col1_html
                </table>
            </div>
        </td>
        <td width="50%" valign="top">
            <div class="rounded-container">
                <table width="100%" border="0" cellspacing="0" cellpadding="0">
                    $row1_col2_html
                </table>
            </div>
        </td>
    </tr>

    <tr>
        <td width="50%" valign="top">
            <div class="rounded-container">
                <table width="100%" border="0" cellspacing="0" cellpadding="0">
                    $row2_col1_html
                </table>
            </div>
        </td>
        <td width="50%" valign="top">
            <div class="rounded-container">
                <table width="100%" border="0" cellspacing="0" cellpadding="0">
                    $row2_col2_html
                </table>
            </div>
        </td>
    </tr>

    <tr>
        <td width="50%" valign="top">
            <div class="rounded-container">
                <table width="100%" border="0" cellspacing="0" cellpadding="0">
                    $row3_col1_html
                </table>
            </div>
        </td>
        <td width="50%" valign="top">
            <div class="rounded-container">
                <table width="100%" border="0" cellspacing="0" cellpadding="0">
                    $row3_col2_html
                </table>
            </div>
        </td>
    </tr>

    <tr>
        <td width="50%" valign="top">
            <table width="100%" border="0" cellspacing="0" cellpadding="5">
            </table>
        </td>
        <td width="50%" valign="top">
            <div class="rounded-container">
                <table width="100%" border="0" cellspacing="0" cellpadding="0">
                    $row4_col2_html
                </table>
            </div>
        </td>
    </tr>

    <tr>
        <td colspan="2" valign="top">
            <div class="rounded-container">
                <table width="100%" border="0" cellspacing="0" cellpadding="0">
                    $row7_html
                </table>
            </div>
        </td>
    </tr>

    <tr>
        <td colspan="2" valign="top">
            <div class="rounded-container">
                <table width="100%" border="0" cellspacing="0" cellpadding="0">
                    $row7_keterangan_html
                </table>
            </div>
        </td>
    </tr>

    <tr>
        <td colspan="2" valign="top">
            <table width="100%" border="0" cellspacing="0" cellpadding="5">
                $row8_html
            </table>
        </td>
    </tr>
</table>

<div style="text-align: center; font-size: 10px; margin-top: 20px; opacity:50%;">Gracia Box 2025. Form Order Produksi.</div>
HTML;
}


// --- Main Execution ---

error_reporting(E_ALL);
ini_set('display_errors', 1);

checkUserAccess();

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo 'Error: Order ID not specified.';
    exit();
}
$order_id = $_GET['id'];

// The $pdo variable is expected to be available from config.php
$order = getOrderData($pdo, $order_id);

$html = generateFoHtml($order);

// Instantiate and use the dompdf class
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

ob_end_clean();
ini_set('display_errors', '0');
error_reporting(0);

// Output the generated PDF to Browser
$dompdf->stream('order_' . $order_id . '.pdf', ["Attachment" => false]);
exit();
?>