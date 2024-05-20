<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db;

class AsyncQuery
{
	private AsyncHelper $asyncHelper;

	private Internals $internals;

	private Query $query;

	private string|NULL $preparedStatementName;


	public function __construct(
		AsyncHelper $asyncHelper,
		Internals $internal,
		Query $query,
		string|NULL $preparedStatementName = NULL,
	)
	{
		$this->asyncHelper = $asyncHelper;
		$this->internals = $internal;
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
			throw Exceptions\ConnectionException::asyncNoQueryIsSent();
		} else if (($actualAsyncQuery !== $this) || ($actualAsyncExecuteQuery !== NULL)) {
			throw Exceptions\ConnectionException::anotherAsyncQueryIsRunning(
				$this->getQuery()->getSql(),
				$actualAsyncExecuteQuery ?? $actualAsyncQuery->getQuery()->getSql(),
			);
		}

		$resource = \pg_get_result($this->internals->getConnectedResource());
		if ($resource === FALSE) {
			$this->asyncHelper->clearQuery();
			throw Exceptions\ResultException::noOtherAsyncResult($this->getQuery());
		}

		if (!$this->asyncHelper::checkAsyncQueryResult($resource)) {
			if ($this->preparedStatementName === NULL) {
				throw Exceptions\QueryException::asyncQueryFailed($this->getQuery(), (string) \pg_result_error($resource));
			} else {
				throw Exceptions\QueryException::asyncPreparedStatementQueryFailed(
					$this->preparedStatementName,
					$this->getQuery(),
					(string) \pg_result_error($resource),
				);
			}
		}

		return $this->internals->createResult($resource, $this->getQuery());
	}

}
