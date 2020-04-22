<?php declare(strict_types=1);

if (\defined('__PHPSTAN_RUNNING__')) {
	return;
}

$loader = __DIR__ . '/../vendor/autoload.php';

if (!\file_exists($loader)) {
	echo 'Install dependencies using `composer update --dev`';
	exit(1);
}

require $loader;

Tester\Environment::setup();

require __DIR__ . '/prepare-db-config.php';
