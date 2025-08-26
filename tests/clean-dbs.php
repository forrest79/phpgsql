<?php declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

$connection = \pg_connect(Forrest79\PhPgSql\Tests\Helper::connectionConfig());
if ($connection !== false) {
	$resource = \pg_query($connection, 'SELECT \'DROP DATABASE \' || datname || \';\' FROM pg_database WHERE datistemplate = FALSE AND datname LIKE \'phpgsql_%_%\';');
	if ($resource !== false) {
		while (($row = \pg_fetch_row($resource)) !== false) {
			\assert(\is_string($row[0]));
			\pg_query($connection, $row[0]);
		}
	}
}
