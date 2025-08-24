<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Benchmarks;

use PgSql;

require __DIR__ . '/bootstrap.php';

final class PgQueryBenchmark extends BenchmarkCase
{
	private PgSql\Connection $connection;


	protected function setUp(): void
	{
		parent::setUp();

		$connection = \pg_connect(\phpgsqlConnectionConfig());
		if ($connection === false) {
			throw new \RuntimeException('pg_connect failed');
		}

		$this->connection = $connection;
	}


	protected function title(): string
	{
		return 'Query without parameters';
	}


	/**
	 * @title run with "pg_query"
	 */
	public function benchmarkPgQuery(): void
	{
		$queryResource = \pg_query($this->connection, 'SELECT ' . \rand(0, 1000));
		if ($queryResource === false) {
			throw new \RuntimeException('pg_query failed');
		}
	}


	/**
	 * @title run with "pg_query_params"
	 */
	public function benchmarkPgQueryParams(): void
	{
		$queryResource = \pg_query_params($this->connection, 'SELECT ' . \rand(0, 1000), []);
		if ($queryResource === false) {
			throw new \RuntimeException('pg_query_params failed');
		}
	}


	/**
	 * @title run with "pg_query" with static WHERE
	 */
	public function benchmarkPgQueryWithParameters(): void
	{
		$queryResource = \pg_query($this->connection, 'SELECT 1 WHERE 1 = 1 AND 2 = 2 AND 3 = 3 AND 4 = 4 AND 5 = 5');
		if ($queryResource === false) {
			throw new \RuntimeException('pg_query failed');
		}
	}


	/**
	 * @title run with "pg_query_params" with parameters WHERE
	 */
	public function benchmarkPgQueryParamsWithParameters(): void
	{
		$queryResource = \pg_query_params($this->connection, 'SELECT 1 WHERE 1 = $1 AND 2 = $2 AND 3 = $3 AND 4 = $4 AND 5 = $5', [1, 2, 3, 4, 5]);
		if ($queryResource === false) {
			throw new \RuntimeException('pg_query_params failed');
		}
	}


	protected function tearDown(): void
	{
		parent::tearDown();
		\pg_close($this->connection);
	}

}

(new PgQueryBenchmark())->run();
