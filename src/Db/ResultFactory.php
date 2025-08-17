<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db;

use PgSql;

interface ResultFactory
{

	/**
	 * @param array<int, string>|null $dataTypesCache
	 */
	function create(
		PgSql\Result $queryResource,
		Query $query,
		RowFactory $rowFactory,
		DataTypeParser $dataTypeParser,
		array|null $dataTypesCache,
	): Result;

}
