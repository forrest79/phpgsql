<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db;

interface ColumnValueParser
{

	function parseColumnValue(string $column, mixed $rawValue): mixed;

}
