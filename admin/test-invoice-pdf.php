<?php
/**
 * Admin diagnostic — test invoice PDF generation on server.
 */
require_once __DIR__ . '/../config/app.php';

header('Content-Type: text/plain; charset=UTF-8');

if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    http_response_code(403);
    exit('Unauthorized');
}

$orderId = (int)($_GET['order_id'] ?? 0);
echo "ARIGATO.LK Invoice PDF Diagnostic\n";
echo "============================\n\n";

$fpdfPath = realpath(__DIR__ . '/../libs/FPDF/fpdf.php');
$genPath = realpath(__DIR__ . '/../services/InvoicePdfGenerator.php');
$storageDir = lumak_invoice_storage_dir();

echo 'FPDF path: ' . ($fpdfPath ?: 'MISSING') . "\n";
echo 'InvoicePdfGenerator: ' . ($genPath ?: 'MISSING') . "\n";
echo 'Storage dir: ' . $storageDir . "\n";
echo 'Storage writable: ' . (is_writable($storageDir) ? 'yes' : 'NO') . "\n";

$fontDir = realpath(__DIR__ . '/../libs/FPDF/font');
echo 'Font dir: ' . ($fontDir ?: 'MISSING') . "\n";
foreach (['helvetica.json', 'helveticab.json', 'helveticai.json', 'helveticabi.json'] as $fontFile) {
    $full = $fontDir ? $fontDir . DIRECTORY_SEPARATOR . $fontFile : '';
    echo '  ' . $fontFile . ': ' . ($full && is_file($full) ? 'OK' : 'MISSING') . "\n";
}
echo "\n";

if ($orderId <= 0) {
    echo "Usage: test-invoice-pdf.php?order_id=123\n";
    exit;
}

echo "Testing order_id={$orderId}...\n\n";

$result = lumak_invoice_prepare_pdf_for_payment($orderId);

if ($result) {
    echo "SUCCESS\n";
    echo 'Filename: ' . ($result['filename'] ?? '') . "\n";
    echo 'PDF bytes: ' . strlen($result['data'] ?? '') . "\n";
    echo 'Saved path: ' . ($result['path'] ?? 'not saved') . "\n";
    if (!empty($result['path']) && is_file($result['path'])) {
        echo 'File on disk: yes (' . filesize($result['path']) . " bytes)\n";
    }
} else {
    echo "FAILED — check assets/uploads/invoices/invoice.log\n";
}

echo "\n--- invoice.log (last 20 lines) ---\n";
$logFile = $storageDir . '/invoice.log';
if (is_file($logFile)) {
    $lines = file($logFile, FILE_IGNORE_NEW_LINES);
    echo implode("\n", array_slice($lines, -20));
} else {
    echo "(no log file yet)\n";
}
