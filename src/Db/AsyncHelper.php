<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db;

class AsyncHelper
{
	/** @var Connection */
	private $connection;

	/** @var AsyncQuery|NULL */
	private $asyncQuery;

	/** @var string|NULL */
	private $asyncExecuteQuery;


	public function __construct(Connection $connection)
	{
		$this->connection = $connection;
	}


	public function createAndSetAsyncQuery(Query $query, ?string $preparedStatementName = NULL): AsyncQuery
	{
		$this->asyncQuery = new AsyncQuery($this->connection, $this, $query, $preparedStatementName);
		$this->asyncExecuteQuery = NULL;
		return $this->asyncQuery;
	}


	public function getAsyncQuery(): ?AsyncQuery
	{
		return $this->asyncQuery;
	}


	public function setAsyncExecuteQuery(string $asyncExecuteQuery): void
	{
		$this->asyncQuery = NULL;
		$this->asyncExecuteQuery = $asyncExecuteQuery;
	}


	public function getAsyncExecuteQuery(): ?string
	{
		return $this->asyncExecuteQuery;
	}


	public function clearQuery(): void
	{
		$this->asyncQuery = NULL;
		$this->asyncExecuteQuery = NULL;
	}


	/**
	 * @param resource $result
	 */
	public static function checkAsyncQueryResult($result): bool
	{
		return !\in_array(\pg_result_status($result), [\PGSQL_BAD_RESPONSE, \PGSQL_NONFATAL_ERROR, \PGSQL_FATAL_ERROR], TRUE);
	}

}
