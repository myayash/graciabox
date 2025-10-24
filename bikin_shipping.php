<?php
// Removed: this endpoint is deprecated. Shipping fields have been moved into bikin_fo.php.
// Return HTTP 410 Gone to indicate the endpoint is intentionally removed.
http_response_code(410);
header('Content-Type: text/plain; charset=utf-8');
echo "410 Gone - bikin_shipping.php has been removed. Use bikin_fo.php instead.";
exit();
