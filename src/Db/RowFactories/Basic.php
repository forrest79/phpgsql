<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db\RowFactories;

use Forrest79\PhPgSql\Db;

class Basic implements Db\RowFactory
{

	public function createRow(array $values, array $columnsDataTypes, Db\DataTypeParser $dataTypeParser): Db\Row
	{
		return new Db\Row($values, $columnsDataTypes, $dataTypeParser);
	}

}
