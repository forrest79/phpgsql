<?php declare(strict_types=1);

$loader = __DIR__ . '/../vendor/autoload.php';

if (!\file_exists($loader)) {
	echo 'Install dependencies using `composer update --dev`';
	exit(1);
}

require $loader;

if (!\function_exists('run')) {
	function run(string $class): void
	{
		if (\defined('__PHPSTAN_RUNNING__')) {
			return;
		}

		$benchmark = new $class();
		if ($benchmark instanceof Forrest79\PhPgSql\Benchmarks\BenchmarkCase) {
			$benchmark->run();
		}
	}
}

require __DIR__ . '/../tests/prepare-db-config.php';
