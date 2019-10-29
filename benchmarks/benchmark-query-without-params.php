<?php declare(strict_types=1);

use function Forrest79\PhPgSql\Benchmarks\benchmark;

$resource = \pg_connect(\PHPGSQL_CONNECTION_CONFIG);

benchmark(static function () use ($resource): void {
	$queryResource = \pg_query($resource, 'SELECT ' . \rand(0, 1000));
	if (!$queryResource) {
		throw new \RuntimeException('pg_query failed');
	}
}, 'run with "pg_query"');

benchmark(static function () use ($resource): void {
	$queryResource = \pg_query_params($resource, 'SELECT ' . \rand(0, 1000), []);
	if (!$queryResource) {
		throw new \RuntimeException('pg_query failed');
	}
}, 'run with "pg_query_params"');

\pg_close($resource);
