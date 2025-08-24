<?php declare(strict_types=1);

require_once __DIR__ . '/prepare-db-config.php';

$connection = \pg_connect(\phpgsqlConnectionConfig());
if ($connection !== false) {
	$resource = \pg_query($connection, 'SELECT \'DROP DATABASE \' || datname || \';\' FROM pg_database WHERE datistemplate = FALSE AND datname LIKE \'phpgsql_%_%\';');
	if ($resource !== false) {
		while (($row = \pg_fetch_row($resource)) !== false) {
			\assert(\is_string($row[0]));
			\pg_query($connection, $row[0]);
		}
	}
}
