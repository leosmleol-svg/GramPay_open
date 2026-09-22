<?php
// Copy outside the document root as ../grampay-mail.php. Never commit the filled file.
return [
    'GRAMPAY_SMTP_HOST' => 'mail.hosting.reg.ru',
    'GRAMPAY_SMTP_PORT' => '587',
    'GRAMPAY_SMTP_ENCRYPTION' => 'none',
    'GRAMPAY_SMTP_USER' => 'admin@grampay.net',
    'GRAMPAY_SMTP_PASS' => '', // Set the mailbox password only on the hosting server.
    'GRAMPAY_SMTP_FROM' => 'admin@grampay.net',
    'GRAMPAY_SMTP_FROM_NAME' => 'GRAMPAY',
];
