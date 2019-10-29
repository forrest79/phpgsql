#!/usr/bin/env php
<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Benchmarks;

\define('PHPGSQL_CONNECTION_CONFIG', \getenv('PHPGSQL_CONNECTION_CONFIG') ?: 'host=localhost port=5432 user=postgres password=postgres');


function benchmark(callable $benchmark, string $title, int $repeat = 10000): void
{
	$start = \microtime(TRUE);

	for ($i = 0; $i < $repeat; $i++) {
		$benchmark();
	}

	echo \sprintf('| %-50s | %012.10f |', \substr($title, 0, 50), (\microtime(TRUE) - $start) / $repeat) . \PHP_EOL;
}

echo \PHP_EOL . 'BENCHMARKS:' . \PHP_EOL . \PHP_EOL;

foreach (\glob(__DIR__ . '/benchmark-*.php') as $benchmarkFile) {
	$filename = \substr(\basename($benchmarkFile), 10, -4);
	$name = \array_map(static function (string $part): string {
		return \ucfirst($part);
	}, \explode('-', $filename));
	echo \sprintf('| %-50s | Time         |', \implode(' ', $name)) . \PHP_EOL;
	echo \sprintf('|----------------------------------------------------|--------------|') . \PHP_EOL;
	require $benchmarkFile;
	echo \sprintf('|----------------------------------------------------|--------------|') . \PHP_EOL . \PHP_EOL;
}
