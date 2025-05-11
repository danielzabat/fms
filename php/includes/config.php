<?php
// config.php

// Gmail SMTP Configurations
define('SMTP_EMAIL', 'sisfinance3220@gmail.com');
define('SMTP_NAME', 'Student Information System (Finance) BSIT-3220');
define('SMTP_PASSWORD', 'btwh nbqe uwss ipsq'); // Gmail app password

// Encryption Key & IV
define('ENCRYPTION_KEY', '06453ab6372124dc0476d483de181341');
define('ENCRYPTION_IV', substr(hash('sha256', 'sis-3220-iv-seed'), 0, 16));
