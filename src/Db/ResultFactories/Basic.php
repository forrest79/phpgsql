<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db\ResultFactories;

use Forrest79\PhPgSql\Db;
use PgSql;

class Basic implements Db\ResultFactory
{

	/**
	 * @param array<int, string>|null $dataTypesCache
	 */
	public function create(
		PgSql\Result $queryResource,
		Db\Query $query,
		Db\RowFactory $rowFactory,
		Db\DataTypeParser $dataTypeParser,
		array|null $dataTypesCache,
	): Db\Result
	{
		return new Db\Result($queryResource, $query, $rowFactory, $dataTypeParser, $dataTypesCache);
	}

}
