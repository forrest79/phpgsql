<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db;

final class PgQuery
{

	/**
	 * @param list<mixed> $params
	 */
	public function __construct(public readonly string $sql, public readonly array $params)
	{
	}

}
