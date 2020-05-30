<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db;

class DummyColumnValueParser implements ColumnValueParser
{

	/**
	 * @param mixed $rawValue
	 * @return mixed
	 */
	public function parseColumnValue(string $column, $rawValue)
	{
		return $rawValue;
	}

}
