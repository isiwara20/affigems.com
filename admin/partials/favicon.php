<?php
/**
 * Favicon tags for admin pages (include inside <head>).
 */
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../../config/app.php';
}
?>
<link rel="icon" href="<?php echo BASE_URL; ?>assets/images/favicon-32.png" type="image/png" sizes="32x32">
<link rel="icon" href="<?php echo BASE_URL; ?>assets/images/favicon.png" type="image/png" sizes="64x64">
<link rel="apple-touch-icon" href="<?php echo BASE_URL; ?>assets/images/apple-touch-icon.png" sizes="180x180">
<link rel="shortcut icon" href="<?php echo BASE_URL; ?>assets/images/favicon.png" type="image/png">
