<?php
require_once __DIR__ . '/vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;
error_reporting(E_ALL); ini_set('display_errors', 1);
require_once 'config.php';
session_start();

// Check if the user is logged in at all.
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Check if the user has the 'admin' role.
if ($_SESSION['role'] !== 'admin') {
    die('Access Denied: You do not have permission to view PDFs.');
}

// Check if ID is set
if (!isset($_GET['id'])) {
    die('Error: Order ID not specified.');
}

$order_id = $_GET['id'];

// Fetch the existing order
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$order_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {

    die('Error: Order not found.');

}




// Helper function to format display key and value

function formatField($key, $value) {

    $display_key = ucwords(str_replace(['_', 'dlm', 'lr', 'pj'], [' ', 'Dalam', 'Luar', 'PJ'], $key));

    $display_value = htmlspecialchars($value);



    if ($key === 'nama') {
        $display_value = strtoupper(htmlspecialchars($value));
    } else if ($key === 'cover_dlm') {

        $display_value = nl2br(htmlspecialchars(preg_replace('/(supplier|jenis|warna|gsm|ukuran):\s*/i', '', $value)));

    } else if ($key === 'cover_lr') {

        $display_value = nl2br(htmlspecialchars(str_replace("\\n", "; ", $value)));

    } else if ($key === 'ukuran') {
        $display_value = htmlspecialchars($value) . ' cm';
    } else if ($key === 'quantity') {

        $display_value = htmlspecialchars(str_replace(' pcs', '', $value)) . ' pcs';

    } else if ($key === 'kode_pisau') {
        $display_value = strtoupper(htmlspecialchars($value));
    } else if ($key === 'aksesoris') {

        $display_value = nl2br(htmlspecialchars($value));

    } else if ($key === 'biaya') {
        $display_value = 'Rp. ' . number_format((float)$value, 0, ',', '.');
    }

    return ['display_key' => $display_key, 'display_value' => $display_value];

}



// Instantiate and use the dompdf class

$options = new Options();

$options->set('isHtml5ParserEnabled', true);

$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);



// Generate HTML content for the PDF

$html = '<style>body { font-family: verdana, sans-serif; }</style>';
$html .= '<div style="width: 100%; overflow: auto; margin-bottom: 20px;">'; // Container for header elements
$html .= '<div style="float: left;">';
$html .= '<img src="' . __DIR__ . '/graciabox_logo_gray.jpeg" style="height: 50px; vertical-align: bottom; margin-right: 10px;">';
$html .= '<h2 style="display: inline-block; vertical-align: bottom; margin: 0; font-size: 18px;">SALES CUSTOM ORDER (NO. ' . htmlspecialchars($order['id']) . ')</h2>';
$html .= '</div>';
$html .= '<div style="float: right;">';
$html .= '<p style="text-align: right; display: inline-block; vertical-align: bottom; margin: 0; font-size: 12px;">' . htmlspecialchars($order['dibuat']) . '</p>';
$html .= '</div>';
$html .= '<div style="clear: both;"></div>'; // Clear floats
$html .= '</div>'; // End container

$html .= '<table width="100%" border="0" cellspacing="0" cellpadding="5">'; // Main table for 2 columns

// Row 1
$html .= '<tr>';
$html .= '<td width="50%" valign="top" style="font-size:24px">';
$html .= '<table width="100%"  border="1" cellspacing="0" cellpadding="5" style="border-radius: 10px;">';
// Customer
if (isset($order['nama'])) {
    $formatted = formatField('nama', $order['nama']);
    $html .= '<tr><td width="50%"><strong>Customer</strong></td><td width="70%">: ' . $formatted['display_value'] . '</td></tr>';
  }
// Ukuran
if (isset($order['ukuran'])) {
    $formatted = formatField('ukuran', $order['ukuran']);
    $html .= '<tr><td width="50%"><strong>Ukuran</strong></td><td width="70%">: ' . $formatted['display_value'] . '</td></tr>';
}
// Kode Pisau
if (isset($order['kode_pisau'])) {
    $formatted = formatField('kode_pisau', $order['kode_pisau']);
    $html .= '<tr><td width="50%"><strong>Kode Pisau</strong></td><td width="70%">: ' . $formatted['display_value'] . '</td></tr>';
}
// Quantity
if (isset($order['quantity'])) {
    $formatted = formatField('quantity', $order['quantity']);
    $html .= '<tr><td width="50%"><strong>Quantity</strong></td><td width="70%">: ' . $formatted['display_value'] . '</td></tr>';
}

