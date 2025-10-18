<?php
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

// Load Composer's autoloader
require_once __DIR__ . '/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Instantiate and use the dompdf class
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);

// Generate HTML content for the PDF
$html = '<h1 style="text-align: center;">Order Details (ID: ' . htmlspecialchars($order['id']) . ')</h1>';
$html .= '<table width="100%" border="1" cellspacing="0" cellpadding="5">';

foreach ($order as $key => $value) {
    // Skip is_archived for display
    if ($key === 'is_archived') {
        continue;
    }

    $display_key = ucwords(str_replace(['_', 'dlm', 'lr', 'pj'], [' ', 'Dalam', 'Luar', 'PJ'], $key));
    $display_value = htmlspecialchars($value);

    // Special formatting for specific fields
    if ($key === 'cover_dlm') {
        $display_value = nl2br(htmlspecialchars(preg_replace('/(supplier|jenis|warna|gsm|ukuran):\s*/i', '', $value)));
    } else if ($key === 'cover_lr') {
        $display_value = nl2br(htmlspecialchars(str_replace("\n", "; ", $value)));
    } else if ($key === 'quantity') {
        $display_value = htmlspecialchars(str_replace(' pcs', '', $value));
    }

    $html .= '<tr>';
    $html .= '<td width="30%"><strong>' . $display_key . ':</strong></td>';
    $html .= '<td width="70%">' . $display_value . '</td>';
    $html .= '</tr>';
}

$html .= '</table>';

$dompdf->loadHtml($html);

// (Optional) Setup the paper size and orientation
$dompdf->setPaper('A4', 'portrait');

// Render the HTML as PDF
$dompdf->render();

// Output the generated PDF to Browser
$dompdf->stream('order_' . $order_id . '.pdf', ["Attachment" => false]);

?>
