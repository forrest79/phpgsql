<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Benchmarks;

require __DIR__ . '/boostrap.php';

final class PgQueryBenchmark extends BenchmarkCase
{
	/** @var resource */
	private $resource;


	protected function setUp(): void
	{
		parent::setUp();

		$resource = \pg_connect(\PHPGSQL_CONNECTION_CONFIG);
		if ($resource === FALSE) {
			throw new \RuntimeException('pg_connect failed');
		}
		$this->resource = $resource;
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
		$queryResource = \pg_query($this->resource, 'SELECT ' . \rand(0, 1000));
		if ($queryResource === FALSE) {
			throw new \RuntimeException('pg_query failed');
		}
	}


	/**
	 * @title run with "pg_query_params"
	 */
	public function benchmarkPgQueryParams(): void
	{
		$queryResource = \pg_query_params($this->resource, 'SELECT ' . \rand(0, 1000), []);
		if ($queryResource === FALSE) {
			throw new \RuntimeException('pg_query_params failed');
		}
	}


	/**
	 * @title run with "pg_query" with static WHERE
	 */
	public function benchmarkPgQueryWithParameters(): void
	{
		$queryResource = \pg_query($this->resource, 'SELECT 1 WHERE 1 = 1 AND 2 = 2 AND 3 = 3 AND 4 = 4 AND 5 = 5');
		if ($queryResource === FALSE) {
			throw new \RuntimeException('pg_query failed');
		}
	}


	/**
	 * @title run with "pg_query_params" with parameters WHERE
	 */
	public function benchmarkPgQueryParamsWithParameters(): void
	{
		$queryResource = \pg_query_params($this->resource, 'SELECT 1 WHERE 1 = $1 AND 2 = $2 AND 3 = $3 AND 4 = $4 AND 5 = $5', [1, 2, 3, 4, 5]);
		if ($queryResource === FALSE) {
			throw new \RuntimeException('pg_query_params failed');
		}
	}


	protected function tearDown(): void
	{
		parent::tearDown();
		\pg_close($this->resource);
	}

}

(new PgQueryBenchmark())->run();
