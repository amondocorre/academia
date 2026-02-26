<?php
define('ENVIRONMENT', 'development');
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
require_once dirname(__FILE__) . '/index.php';
// But this requires mapping routing or just bootstrapping.
