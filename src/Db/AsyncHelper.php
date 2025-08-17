<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db;

use PgSql;

class AsyncHelper
{
	private Connection $connection;

	private AsyncQuery|null $asyncQuery = null;

	private string|null $asyncExecuteQuery = null;


	public function __construct(Connection $connection)
	{
		$this->connection = $connection;
	}


	public function createAndSetAsyncQuery(
		ResultBuilder $resultBuilder,
		Query $query,
		string|null $preparedStatementName = null,
	): AsyncQuery
	{
		$this->asyncQuery = new AsyncQuery($this->connection, $resultBuilder, $this, $query, $preparedStatementName);
		$this->asyncExecuteQuery = null;

		return $this->asyncQuery;
	}


	public function getAsyncQuery(): AsyncQuery|null
	{
		return $this->asyncQuery;
	}


	public function setAsyncExecuteQuery(string $asyncExecuteQuery): void
	{
		$this->asyncQuery = null;
		$this->asyncExecuteQuery = $asyncExecuteQuery;
	}


	public function getAsyncExecuteQuery(): string|null
	{
		return $this->asyncExecuteQuery;
	}


	public function clearQuery(): void
	{
		$this->asyncQuery = null;
		$this->asyncExecuteQuery = null;
	}


	public static function checkAsyncQueryResult(PgSql\Result $result): bool
	{
		return !\in_array(\pg_result_status($result), [\PGSQL_BAD_RESPONSE, \PGSQL_NONFATAL_ERROR, \PGSQL_FATAL_ERROR], true);
	}

}
