<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db;

interface RowFactory
{

	/**
	 * @param array<string, mixed> $values
	 */
	function createRow(Result $result, array $values): Row;

}
