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
define('MAIL_USERNAME', 'murshidmunzeer@gmail.com');
define('MAIL_PASSWORD', '');
define('MAIL_FROM_EMAIL', 'murshidmunzeer@gmail.com');
define('MAIL_FROM_NAME', 'Affinity Gems & Jewels');
define('MAIL_ENCRYPTION', ''); // Empty = auto-detect (SMTPAutoTLS will try TLS if available)
