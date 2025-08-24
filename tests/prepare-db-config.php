<?php declare(strict_types=1);


function phpgsqlConnectionConfig(): string
{
	$envConfig = \getenv('PHPGSQL_CONNECTION_CONFIG');

	return $envConfig === false
		? 'host=localhost port=5432 user=postgres password=postgres'
		: $envConfig;
}
