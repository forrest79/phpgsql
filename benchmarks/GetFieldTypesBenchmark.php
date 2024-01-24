<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Benchmarks;

use PgSql;

require __DIR__ . '/boostrap.php';

final class GetFieldTypesBenchmark extends BenchmarkCase
{
	private const COLUMNS = [
		'col1' => 1,
		'col2' => 2,
		'col3' => 3,
		'col4' => 4,
	];

	/** @var array<int, string> */
	private array $cache = [
		23 => 'int4',
		25 => 'text',
		705 => 'unknown',
		16 => 'bool',
		1184 => 'timestamptz',
	];

	private PgSql\Connection $connection;

	private PgSql\Result $queryResult;


	protected function setUp(): void
	{
		parent::setUp();

		$connection = \pg_connect(\PHPGSQL_CONNECTION_CONFIG);
		if ($connection === FALSE) {
			throw new \RuntimeException('pg_connect failed');
		}

		$this->connection = $connection;

		$queryResult = \pg_query($connection, 'SELECT 1 AS col1, \'a\' AS col2, TRUE AS col3, now() AS col4');
		if ($queryResult === FALSE) {
			throw new \RuntimeException('pg_query failed');
		}

		$this->queryResult = $queryResult;
	}


	protected function title(): string
	{
		return 'Get columns types';
	}


	/**
	 * In production, this could be much slower, because "pg_field_type" loads types from PostgreSQL for every request.
	 *
	 * @title get with "pg_field_type"
	 */
	public function benchmarkPgFieldType(): void
	{
		$type = NULL;
		$types = [];
		$fieldsCnt = \pg_num_fields($this->queryResult);
		for ($i = 0; $i < $fieldsCnt; $i++) {
			if ($this->cache !== NULL) { // just to simulate condition in real code in Result
				$type = \pg_field_type($this->queryResult, $i);
			}

			$types[\pg_field_name($this->queryResult, $i)] = $type;
		}
	}


	/**
	 * @title get with "pg_field_type_oid" (from cache)
	 */
	public function benchmarkPgFieldTypeOid(): void
	{
		$type = NULL;
		$types = [];
		$fieldsCnt = \pg_num_fields($this->queryResult);
		for ($i = 0; $i < $fieldsCnt; $i++) {
			if ($this->cache !== NULL) { // just to simulate condition in real code in Result
				$type = $this->cache[\pg_field_type_oid($this->queryResult, $i)];
			}

			$types[\pg_field_name($this->queryResult, $i)] = $type;
		}
	}


	/**
	 * @title get with "pg_field_type_oid" (from cache) by name
	 */
	public function benchmarkPgFieldTypeOidName(): void
	{
		$type = NULL;
		$types = [];
		$columns = \array_keys(self::COLUMNS);
		foreach ($columns as $column) {
			if ($this->cache !== NULL) { // just to simulate condition in real code in Result
				$type = $this->cache[\pg_field_type_oid($this->queryResult, \pg_field_num($this->queryResult, $column))];
			}

			$types[$column] = $type;
		}
	}


	protected function tearDown(): void
	{
		parent::tearDown();
		\pg_close($this->connection);
	}

}

(new GetFieldTypesBenchmark())->run();
