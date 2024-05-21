<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db;

use PgSql;

class Internals
{
	private Connection $connection;

	/** @var list<callable(Connection, Query, int|float|NULL, string|NULL): void> function (Connection $connection, Query $query, int|float|NULL $timeNs, string|NULL $prepareStatementName) {} */
	private array $onQuery = [];

	/** @var list<callable(Connection, Result): void> function (Connection $connection, Result $result) {} */
	private array $onResult = [];


	public function __construct(Connection $connection)
	{
		$this->connection = $connection;
	}


	public function getResource(): PgSql\Connection
	{
		return $this->connection->getResource();
	}


	public function createResult(PgSql\Result $resource, Query $query): Result
	{
		$result = $this->connection->getResultFactory()->createResult($resource, $query);

		$this->onResult($result);

		return $result;
	}


	public function getLastError(): string
	{
		return $this->connection->getLastError();
	}


	public function addOnQuery(callable $callback): void
	{
		$this->onQuery[] = $callback;
	}


	public function hasOnQuery(): bool
	{
		return $this->onQuery !== [];
	}


	public function onQuery(Query $query, float|NULL $timeNs = NULL, string|NULL $prepareStatementName = NULL): void
	{
		foreach ($this->onQuery as $event) {
			$event($this->connection, $query, $timeNs, $prepareStatementName);
		}
	}


	public function addOnResult(callable $callback): void
	{
		$this->onResult[] = $callback;
	}


	private function onResult(Result $result): void
	{
		foreach ($this->onResult as $event) {
			$event($this->connection, $result);
		}
	}

}
