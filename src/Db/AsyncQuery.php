<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db;

class AsyncQuery
{
	/** @var Connection */
	private $connection;

	/** @var Query */
	private $query;


	public function __construct(Connection $connection, Query $query)
	{
		$this->connection = $connection;
		$this->query = $query;
	}


	public function getQuery(): Query
	{
		return $this->query;
	}


	public function getNextResult(): Result
	{
		if ($this->connection->getAsyncQuery() !== $this) {
			$connectionQuery = '';
			$asyncQuery = $this->connection->getAsyncQuery();
			if (\is_string($asyncQuery)) {
				$connectionQuery = $asyncQuery;
			} else if ($asyncQuery instanceof AsyncQuery) {
				$connectionQuery = $asyncQuery->getQuery()->getSql();
			}
			throw Exceptions\ResultException::anotherAsyncQueryIsRunning($this->query, $connectionQuery);
		}

		return $this->connection->getNextAsyncQueryResult();
	}

}
