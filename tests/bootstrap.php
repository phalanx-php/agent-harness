<?php

declare(strict_types=1);

$skipVendor = getenv('PHALANX_STARTER_SKIP_VENDOR') === '1';
$autoload = dirname(__DIR__) . '/vendor/autoload.php';

if (!is_file($autoload)) {
    $autoload = dirname(__DIR__, 3) . '/phalanx/vendor/autoload.php';
}

if (!$skipVendor && !function_exists('Phalanx\\Tui\\Kit\\text') && !is_file($autoload)) {
    throw new RuntimeException('Cannot find autoload.php');
}

if (!$skipVendor && !function_exists('Phalanx\\Tui\\Kit\\text')) {
    require $autoload;
}

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\Collab\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $path = dirname(__DIR__) . '/app/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($path)) {
        require $path;
    }
});
