<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db;

interface RowFactory
{

	public function createRow(array $values, array $columnsDataTypes, DataTypeParser $dataTypeParser): Row;

}
