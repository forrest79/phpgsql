<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Benchmarks;

require __DIR__ . '/boostrap.php';

class QueryWithoutParametersBenchmark extends BenchmarkCase
{
	/** @var resource */
	private $resource;


	public function __construct()
	{
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


	protected function tearDown(): void
	{
		parent::tearDown();
		\pg_close($this->resource);
	}

}

(new QueryWithoutParametersBenchmark())->run();
