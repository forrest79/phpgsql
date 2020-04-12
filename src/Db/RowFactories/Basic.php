<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db\RowFactories;

use Forrest79\PhPgSql\Db;

class Basic implements Db\RowFactory
{

	/**
	 * @param array<string, mixed> $values
	 */
	public function createRow(Db\Result $result, array $values): Db\Row
	{
		return new Db\Row($result, $values);
	}

}
