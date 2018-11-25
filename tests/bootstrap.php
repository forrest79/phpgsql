<?php declare(strict_types=1);

if (!$loader = include __DIR__ . '/../vendor/autoload.php') {
	echo 'Install dependencies using `composer update --dev`';
	exit(1);
}

Tester\Environment::setup();

define('PHPGSQL_CONNECTION_CONFIG', getenv('PHPGSQL_CONNECTION_CONFIG') ?: 'host=localhost port=5432 user=postgres password=postgres');
