<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db\ResultFactories;

use Forrest79\PhPgSql\Db;
use PgSql;

class Basic implements Db\ResultFactory
{
	private Db\Connection $connection;


	public function __construct(Db\Connection $connection)
	{
		$this->connection = $connection;
	}


	public function createResult(PgSql\Result $resource, Db\Query $query): Db\Result
	{
		return new Db\Result(
			$resource,
			$query,
			$this->connection->getDefaultRowFactory(),
			$this->connection->getDataTypeParser(),
			$this->connection->getDataTypesCache(),
		);
	}

}
