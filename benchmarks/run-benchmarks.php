#!/usr/bin/env php
<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Benchmarks;

$benchmarkFiles = \glob(__DIR__ . '/*Benchmark.php');
assert(is_array($benchmarkFiles));

foreach ($benchmarkFiles as $benchmarkFile) {
	require $benchmarkFile;
}
