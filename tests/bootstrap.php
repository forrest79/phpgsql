<?php declare(strict_types=1);

$loader = __DIR__ . '/../vendor/autoload.php';

if (!\file_exists($loader)) {
	echo 'Install dependencies using `composer update --dev`';
	exit(1);
}

require $loader;

if (!\defined('__PHPSTAN_RUNNING__')) {
	Tester\Environment::setup();
}

require __DIR__ . '/prepare-db-config.php';
