<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db\ResultFactories;

use Forrest79\PhPgSql\Db;
use PgSql;

class Basic implements Db\ResultFactory
{
	private Db\Connection $connection;

	private Db\Events $events;


	public function __construct(Db\Connection $connection, Db\Events $events)
	{
		$this->connection = $connection;
		$this->events = $events;
	}


	public function createResult(PgSql\Result $resource, Db\Query $query): Db\Result
	{
		$result = new Db\Result(
			$resource,
			$query,
			$this->connection->getDefaultRowFactory(),
			$this->connection->getDataTypeParser(),
			$this->connection->getDataTypesCache(),
		);

		$this->events->onResult($result);

		return $result;
	}

}
