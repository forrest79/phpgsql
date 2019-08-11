<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db;

interface RowFactory
{

	function createRow(array $values, array $columnsDataTypes, DataTypeParser $dataTypeParser): Row;

}
