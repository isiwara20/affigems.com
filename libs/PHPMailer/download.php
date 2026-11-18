<?php
/**
 * PHPMailer Downloader
 * Run this ONCE in your browser to download PHPMailer files:
 * http://localhost/Damaz_Ecommerce/libs/PHPMailer/download.php
 * 
 * After downloading, DELETE this file.
 */

$files = [
    'PHPMailer.php' => 'https://raw.githubusercontent.com/PHPMailer/PHPMailer/master/src/PHPMailer.php',
    'SMTP.php' => 'https://raw.githubusercontent.com/PHPMailer/PHPMailer/master/src/SMTP.php',
    'Exception.php' => 'https://raw.githubusercontent.com/PHPMailer/PHPMailer/master/src/Exception.php',
];

echo "<h2>Downloading PHPMailer files...</h2>";

$dir = __DIR__;
$success = true;

foreach ($files as $filename => $url) {
    $content = file_get_contents($url);
    if ($content !== false) {
        file_put_contents($dir . '/' . $filename, $content);
        echo "<p style='color: green;'>âœ“ Downloaded: $filename</p>";
    } else {
        echo "<p style='color: red;'>âœ— Failed to download: $filename</p>";
        $success = false;
    }
}

if ($success) {
    echo "<h3 style='color: green;'>All files downloaded successfully!</h3>";
    echo "<p>You can now delete this file (download.php) and the email system is ready.</p>";
} else {
    echo "<h3 style='color: red;'>Some files failed. Please check your internet connection and try again.</h3>";
}
