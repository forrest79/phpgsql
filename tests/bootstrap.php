<?php declare(strict_types=1);

// The Nette Tester command-line runner can be
// invoked through the command: ../vendor/bin/tester .
if (@!include __DIR__ . '/../vendor/autoload.php') {
	echo 'Install Nette Tester using `composer install`';
	exit(1);
}

Tester\Environment::setup();

define('PHPGSQL_CONNECTION_CONFIG', getenv('PHPGSQL_CONNECTION_CONFIG') ?: 'host=localhost port=5432 user=postgres password=postgres');
