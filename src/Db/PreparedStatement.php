<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db;

class PreparedStatement
{
	/** @var int */
	private static $id = 1;

	/** @var Connection */
	private $connection;

	/** @var Events */
	private $events;

	/** @var string */
	private $query;

	/** @var string|NULL */
	private $statementName = NULL;


	public function __construct(Connection $connection, Events $events, string $query)
	{
		$this->connection = $connection;
		$this->events = $events;
		$this->query = $query;
	}


	/**
	 * @param mixed ...$params
	 */
	public function execute(...$params): Result
	{
		return $this->executeArgs($params);
	}


	/**
	 * @param array<mixed> $params
	 */
	public function executeArgs(array $params): Result
	{
		$statementName = $this->prepareStatement();

		$startTime = $this->events->hasOnQuery() ? \microtime(TRUE) : NULL;

		$params = self::prepareParams($params);

		$query = new Query($this->query, $params);

		$resource = @\pg_execute($this->connection->getResource(), $statementName, $params); // intentionally @
		if ($resource === FALSE) {
			throw Exceptions\QueryException::preparedStatementQueryFailed($statementName, $query, $this->connection->getLastError());
		}

		if ($startTime !== NULL) {
			$this->events->onQuery($query, \microtime(TRUE) - $startTime, $statementName);
		}

		return $this->connection->createResult($resource, $query);
	}


	private function prepareStatement(): string
	{
		if ($this->statementName === NULL) {
			$statementName = self::getNextStatementName();
			$this->query = self::prepareQuery($this->query);
			$resource = @\pg_prepare($this->connection->getResource(), $statementName, $this->query); // intentionally @
			if ($resource === FALSE) {
				throw Exceptions\QueryException::preparedStatementQueryFailed(
					$statementName,
					new Query($this->query, []),
					$this->connection->getLastError()
				);
			}
			$this->statementName = $statementName;
		}

		return $this->statementName;
	}


	public static function getNextStatementName(): string
	{
		return 'phpgsql' . self::$id++;
	}


	public static function prepareQuery(string $query): string
	{
		$paramIndex = 0;

		return (string) \preg_replace_callback(
			'/([\\\\]?)\?/',
			static function ($matches) use (&$paramIndex): string {
				if ($matches[1] === '\\') {
					return '?';
				}

				return '$' . ++$paramIndex;
			},
			$query
		);
	}


	/**
	 * @param array<mixed> $params
	 * @return array<mixed>
	 */
	public static function prepareParams(array $params): array
	{
		return \array_map(static function ($value) {
			if (\is_bool($value)) {
				return $value ? 'TRUE' : 'FALSE';
			}
			return $value;
		}, $params);
	}

}
