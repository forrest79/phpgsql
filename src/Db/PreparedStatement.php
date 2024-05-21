<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db;

class PreparedStatement extends PreparedStatementHelper
{

	public function execute(mixed ...$params): Result
	{
		\assert(\array_is_list($params));
		return $this->executeArgs($params);
	}


	/**
	 * @param list<mixed> $params
	 */
	public function executeArgs(array $params): Result
	{
		$statementName = $this->prepareStatement();

		$startTime = $this->internals->hasOnQuery() ? \hrtime(TRUE) : NULL;

		$params = self::prepareParams($params);

		$query = new Query($this->query, $params);

		$resource = @\pg_execute($this->internals->getResource(), $statementName, $params); // intentionally @
		if ($resource === FALSE) {
			throw Exceptions\QueryException::preparedStatementQueryFailed($statementName, $query, $this->internals->getLastError());
		}

		if ($startTime !== NULL) {
			$this->internals->onQuery($query, \hrtime(TRUE) - $startTime, $statementName);
		}

		return $this->internals->createResult($resource, $query);
	}


	private function prepareStatement(): string
	{
		if ($this->statementName === NULL) {
			$statementName = self::getNextStatementName();

			$this->query = self::prepareQuery($this->query);

			$resource = @\pg_prepare($this->internals->getResource(), $statementName, $this->query); // intentionally @
			if ($resource === FALSE) {
				throw Exceptions\QueryException::preparedStatementQueryFailed(
					$statementName,
					new Query($this->query, []),
					$this->internals->getLastError(),
				);
			}

			$this->statementName = $statementName;
		}

		return $this->statementName;
	}

}
