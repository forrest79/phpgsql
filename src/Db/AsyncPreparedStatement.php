<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db;

class AsyncPreparedStatement extends PreparedStatementHelper
{
	private AsyncHelper $asyncHelper;


	public function __construct(
		AsyncHelper $asyncHelper,
		Connection $connection,
		ResultBuilder $resultBuilder,
		Events $events,
		string $query,
	)
	{
		parent::__construct($connection, $resultBuilder, $events, $query);
		$this->asyncHelper = $asyncHelper;
	}


	public function execute(mixed ...$params): AsyncQuery
	{
		\assert(\array_is_list($params));
		return $this->executeArgs($params);
	}


	/**
	 * @param list<mixed> $params
	 */
	public function executeArgs(array $params): AsyncQuery
	{
		$statementName = $this->prepareStatement();

		$params = self::prepareParams($params);

		$query = new Query($this->query, $params);

		$success = @\pg_send_execute($this->connection->getResource(), $statementName, $params); // intentionally @
		if ($success === false) {
			throw Exceptions\QueryException::asyncPreparedStatementQueryFailed(
				$statementName,
				$query,
				$this->connection->getLastError(),
			);
		}

		if ($this->events->hasOnQuery()) {
			$this->events->onQuery($query, null, $statementName);
		}

		return $this->asyncHelper->createAndSetAsyncQuery($this->resultBuilder, $query, $statementName);
	}


	private function prepareStatement(): string
	{
		if ($this->statementName === null) {
			$statementName = self::getNextStatementName();

			$this->query = self::prepareQuery($this->query);

			$success = @\pg_send_prepare($this->connection->getResource(), $statementName, $this->query); // intentionally @
			if ($success === false) {
				throw Exceptions\QueryException::asyncPreparedStatementQueryFailed(
					$statementName,
					new Query($this->query, []),
					$this->connection->getLastError(),
				);
			}

			$resource = \pg_get_result($this->connection->getResource());
			if (($resource === false) || (!$this->asyncHelper::checkAsyncQueryResult($resource))) {
				throw Exceptions\QueryException::asyncPreparedStatementQueryFailed(
					$statementName,
					new Query($this->query, []),
					($resource !== false) ? (string) \pg_result_error($resource) : $this->connection->getLastError(),
				);
			}

			$this->statementName = $statementName;
		}

		return $this->statementName;
	}

}
