<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db;

class AsyncQuery
{
	/** @var Connection */
	private $connection;

	/** @var AsyncHelper */
	private $asyncHelper;

	/** @var Query */
	private $query;

	/** @var string|NULL */
	private $preparedStatementName;


	public function __construct(
		Connection $connection,
		AsyncHelper $asyncHelper,
		Query $query,
		?string $preparedStatementName = NULL
	)
	{
		$this->connection = $connection;
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
		if (($actualAsyncQuery === NULL) && ($actualAsyncExecuteQuery === NULL)) {
			throw Exceptions\ConnectionException::asyncNoQueryIsSentException();
		} else if (($actualAsyncQuery !== $this) || ($actualAsyncExecuteQuery !== NULL)) {
			throw Exceptions\ConnectionException::anotherAsyncQueryIsRunning(
				$this->getQuery()->getSql(),
				$actualAsyncExecuteQuery ?? $actualAsyncQuery->getQuery()->getSql()
			);
		}

		$result = \pg_get_result($this->connection->getResource());
		if ($result === FALSE) {
			$this->asyncHelper->clearQuery();
			throw Exceptions\ResultException::noOtherAsyncResult($this->getQuery());
		}

		if (!$this->asyncHelper::checkAsyncQueryResult($result)) {
			if ($this->preparedStatementName === NULL) {
				throw Exceptions\QueryException::asyncQueryFailed($this->getQuery(), (string) \pg_result_error($result));
			} else {
				throw Exceptions\QueryException::asyncPreparedStatementQueryFailed(
					$this->preparedStatementName,
					$this->getQuery(),
					(string) \pg_result_error($result)
				);
			}
		}

		return $this->connection->createResult($result, $this->getQuery());
	}

}
