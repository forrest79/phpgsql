<?php declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$connection = \pg_connect(PHPGSQL_CONNECTION_CONFIG);
if ($connection) {
	$resource = \pg_query($connection, 'SELECT \'DROP DATABASE \' || datname || \';\' FROM pg_database WHERE datistemplate = FALSE AND datname LIKE \'phpgsql_%_%\';');
	if ($resource) {
		while ($row = \pg_fetch_row($resource)) {
			\pg_query($row[0]);
		}
	}
}

// @hack
Tester\Assert::true(TRUE);
