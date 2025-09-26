<?php declare(strict_types=1);

$loader = __DIR__ . '/../vendor/autoload.php';

if (!\file_exists($loader)) {
	echo 'Install dependencies using `composer update --dev`';
	exit(1);
}

require $loader;

require_once __DIR__ . '/../tests/prepare-db-config.php';
