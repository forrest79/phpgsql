<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db;

class BasicRowFactory implements RowFactory
{

	public function createRow(array $values, array $columnsDataTypes, DataTypeParsers\DataTypeParser $dataTypeParser): Row
	{
		return new Row($values, $columnsDataTypes, $dataTypeParser);
	}

}
