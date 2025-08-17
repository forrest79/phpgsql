<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db;

class AsyncQuery
{
	private Connection $connection;

	private ResultBuilder $resultBuilder;

	private AsyncHelper $asyncHelper;

	private Query $query;

	private string|null $preparedStatementName;


	public function __construct(
		Connection $connection,
		ResultBuilder $resultBuilder,
		AsyncHelper $asyncHelper,
		Query $query,
		string|null $preparedStatementName = null,
	)
	{
		$this->connection = $connection;
		$this->resultBuilder = $resultBuilder;
		$this->asyncHelper = $asyncHelper;
		$this->query = $query;
		$this->preparedStatementName = $preparedStatementName;
	}


	public function getQuery(): Query
	{
		return $this->query;
	}


	/**
	 * @throws Exceptions\QueryException
	 */
	public function getNextResult(): Result
	{
		$actualAsyncQuery = $this->asyncHelper->getAsyncQuery();
		$actualAsyncExecuteQuery = $this->asyncHelper->getAsyncExecuteQuery();
		if (($actualAsyncQuery === null) && ($actualAsyncExecuteQuery === null)) {
			throw Exceptions\ConnectionException::asyncNoQueryIsSent();
		} else if (($actualAsyncQuery !== $this) || ($actualAsyncExecuteQuery !== null)) {
			throw Exceptions\ConnectionException::anotherAsyncQueryIsRunning(
				$this->getQuery()->sql,
				$actualAsyncExecuteQuery ?? $actualAsyncQuery->getQuery()->sql,
			);
		}

		$resource = \pg_get_result($this->connection->getResource());
		if ($resource === false) {
			$this->asyncHelper->clearQuery();
			throw Exceptions\ResultException::noOtherAsyncResult($this->getQuery());
		}

		if (!$this->asyncHelper::checkAsyncQueryResult($resource)) {
			if ($this->preparedStatementName === null) {
				throw Exceptions\QueryException::asyncQueryFailed($this->getQuery(), (string) \pg_result_error($resource));
			} else {
				throw Exceptions\QueryException::asyncPreparedStatementQueryFailed(
					$this->preparedStatementName,
					$this->getQuery(),
					(string) \pg_result_error($resource),
				);
			}
		}

		return $this->resultBuilder->build($resource, $this->getQuery());
	}

}
