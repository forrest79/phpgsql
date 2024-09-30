<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db;

use PgSql;

class AsyncHelper
{
	private Connection $connection;

	private AsyncQuery|NULL $asyncQuery = NULL;

	private string|NULL $asyncExecuteQuery = NULL;


	public function __construct(Connection $connection)
	{
		$this->connection = $connection;
	}


	public function createAndSetAsyncQuery(
		ResultBuilder $resultBuilder,
		Query $query,
		string|NULL $preparedStatementName = NULL,
	): AsyncQuery
	{
		$this->asyncQuery = new AsyncQuery($this->connection, $resultBuilder, $this, $query, $preparedStatementName);
		$this->asyncExecuteQuery = NULL;

		return $this->asyncQuery;
	}


	public function getAsyncQuery(): AsyncQuery|NULL
	{
		return $this->asyncQuery;
	}


	public function setAsyncExecuteQuery(string $asyncExecuteQuery): void
	{
		$this->asyncQuery = NULL;
		$this->asyncExecuteQuery = $asyncExecuteQuery;
	}


	public function getAsyncExecuteQuery(): string|NULL
	{
		return $this->asyncExecuteQuery;
	}


	public function clearQuery(): void
	{
		$this->asyncQuery = NULL;
		$this->asyncExecuteQuery = NULL;
	}


	public static function checkAsyncQueryResult(PgSql\Result $result): bool
	{
		return !\in_array(\pg_result_status($result), [\PGSQL_BAD_RESPONSE, \PGSQL_NONFATAL_ERROR, \PGSQL_FATAL_ERROR], TRUE);
	}

}
