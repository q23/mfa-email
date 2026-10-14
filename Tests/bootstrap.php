<?php

declare(strict_types=1);

// Minimal autoloader for unit tests. The classes under test only declare TYPO3
// types and never load TYPO3 core classes at runtime, so no core install is needed.
spl_autoload_register(static function (string $class): void {
    $map = [
        'Q23\\MfaEmail\\Tests\\' => __DIR__ . '/',
        'Q23\\MfaEmail\\' => dirname(__DIR__) . '/Classes/',
    ];
    foreach ($map as $prefix => $dir) {
        if (str_starts_with($class, $prefix)) {
            $file = $dir . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
            if (is_file($file)) {
                require $file;
            }
            return;
        }
    }
});
