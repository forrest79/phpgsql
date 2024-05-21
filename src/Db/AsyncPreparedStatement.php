<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db;

class AsyncPreparedStatement extends PreparedStatementHelper
{
	private AsyncHelper $asyncHelper;


	public function __construct(AsyncHelper $asyncHelper, Internals $internals, string $query)
	{
		parent::__construct($internals, $query);
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

		$success = @\pg_send_execute($this->internals->getResource(), $statementName, $params); // intentionally @
		if ($success === FALSE) {
			throw Exceptions\QueryException::asyncPreparedStatementQueryFailed(
				$statementName,
				$query,
				$this->internals->getLastError(),
			);
		}

		if ($this->internals->hasOnQuery()) {
			$this->internals->onQuery($query, NULL, $statementName);
		}

		return $this->asyncHelper->createAndSetAsyncQuery($query, $statementName);
	}


	private function prepareStatement(): string
	{
		if ($this->statementName === NULL) {
			$statementName = self::getNextStatementName();

			$this->query = self::prepareQuery($this->query);

			$success = @\pg_send_prepare($this->internals->getResource(), $statementName, $this->query); // intentionally @
			if ($success === FALSE) {
				throw Exceptions\QueryException::asyncPreparedStatementQueryFailed(
					$statementName,
					new Query($this->query, []),
					$this->internals->getLastError(),
				);
			}

			$resource = \pg_get_result($this->internals->getResource());
			if (($resource === FALSE) || (!$this->asyncHelper::checkAsyncQueryResult($resource))) {
				throw Exceptions\QueryException::asyncPreparedStatementQueryFailed(
					$statementName,
					new Query($this->query, []),
					($resource !== FALSE) ? (string) \pg_result_error($resource) : $this->internals->getLastError(),
				);
			}

			$this->statementName = $statementName;
		}

		return $this->statementName;
	}

}
