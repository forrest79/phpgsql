<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Tests;

final class Helper
{

	public static function connectionConfig(): string
	{
		$envConfig = \getenv('PHPGSQL_CONNECTION_CONFIG');

		return $envConfig === false
			? 'host=localhost port=5432 user=postgres password=postgres'
			: $envConfig;
	}

}
