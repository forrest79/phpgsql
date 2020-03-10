<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db\RowFactories;

use Forrest79\PhPgSql\Db;

class Basic implements Db\RowFactory
{

	/**
	 * @param array<string, mixed> $values
	 * @param array<string, string> $columnsDataTypes
	 */
	public function createRow(array $values, array $columnsDataTypes, Db\DataTypeParser $dataTypeParser): Db\RowResult
	{
		return new Db\RowResult($values, $columnsDataTypes, $dataTypeParser);
	}

}
