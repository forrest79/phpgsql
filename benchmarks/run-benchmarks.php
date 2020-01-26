#!/usr/bin/env php
<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Benchmarks;

foreach ((array) \glob(__DIR__ . '/*Benchmark.php') as $benchmarkFile) {
	require $benchmarkFile;
}
