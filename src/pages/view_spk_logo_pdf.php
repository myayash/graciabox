<?php
ob_start(); // Start output buffering at the very top to capture any stray output or errors.

// Set correct paths for autoload and config based on the file's location in src/pages/
require_once dirname(dirname(__DIR__)) . '/vendor/autoload.php';
require_once dirname(dirname(__DIR__)) . '/config/config.php';

use Dompdf\Dompdf;
use Dompdf\Options;
session_start();

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
 * Fetches SPK data from the database.
 *
 * @param PDO $pdo The PDO database connection object.
 * @param int $spk_id The ID of the SPK to fetch.
 * @return array The SPK data.
 */
function getSpkLogoData($pdo, $spk_id) {
    $stmt = $pdo->prepare("SELECT * FROM spk_logo WHERE id = ?");
    $stmt->execute([$spk_id]);
    $spk = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$spk) {
        http_response_code(404);
        echo 'Error: SPK not found.';
        exit();
    }
    return $spk;
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
 * Formats a key-value pair for display.
 *
 * @param string $key The field key.
 * @param string $value The field value.
 * @return array An array with 'display_key' and 'display_value'.
 */
function formatField($key, $value) {
    $display_key = ucwords(str_replace('_', ' ', $key));
    $display_value = htmlspecialchars($value);

    if ($key === 'nama') {
        $display_value = strtoupper(htmlspecialchars($value));
    } else if ($key === 'logo') {
        $display_key = 'Warna Poly';
    }

    return ['display_key' => $display_key, 'display_value' => $display_value];
}

/**
 * Generates the HTML content for the SPK PDF.
 *
 * @param array $spk The SPK data.
 * @return string The generated HTML.
 */
function generateSpkHtml($spk) {
    $logo_base64 = getBase64Image(dirname(dirname(__DIR__)) . '/public/assets/images/graciabox_logo_gray.jpeg');

    $fields_col1_html = '';
    $fields_col1 = ['nama', 'ukuran', 'model_box', 'quantity'];
    foreach($fields_col1 as $field) {
        if (isset($spk[$field])) {
            $formatted = formatField($field, $spk[$field]);
            $fields_col1_html .= '<tr><td width="40%"><strong>' . $formatted['display_key'] . '</strong></td><td class="data-value">: ' . $formatted['display_value'] . '</td></tr>';
        }
    }

    $fields_col2_html = '';
    $fields_col2 = ['logo', 'ukuran_poly', 'lokasi_poly', 'klise'];
    foreach($fields_col2 as $field) {
        if (isset($spk[$field])) {
            $formatted = formatField($field, $spk[$field]);
            $fields_col2_html .= '<tr><td width="40%"><strong>' . $formatted['display_key'] . '</strong></td><td class="data-value">: ' . $formatted['display_value'] . '</td></tr>';
        }
    }

    $logo_image_gallery_html = '';
    if (!empty($spk['logo_img'])) {
        $logo_image_gallery_html .= '<h3>Gambar Logo</h3><div class="image-gallery"><div class="image-list">';
        $images = explode(',', $spk['logo_img']);
        foreach ($images as $image) {
            $image_name = trim($image);
            $image_path = dirname(dirname(__DIR__)) . '/public/uploads/' . $image_name;
            $base64 = getBase64Image($image_path);
            if ($base64) {
                $logo_image_gallery_html .= '<div class="image-item"><img src="' . $base64 . '" alt="Logo image"></div>';
            }
        }
        $logo_image_gallery_html .= '</div></div>';
    }

    $poly_image_gallery_html = '';
    if (!empty($spk['poly_img'])) {
        $poly_image_gallery_html .= '<h3>Gambar Poly</h3><div class="image-gallery"><div class="image-list">';
        $polyImages = explode(',', $spk['poly_img']);
        foreach ($polyImages as $pimage) {
            $pimage_name = trim($pimage);
            $pimage_path = dirname(dirname(__DIR__)) . '/public/uploads/' . $pimage_name;
            $pbase64 = getBase64Image($pimage_path);
            if ($pbase64) {
                $poly_image_gallery_html .= '<div class="image-item"><img src="' . $pbase64 . '" alt="Poly image"></div>';
            }
        }
        $poly_image_gallery_html .= '</div></div>';
    }

    $spk_id_display = htmlspecialchars($spk['id']);
    $dibuat_display = htmlspecialchars($spk['dibuat']);

    return <<<HTML
<style>
body { font-family: verdana, sans-serif; }
.container { border: 1px solid #000; border-radius: 5px; padding: 10px; box-sizing: border-box; width: 100%; overflow: hidden; }
table { border-collapse: collapse; width: 100%; }
td, th { padding: 6px; }
.header-table td { vertical-align: middle; }
.data-table { margin-top: 20px; }
.data-table td { vertical-align: top; }
.data-table strong { font-size: 24px; }
.data-value { font-size: 24px; }
.image-gallery { margin-top: 10px; }
.image-gallery .image-item { display: inline-block; vertical-align: top; }
.image-gallery .image-item img { max-width: 150px; max-height: 150px; margin: 5px; border: 1px solid #ccc; display: block; }
</style>
<div class="container">

<div style="width: 100%; overflow: auto; margin-bottom: 20px;">
<div style="float: left;">
<img src="$logo_base64" style="height: 50px; vertical-align: bottom; margin-right: 10px;">
<h2 style="display: inline-block; vertical-align: bottom; margin: 0; font-size: 18px;">SPK LOGO (NO. $spk_id_display)</h2>
</div>
<div style="float: right;">
<p style="text-align: right; display: inline-block; vertical-align: bottom; margin: 0; font-size: 12px;">$dibuat_display</p>
</div>
<div style="clear: both;"></div>
</div>

<table class="data-table">

<tr><td width="50%" valign="top">
<table>
$fields_col1_html
</table>
</td>

<td width="50%" valign="top">
<table>
$fields_col2_html
</table>
</td></tr>

</table>

$logo_image_gallery_html
$poly_image_gallery_html

</div>

<div style="text-align: center; font-size: 10px; margin-top: 20px; opacity:50%;">Gracia Box 2025. Surat Perintah Kerja Produksi Logo.</div>
HTML;
}

// --- Main Execution ---

// Enable error reporting for debugging. Errors will be captured by the output buffer.
error_reporting(E_ALL);
ini_set('display_errors', 1);

checkUserAccess();

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo 'Error: SPK ID not specified.';
    exit();
}
$spk_id = $_GET['id'];

// The $pdo variable is expected to be available from config.php
$spk = getSpkLogoData($pdo, $spk_id);

$html = generateSpkHtml($spk);

// Instantiate and use the dompdf class
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true); // For external resources, though we use base64
$dompdf = new Dompdf($options);

$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Clean any output that was buffered, including errors, before sending the PDF.
ob_end_clean();

// Disable error display before sending binary content to prevent corrupting the PDF.
ini_set('display_errors', '0');
error_reporting(0);

// Output the generated PDF to Browser
$dompdf->stream('spk_logo_' . $spk_id . '.pdf', ["Attachment" => false]);
exit();
?>