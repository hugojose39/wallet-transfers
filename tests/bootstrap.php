<?php

declare(strict_types=1);

ini_set('display_errors', 'on');
ini_set('display_startup_errors', 'on');
error_reporting(E_ALL);

! defined('BASE_PATH') && define('BASE_PATH', dirname(__DIR__, 1));

require BASE_PATH . '/vendor/autoload.php';

if (! function_exists('env')) {
    function env($key, $default = null)
    {
        return \Hyperf\Support\env($key, $default);
    }
}

// Garante que o scan cache esteja desativado em testes
putenv('SCAN_CACHEABLE=false');
$_ENV['SCAN_CACHEABLE'] = 'false';
putenv('APP_ENV=testing');
$_ENV['APP_ENV'] = 'testing';

! defined('SWOOLE_HOOK_FLAGS') && define('SWOOLE_HOOK_FLAGS', Hyperf\Engine\DefaultOption::hookFlags());

Hyperf\Di\ClassLoader::init();

$container = require BASE_PATH . '/config/container.php';
