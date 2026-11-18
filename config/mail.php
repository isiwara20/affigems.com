<?php
/**
 * Email Configuration
 * 
 * Using hosting provider's SMTP server
 * Sends emails from info@arigato.lk
 * 
 * NOTE: When running on cPanel (production), use 'localhost' as MAIL_HOST
 * since the mail server is on the same server. This avoids DNS/SSL issues.
 * If sending from external server, use 'arigato.lk' or 'mail.arigato.lk'
 */

define('MAIL_HOST', 'localhost');
define('MAIL_PORT', 587);
define('MAIL_USERNAME', 'info@arigato.lk');
define('MAIL_PASSWORD', 'osloCC@123');
define('MAIL_FROM_EMAIL', 'info@arigato.lk');
define('MAIL_FROM_NAME', 'ARIGATO.LK');
define('MAIL_ENCRYPTION', ''); // Empty = auto-detect (SMTPAutoTLS will try TLS if available)
