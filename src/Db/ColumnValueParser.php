<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db;

interface ColumnValueParser
{

	/**
	 * @param mixed $rawValue
	 * @return mixed
	 */
	function parseColumnValue(string $column, $rawValue);

}
