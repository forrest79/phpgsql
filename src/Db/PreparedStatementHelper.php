<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db;

abstract class PreparedStatementHelper
{
	private static int $id = 1;

	protected Connection $connection;

	protected ResultBuilder $resultBuilder;

	protected Events $events;

	protected string $query;

	protected string|NULL $statementName = NULL;


	public function __construct(Connection $connection, ResultBuilder $resultBuilder, Events $events, string $query)
	{
		$this->connection = $connection;
		$this->resultBuilder = $resultBuilder;
		$this->events = $events;
		$this->query = $query;
	}


	protected static function getNextStatementName(): string
	{
		return 'phpgsql' . self::$id++;
	}


	protected static function prepareQuery(string $query): string
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
			$query,
		);
	}


	/**
	 * @param list<mixed> $params
	 * @return list<mixed>
	 */
	protected static function prepareParams(array $params): array
	{
		return \array_map(static function ($value) {
			if (\is_bool($value)) {
				return $value ? 'TRUE' : 'FALSE';
			}

			return $value;
		}, $params);
	}

}
