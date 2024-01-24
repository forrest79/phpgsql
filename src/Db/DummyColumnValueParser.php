<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db;

class DummyColumnValueParser implements ColumnValueParser
{

	public function parseColumnValue(string $column, mixed $rawValue): mixed
	{
		return $rawValue;
	}

}