$html .= '</table>';
$html .= '</td>';

$html .= '<td width="50%" valign="top">';
$html .= '<table width="100%" border="1" cellspacing="0" cellpadding="5" style="border-radius: 10px;">';
// Tanggal Kirim
if (isset($order['tanggal_kirim'])) {
    $formatted = formatField('tanggal_kirim', $order['tanggal_kirim']);
    $html .= '<tr><td width="50%"><strong>Tanggal Kirim</strong></td><td width="70%">: ' . $formatted['display_value'] . '</td></tr>';
}
// Jam Kirim
if (isset($order['jam_kirim'])) {
    $formatted = formatField('jam_kirim', $order['jam_kirim']);
    $html .= '<tr><td width="50%"><strong>Jam Kirim</strong></td><td width="70%">: ' . $formatted['display_value'] . '</td></tr>';
}
// Dikirim Dari
if (isset($order['dikirim_dari'])) {
    $formatted = formatField('dikirim_dari', $order['dikirim_dari']);
    $html .= '<tr><td width="50%"><strong>Dikirim Dari</strong></td><td width="70%">: ' . $formatted['display_value'] . '</td></tr>';
}
// Tujuan Kirim
if (isset($order['tujuan_kirim'])) {
    $formatted = formatField('tujuan_kirim', $order['tujuan_kirim']);
    $html .= '<tr><td width="50%"><strong>Tujuan Kirim</strong></td><td width="70%">: ' . $formatted['display_value'] . '</td></tr>';
}
$html .= '</table>';
$html .= '</td>';
$html .= '</tr>';

// Row 2
$html .= '<tr>';
$html .= '<td width="50%" valign="top">';
$html .= '<table width="100%" border="1" cellspacing="0" cellpadding="5" style="border-radius: 10px;">';
// Model Box
if (isset($order['model_box'])) {
    $formatted = formatField('model_box', $order['model_box']);
    $html .= '<tr><td width="50%"style= "font-size:24px;"><strong>Model Box</strong></td><td width="70%" style="font-size:24px;">: ' . $formatted['display_value'] . '</td></tr>';
}
// Nama Pisau
if (isset($order['nama_box_lama'])) {
    $formatted = formatField('nama_box_lama', $order['nama_box_lama']);
    $html .= '<tr><td width="50%"><strong>Nama Pisau</strong></td><td width="70%">: ' . $formatted['display_value'] . '</td></tr>';
}
// Jenis Board
if (isset($order['jenis_board'])) {
    $formatted = formatField('jenis_board', $order['jenis_board']);
    $html .= '<tr><td width="50%" style= "font-size:24px;"><strong>Board</strong></td><td width="70%" style = "vertical-align:top; font-size:24px;">: ' . $formatted['display_value'] . '</td></tr>';
}
// Cover Dalam
if (isset($order['cover_dlm'])) {
    $formatted = formatField('cover_dlm', $order['cover_dlm']);
    $html .= '<tr><td width="50%"><strong>Cover Dalam</strong></td><td width="70%" style = "vertical-align:top;">: ' . $formatted['display_value'] . '</td></tr>';
}
;
$html .= '</td>';
$html .= '<td width="50%" valign="top">';
$html .= '<table width="100%" border="1" cellspacing="0" cellpadding="5">';
// Tanggal DP
if (isset($order['tanggal_dp'])) {
    $formatted = formatField('tanggal_dp', $order['tanggal_dp']);
    $html .= '<tr><td width="50%"><strong>Tanggal DP</strong></td><td width="70%">: ' . $formatted['display_value'] . '</td></tr>';
}
// Pelunasan
if (isset($order['pelunasan'])) {
    $formatted = formatField('pelunasan', $order['pelunasan']);
    $html .= '<tr><td width="50%"><strong>Pelunasan</strong></td><td width="70%">: ' . $formatted['display_value'] . '</td></tr>';
}
// Ongkir
if (isset($order['ongkir'])) {
    $formatted = formatField('ongkir', $order['ongkir']);
    $html .= '<tr><td width="50%"><strong>Ongkir</strong></td><td width="70%">: ' . $formatted['display_value'] . '</td></tr>';
}
// Packing
if (isset($order['packing'])) {
    $formatted = formatField('packing', $order['packing']);
    $html .= '<tr><td width="50%"><strong>Packing</strong></td><td width="70%">: ' . $formatted['display_value'] . '</td></tr>';
}
// Biaya
if (isset($order['biaya'])) {
    $formatted = formatField('biaya', $order['biaya']);
    $html .= '<tr><td width="50%"><strong>Biaya</strong></td><td width="70%">: ' . $formatted['display_value'] . '</td></tr>';
}
$html .= '</table>';
$html .= '</td>';
$html .= '</tr>';

