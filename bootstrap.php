<?php

require_once 'src/autoload.php';
require_once 'vendor/autoload.php';

ini_set('serialize_precision', 10);

set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, $severity, $severity, $file, $line);
});