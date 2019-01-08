<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db\DataTypesCache;

use Forrest79\PhPgSql\Db;

abstract class DbDataTypesCache implements DataTypesCache
{
	/** @var Db\Connection */
	private $connection;


	public function __construct(Db\Connection $connection)
	{
		$this->connection = $connection;
	}


	protected function loadFromDb(): array
	{
		$dataTypesCache = $this->connection->getDataTypesCache();
		$this->connection->setDataTypesCache(NULL); // prevent unfinite loop - fetchPairs with DataTypesCache calling also this function
		$types = $this->connection->query('SELECT oid, typname FROM pg_catalog.pg_type')->fetchPairs('oid', 'typname');
		$this->connection->setDataTypesCache($dataTypesCache);
		return $types;
	}

}