// Row 3
$html .= '<tr>';
$html .= '<td width="50%" valign="top">';
$html .= '<table width="100%" border="1" cellspacing="0" cellpadding="5">';
$html .= '<tr><td colspan="2"><strong style=" text-align:left; vertical-align:top; opacity:0.5;">Cover Luar</strong></td></tr>';
// Cover Luar
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
            // If no colon, treat the whole part as a value without a specific label
            $cover_lr_data[] = $part;
        }
    }

    

    // Now generate HTML rows for each remaining parsed cover_lr item
    foreach ($cover_lr_data as $label => $value_item) {
        $display_label = is_numeric($label) ? 'Cover Luar Detail' : ucwords(str_replace('_', ' ', $label));
        $html .= '<tr><td width="50%" style="vertical-align:top;"><strong>' . htmlspecialchars($display_label) . '</strong></td><td width="70%" style="vertical-align:top;">: ' . htmlspecialchars($value_item) . '</td></tr>';
    }
}

$html .= '</table>';
$html .= '</td>';
$html .= '<td width="50%" valign="top">';
$html .= '<table width="100%" border="1" cellspacing="0" cellpadding="5">';
$html .= '<tr><td colspan="2"><strong style=" text-align:left; vertical-align:top; opacity:0.5;">Aksesoris</strong></td></tr>';
// Aksesoris
if (isset($order['aksesoris'])) {
    $aksesoris_data = [];
    $jenis = '';
    $ukuran = '';
    $warna = '';

    $parts = explode("
", str_replace([";", "-"], "
", $order['aksesoris']));
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
        $html .= '<tr><td colspan="2" style="vertical-align:top; font-size:24px;"><strong>' . strtoupper(htmlspecialchars($jenis)) . '</strong></td></tr>';
    }
    if (!empty($ukuran)) {
        $html .= '<tr><td width="50%" style="vertical-align:top; font-size:24px;"><strong>&nbsp;&nbsp;&nbsp;&nbsp;ukuran</strong></td><td width="70%" style="vertical-align:top; font-size:24px;">: ' . htmlspecialchars($ukuran) . '</td></tr>';
    }
    if (!empty($warna)) {
        $html .= '<tr><td width="50%" style="vertical-align:top; font-size:24px;"><strong>&nbsp;&nbsp;&nbsp;&nbsp;warna</strong></td><td width="70%" style="vertical-align:top; font-size:24px;">: ' . htmlspecialchars($warna) . '</td></tr>';
    }

    // Now generate HTML rows for any other parsed aksesoris item
    foreach ($aksesoris_data as $label => $value_item) {
        $display_label = is_numeric($label) ? 'Aksesoris Detail' : ucwords(str_replace('_', ' ', $label));
        $html .= '<tr><td width="50%" style="vertical-align:top; font-size:24px;"><strong>' . htmlspecialchars($display_label) . '</strong></td><td width="70%" style="vertical-align:top; font-size:24px;">: ' . htmlspecialchars($value_item) . '</td></tr>';
    }
}
// Ket. Aksesoris
if (isset($order['ket_aksesoris'])) {
    $formatted = formatField('ket_aksesoris', $order['ket_aksesoris']);
    $html .= '<tr><td width="50%"><strong>Keterangan</strong></td><td width="70%">: ' . $formatted['display_value'] . '</td></tr>';
}


$html .= '</table>';
$html .= '</td>';
$html .= '</tr>';

