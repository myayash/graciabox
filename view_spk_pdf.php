<?php
ob_start();
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
    die('Error: SPK ID not specified.');
}

$spk_id = $_GET['id'];

// Fetch the existing SPK data
$stmt = $pdo->prepare("SELECT * FROM spk_dudukan WHERE id = ?");
$stmt->execute([$spk_id]);
$spk = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$spk) {
    die('Error: SPK not found.');
}

// Helper function to format display key and value
function formatField($key, $value) {
    $display_key = ucwords(str_replace('_', ' ', $key));
    $display_value = htmlspecialchars($value);

    if ($key === 'nama') {
        $display_value = strtoupper(htmlspecialchars($value));
    } else if ($key === 'ukuran') {
        $display_value = htmlspecialchars($value) . ' cm';
    }

    return ['display_key' => $display_key, 'display_value' => $display_value];
}

// Instantiate and use the dompdf class
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);

// Generate HTML content for the PDF
$html = '<style>
body { font-family: verdana, sans-serif; }
.container { border: 0px solid #000; border-radius: 5px; padding: 10px; }
table { border-collapse: collapse; width: 100%; }
td, th { padding: 6px; }
.header-table td { vertical-align: middle; }
.data-table { margin-top: 20px; }
.data-table td { vertical-align: top; }
.data-table strong { font-size: 18px; }
.data-value { font-size: 18px; }
.image-gallery { margin-top: 10px; }
.image-gallery img { max-width: 150px; max-height: 150px; margin: 5px; border: 1px solid #ccc; }
</style>';

$html .= '<div class="container">';

// Header
$html .= '<table class="header-table"><tr>';
$logo_path = __DIR__ . '/graciabox_logo_gray.jpeg';
$logo_type = pathinfo($logo_path, PATHINFO_EXTENSION);
$logo_data = file_get_contents($logo_path);
$logo_base64 = 'data:image/' . $logo_type . ';base64,' . base64_encode($logo_data);
$html .= '<td><img src="' . $logo_base64 . '" style="height: 50px;"></td>';
$html .= '<td style="text-align: center;"><h2>SPK DUDUKAN (NO. ' . htmlspecialchars($spk['id']) . ')</h2></td>';
$html .= '<td style="text-align: right;">' . htmlspecialchars($spk['dibuat']) . '</td>';
$html .= '</tr></table>';


// Data section
$html .= '<table class="data-table">';

// Column 1
$html .= '<tr><td width="50%" valign="top">';
$html .= '<table>';
$fields_col1 = ['nama', 'ukuran', 'model_box', 'quantity'];
foreach($fields_col1 as $field) {
    if (isset($spk[$field])) {
        $formatted = formatField($field, $spk[$field]);
        $html .= '<tr><td width="40%"><strong>' . $formatted['display_key'] . '</strong></td><td class="data-value">: ' . $formatted['display_value'] . '</td></tr>';
    }
}
$html .= '</table>';
$html .= '</td>';

// Column 2
$html .= '<td width="50%" valign="top">';
$html .= '<table>';
$fields_col2 = []; // Dudukan and Jumlah Layer moved below
foreach($fields_col2 as $field) {
    if (isset($spk[$field])) {
        $formatted = formatField($field, $spk[$field]);
        $html .= '<tr><td width="40%"><strong>' . $formatted['display_key'] . '</strong></td><td class="data-value">: ' . $formatted['display_value'] . '</td></tr>';
    }
}
$html .= '</table>';
$html .= '</td></tr>';

$html .= '</table>';

// Image Gallery
if (!empty($spk['dudukan_img'])) {
    $html .= '<h3>Gambar Dudukan</h3>';
    $html .= '<div class="image-gallery">';
    $images = explode(',', $spk['dudukan_img']);
    
    foreach ($images as $image) {
        $image_name = trim($image);
        $image_path = __DIR__ . '/uploads/' . $image_name;

        if (is_file($image_path)) {
            $image_data = @file_get_contents($image_path);
            if ($image_data !== false) {
                $type = pathinfo($image_name, PATHINFO_EXTENSION);
                $base64 = 'data:image/' . $type . ';base64,' . base64_encode($image_data);
                $html .= '<img src="' . $base64 . '">';
            }
        }
    }
    $html .= '</div>';
}

// Dudukan and Jumlah Layer table
if (isset($spk['jumlah_layer']) && is_numeric($spk['jumlah_layer']) && $spk['jumlah_layer'] > 0) {
    $html .= '<table class="data-table" style="margin-top: 20px; width: 100%; border: 1px solid #ccc;">';
    $html .= '<thead><tr><th style="text-align: center; border-bottom: 1px solid #ccc;">Jumlah Layer</th><th style="text-align: center; border-bottom: 1px solid #ccc;">Dudukan</th><th style="text-align: center; border-bottom: 1px solid #ccc;">Keterangan</th></tr></thead>';
    $html .= '<tbody>';
    $dudukan_value = isset($spk['dudukan']) ? htmlspecialchars($spk['dudukan']) : '';
    for ($i = 0; $i < $spk['jumlah_layer']; $i++) {
        $html .= '<tr>';
        $html .= '<td style="text-align: center; border-top: 1px solid #ccc;">' . ($i + 1) . '</td>';
        $html .= '<td style="text-align: center; border-top: 1px solid #ccc;">' . $dudukan_value . '</td>';
        $html .= '<td style="text-align: center; border-top: 1px solid #ccc;"></td>';
        $html .= '</tr>';
    }
    $html .= '</tbody>';
    $html .= '</table>';
}


$html .= '</div>'; //end container

$html .= '<div style="text-align: center; font-size: 10px; margin-top: 20px; opacity:50%;">Gracia Box 2025. Form Order Produksi.</div>';

$dompdf->loadHtml($html);

// (Optional) Setup the paper size and orientation
$dompdf->setPaper('A4', 'portrait');

// Render the HTML as PDF
$dompdf->render();

ob_end_clean();
ini_set('display_errors', '0');

// Output the generated PDF to Browser
$dompdf->stream('spk_dudukan_' . $spk_id . '.pdf', ["Attachment" => false]);
?>