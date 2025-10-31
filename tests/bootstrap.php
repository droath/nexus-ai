<?php

declare(strict_types=1);

define('ROOT', dirname(__DIR__));

require_once ROOT.'/vendor/autoload.php';

$files = ['.env', '.env.testing'];

(Dotenv\Dotenv::createImmutable(
    ROOT,
    $files
))->safeLoad();
