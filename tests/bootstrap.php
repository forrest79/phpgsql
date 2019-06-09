<?php declare(strict_types=1);

$loader = __DIR__ . '/../vendor/autoload.php';

if (!\file_exists($loader)) {
	echo 'Install dependencies using `composer update --dev`';
	exit(1);
}

require $loader;

Tester\Environment::setup();

\define('PHPGSQL_CONNECTION_CONFIG', \getenv('PHPGSQL_CONNECTION_CONFIG') ?: 'host=localhost port=5432 user=postgres password=postgres');
