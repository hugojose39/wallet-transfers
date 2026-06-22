#!/usr/bin/env php
<?php

declare(strict_types=1);

ini_set('display_errors', 'on');
ini_set('display_startup_errors', 'on');

error_reporting(E_ALL);
date_default_timezone_set('America/Sao_Paulo');

!defined('BASE_PATH') && define('BASE_PATH', dirname(__DIR__, 1));
!defined('SWOOLE_HOOK_FLAGS') && define('SWOOLE_HOOK_FLAGS', SWOOLE_HOOK_ALL);

require BASE_PATH . '/vendor/autoload.php';

if (! function_exists('env')) {
    function env($key, $default = null) {
        return \Hyperf\Support\env($key, $default);
    }
}

Hyperf\Di\ClassLoader::init(
    handler: new Hyperf\Di\ScanHandler\PcntlScanHandler()
);

$application = require_once BASE_PATH . '/config/container.php';

$application->run();
