<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db;

interface DataTypeCache
{

	/**
	 * @return array with structure [int column-oid => string column-typname] from table pg_catalog.pg_type
	 */
	public function load(): array;

}