// Row 4
$html .= '<tr>';
$html .= '<td width="50%" valign="top">';
$html .= '<table width="100%" border="0" cellspacing="0" cellpadding="5">';

$html .= '</table>';
$html .= '</td>';
$html .= '<td width="50%" valign="top">';
$html .= '<table width="100%" border="1" cellspacing="0" cellpadding="5">';
// Dudukan
if (isset($order['dudukan'])) {
    $formatted = formatField('dudukan', $order['dudukan']);
    $html .= '<tr><td width="50%" style="font-size:24px;"><strong>Dudukan</strong></td><td width="70%" style="font-size:24px;">: ' . $formatted['display_value'] . '</td></tr>';
}
// Logo
if (isset($order['logo'])) {
    $formatted = formatField('logo', $order['logo']);
    $html .= '<tr><td width="50%" style="font-size:24px;"><strong>Logo</strong></td><td width="70%" style="font-size:24px;">: ' . $formatted['display_value'] . '</td></tr>';
}
// Ukuran Poly
if (isset($order['ukuran_poly'])) {
    $formatted = formatField('ukuran_poly', $order['ukuran_poly']);
    $html .= '<tr><td width="50%"><strong>Ukuran Poly</strong></td><td width="70%">: ' . $formatted['display_value'] . '</td></tr>';
}
// Lokasi Poly
if (isset($order['lokasi_poly'])) {
    $formatted = formatField('lokasi_poly', $order['lokasi_poly']);
    $html .= '<tr><td width="50%"><strong>Lokasi Poly</strong></td><td width="70%">: ' . $formatted['display_value'] . '</td></tr>';
}
// Klise
if (isset($order['klise'])) {
    $formatted = formatField('klise', $order['klise']);
    $html .= '<tr><td width="50%"><strong>Klise</strong></td><td width="70%">: ' . $formatted['display_value'] . '</td></tr>';
}
$html .= '</table>';
$html .= '</td>';
$html .= '</tr>';



// Row 7
$html .= '<tr>';
$html .= '<td colspan="2" valign="top">';
$html .= '<table width="100%" border="1" cellspacing="0" cellpadding="5">';
// FB Cust
if (isset($order['feedback_cust'])) {
    $formatted = formatField('feedback_cust', $order['feedback_cust']);
    $html .= '<tr><td width="30%"><strong>Feedback Customer</strong></td><td width="85%">: ' . $formatted['display_value'] . '</td></tr>';
}
$html .= '</table>';
$html .= '</td>';
$html .= '</tr>';

// Row 7
$html .= '<tr>';
$html .= '<td colspan="2" valign="top">';
$html .= '<table width="100%" border="1" cellspacing="0" cellpadding="5">';
// Keterangan
if (isset($order['keterangan'])) {
    $formatted = formatField('keterangan', $order['keterangan']);
    $html .= '<tr><td width="30%"><strong>Keterangan</strong></td><td width="85%">: ' . $formatted['display_value'] . '</td></tr>';
}
$html .= '</table>';
$html .= '</td>';
$html .= '</tr>';

// Row 8 (Spanning both columns for Keterangan, Sales PJ, Lokasi)
$html .= '<tr>';
$html .= '<td colspan="2" valign="top">';
$html .= '<table width="100%" border="0" cellspacing="0" cellpadding="5">';

// Sales PJ
if (isset($order['sales_pj'])) {
    $formatted = formatField('sales_pj', $order['sales_pj']);
    $html .= '<tr><td width="30%"><strong></strong></td><td width="85%" style="text-align: right;">' . $formatted['display_value'] . ',</td></tr>';
}
// Lokasi
if (isset($order['lokasi'])) {
    $formatted = formatField('lokasi', $order['lokasi']);
    $html .= '<tr><td width="30%"><strong></strong></td><td width="85%" style="text-align: right;">' . $formatted['display_value'] . '</td></tr>';
}
$html .= '</table>';
$html .= '</td>';
$html .= '</tr>';

$html .= '</table>'; // End Main table


$dompdf->loadHtml($html);

// (Optional) Setup the paper size and orientation
$dompdf->setPaper('A4', 'portrait');

// Render the HTML as PDF
$dompdf->render();

// Output the generated PDF to Browser
$dompdf->stream('order_' . $order_id . '.pdf', ["Attachment" => false]);

?>