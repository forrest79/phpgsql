#!/usr/bin/env php
<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Benchmarks;

echo \PHP_EOL . 'BENCHMARKS:' . \PHP_EOL . \PHP_EOL;

foreach ((array) \glob(__DIR__ . '/*Benchmark.php') as $benchmarkFile) {
	require $benchmarkFile;
}
