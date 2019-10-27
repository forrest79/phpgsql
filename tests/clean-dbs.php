<?php declare(strict_types=1);

require __DIR__ . '/prepare-db-config.php';

$connection = \pg_connect(\PHPGSQL_CONNECTION_CONFIG);
if ($connection !== FALSE) {
	$resource = \pg_query($connection, 'SELECT \'DROP DATABASE \' || datname || \';\' FROM pg_database WHERE datistemplate = FALSE AND datname LIKE \'phpgsql_%_%\';');
	if ($resource !== FALSE) {
		while ($row = \pg_fetch_row($resource)) {
			\pg_query($row[0]);
		}
	}
}
