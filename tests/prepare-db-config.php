<?php declare(strict_types=1);

if (!\defined('PHPGSQL_CONNECTION_CONFIG')) {
	$envConfig = \getenv('PHPGSQL_CONNECTION_CONFIG');
	\define(
		'PHPGSQL_CONNECTION_CONFIG',
		$envConfig === FALSE
			? 'host=localhost port=5432 user=postgres password=postgres'
			: $envConfig
	);
}
