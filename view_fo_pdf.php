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



    if ($key === 'cover_dlm') {

        $display_value = nl2br(htmlspecialchars(preg_replace('/(supplier|jenis|warna|gsm|ukuran):\s*/i', '', $value)));

    } else if ($key === 'cover_lr') {

        $display_value = nl2br(htmlspecialchars(str_replace("\\n", "; ", $value)));

    } else if ($key === 'quantity') {

        $display_value = htmlspecialchars(str_replace(' pcs', '', $value));

    } else if ($key === 'aksesoris') {

        $display_value = nl2br(htmlspecialchars(preg_replace('/(jenis|ukuran|warna):\s*/i', '', $value)));

    }

    return ['display_key' => $display_key, 'display_value' => $display_value];

}



// Instantiate and use the dompdf class

$options = new Options();

$options->set('isHtml5ParserEnabled', true);

$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);



// Generate HTML content for the PDF

$html = '<style>body { font-family: helvetica, sans-serif; }</style><h1 style="text-align: center;">SALES CUSTOM ORDER (NO. ' . htmlspecialchars($order['id']) . ')</h1>';
$html .= '<p style="text-align: center;">' . htmlspecialchars($order['dibuat']) . '</p>';

$html .= '<table width="100%" border="0" cellspacing="0" cellpadding="5">'; // Main table for 2 columns

$html .= '<tr>'; // Row for the two columns



// Column 1

$html .= '<td width="50%" valign="top">';

$html .= '<table width="100%" border="1" cellspacing="0" cellpadding="5">';



// Column 1, Group 1

$group1_col1_fields = [

    'Nama' => 'nama',

    'Ukuran (cm)' => 'ukuran',

    'Kode Pisau' => 'kode_pisau',

    'Quantity' => 'quantity'

];

foreach ($group1_col1_fields as $display_name => $db_key) {

    if (isset($order[$db_key])) {

        $formatted = formatField($db_key, $order[$db_key]);

        $html .= '<tr><td width="30%"><strong>' . $display_name . ':</strong></td><td width="70%">' . $formatted['display_value'] . '</td></tr>';

    }

}

$html .= '</table><br/>'; // End Group 1, add a break



// Column 1, Group 2

$html .= '<table width="100%" border="1" cellspacing="0" cellpadding="5">';

$group2_col1_fields = [

    'Model Box' => 'model_box',

    'Nama Box Lama' => 'nama_box_lama',

    'Jenis Board' => 'jenis_board',

    'Cover Dalam' => 'cover_dlm',

    'Cover Luar' => 'cover_lr'

];

foreach ($group2_col1_fields as $display_name => $db_key) {

    if (isset($order[$db_key])) {

        $formatted = formatField($db_key, $order[$db_key]);

        $html .= '<tr><td width="30%"><strong>' . $display_name . ':</strong></td><td width="70%">' . $formatted['display_value'] . '</td></tr>';

    }

}

$html .= '</table><br/>'; // End Group 2, add a break



// Column 1, Group 3

$html .= '<table width="100%" border="1" cellspacing="0" cellpadding="5">';

$group3_col1_fields = [

    'Aksesoris' => 'aksesoris',

    'Dudukan' => 'dudukan',

    'Logo' => 'logo',

    'Ukuran Poly' => 'ukuran_poly',

    'Lokasi Poly' => 'lokasi_poly',

    'Klise' => 'klise'

];

        foreach ($group3_col1_fields as $display_name => $db_key) {

            if (isset($order[$db_key])) {

                $formatted = formatField($db_key, $order[$db_key]);

                $html .= '<tr><td width="30%"><strong>' . $display_name . ':</strong></td><td width="70%">' . $formatted['display_value'] . '</td></tr>';

            }

        }

$html .= '</table>'; // End Group 3

$html .= '</td>'; // End Column 1



// Column 2

$html .= '<td width="50%" valign="top">';

$html .= '<table width="100%" border="1" cellspacing="0" cellpadding="5">';



// Column 2, Group 1

$group1_col2_fields = [

    'Tanggal Kirim' => 'tanggal_kirim',

    'Jam Kirim' => 'jam_kirim',

    'Dikirim Dari' => 'dikirim_dari',

    'Tujuan Kirim' => 'tujuan_kirim'

];

foreach ($group1_col2_fields as $display_name => $db_key) {

    if (isset($order[$db_key])) {

        $formatted = formatField($db_key, $order[$db_key]);

        $html .= '<tr><td width="30%"><strong>' . $display_name . ':</strong></td><td width="70%">' . $formatted['display_value'] . '</td></tr>';

    }

}

$html .= '</table>'; // End Group 1

$html .= '</td>'; // End Column 2

$html .= '</tr>'; // End Row for the two columns



// Bottom Group (spanning both columns)

$html .= '<tr><td colspan="2">';

$html .= '<table width="100%" border="1" cellspacing="0" cellpadding="5">';

$bottom_group_fields = [

    'Keterangan' => 'keterangan',

    'Sales PJ' => 'sales_pj',

    'Lokasi' => 'lokasi'

];

foreach ($bottom_group_fields as $display_name => $db_key) {

    if (isset($order[$db_key])) {

        $formatted = formatField($db_key, $order[$db_key]);

        $html .= '<tr><td width="15%"><strong>' . $display_name . ':</strong></td><td width="85%">' . $formatted['display_value'] . '</td></tr>';

    }

}

$html .= '</table>';

$html .= '</td></tr>'; // End Bottom Group

$html .= '</table>'; // End Main table



$dompdf->loadHtml($html);

// (Optional) Setup the paper size and orientation
$dompdf->setPaper('A4', 'portrait');

// Render the HTML as PDF
$dompdf->render();

// Output the generated PDF to Browser
$dompdf->stream('order_' . $order_id . '.pdf', ["Attachment" => false]);

?>