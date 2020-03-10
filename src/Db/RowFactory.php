<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db;

interface RowFactory
{

	/**
	 * @param array<string, mixed> $values
	 * @param array<string, string> $columnsDataTypes
	 */
	function createRow(array $values, array $columnsDataTypes, DataTypeParser $dataTypeParser): Rowable;

}
