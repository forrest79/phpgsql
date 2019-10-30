<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Benchmarks;

require __DIR__ . '/boostrap.php';

class PdoBenchmark extends BenchmarkCase
{
	/** @var resource */
	private $resource;

	/** @var \PDO */
	private $pdo;

	/** @var \PDO */
	private $pdoEmulate;

	/** @var \PDOStatement */
	private $pdoPrepareStatement;

	/** @var \PDOStatement */
	private $pdoEmulatePrepareStatement;


	public function __construct()
	{
		$resource = \pg_connect(\PHPGSQL_CONNECTION_CONFIG);
		if ($resource === FALSE) {
			throw new \RuntimeException('pg_query failed');
		}
		$this->resource = $resource;

		$pdoConfig = 'pgsql:' . \str_replace(' ', ';', \PHPGSQL_CONNECTION_CONFIG);

		$this->pdo = new \PDO($pdoConfig);

		$this->pdoEmulate = new \PDO(
			$pdoConfig,
			NULL,
			NULL,
			[\PDO::ATTR_EMULATE_PREPARES => TRUE]
		);

		$pgPrepareResource = \pg_prepare($resource, 'test', 'SELECT ' . \rand(0, 1000) . ' WHERE 1 = $1');
		if ($pgPrepareResource === FALSE) {
			throw new \RuntimeException('pg_prepare failed');
		}

		$this->pdoPrepareStatement = $this->pdo->prepare('SELECT ' . \rand(0, 1000) . ' WHERE 1 = ?');

		$this->pdoEmulatePrepareStatement = $this->pdoEmulate->prepare('SELECT ' . \rand(0, 1000) . ' WHERE 1 = ?');
	}


	protected function title(): string
	{
		return 'Comparison with PDO';
	}


	/**
	 * @title simple query - "pg_query"
	 */
	public function benchmarkSimplePgQuery(): void
	{
		$queryResource = \pg_query($this->resource, 'SELECT ' . \rand(0, 1000));
		if ($queryResource === FALSE) {
			throw new \RuntimeException('pg_query failed');
		}
	}


	/**
	 * @title simple query - "PDO::query"
	 */
	public function benchmarkSimplePdoQuery(): void
	{
		$queryResource = $this->pdo->query('SELECT ' . \rand(0, 1000));
		if ($queryResource === FALSE) {
			throw new \RuntimeException('PDO::query failed');
		}
	}


	/**
	 * @title simple query - "PDO::exec"
	 */
	public function benchmarkSimplePdoExec(): void
	{
		$this->pdo->exec('SELECT ' . \rand(0, 1000));
	}


	/**
	 * @title params query - "pg_query_params"
	 */
	public function benchmarkParamsPgQueryParams(): void
	{
		$queryResource = \pg_query_params($this->resource, 'SELECT ' . \rand(0, 1000) . ' WHERE 1 = $1', [1]);
		if ($queryResource === FALSE) {
			throw new \RuntimeException('pg_query_params failed');
		}
		$data = \pg_fetch_all($queryResource);
		if ($data === FALSE) {
			throw new \RuntimeException('pg_fetch_all failed');
		}
	}


	/**
	 * @title params query - "PDO::prepare->execute"
	 */
	public function benchmarkParamsPdoPrepareExecute(): void
	{
		$queryResource = $this->pdo->prepare('SELECT ' . \rand(0, 1000) . ' WHERE 1 = ?');
		$result = $queryResource->execute([1]);
		$queryResource->fetchAll();
		if ($result === FALSE) {
			throw new \RuntimeException('PDO::execute failed');
		}
	}


	/**
	 * @title params query - "PDO::prepare->execute" (emulate)
	 */
	public function benchmarkParamsPdoPrepareExecuteEmulate(): void
	{
		$queryResource = $this->pdoEmulate->prepare('SELECT ' . \rand(0, 1000) . ' WHERE 1 = ?');
		$result = $queryResource->execute([1]);
		$queryResource->fetchAll();
		if ($result === FALSE) {
			throw new \RuntimeException('PDO::execute failed');
		}
	}


	/**
	 * @title repeat statement - "pg_prepare->pg_execute"
	 */
	public function benchmarkRepeatStatementPgPrepare(): void
	{
		$queryResource = \pg_execute($this->resource, 'test', [1]);
		if ($queryResource === FALSE) {
			throw new \RuntimeException('pg_execute failed');
		}
		$data = \pg_fetch_all($queryResource);
		if ($data === FALSE) {
			throw new \RuntimeException('pg_fetch_all failed');
		}
	}


	/**
	 * @title repeat statement - "PDO::execute"
	 */
	public function benchmarkRepeatStatementPdoExecute(): void
	{
		$result = $this->pdoPrepareStatement->execute([1]);
		$this->pdoPrepareStatement->fetchAll();
		if ($result === FALSE) {
			throw new \RuntimeException('PDO::execute failed');
		}
	}


	/**
	 * @title repeat statement - "PDO::execute" (emulate)
	 */
	public function benchmarkRepeatStatementPdoExecuteEmulate(): void
	{
		$result = $this->pdoEmulatePrepareStatement->execute([1]);
		$this->pdoEmulatePrepareStatement->fetchAll();
		if ($result === FALSE) {
			throw new \RuntimeException('PDO::execute failed');
		}
	}


	protected function tearDown(): void
	{
		parent::tearDown();
		\pg_close($this->resource);
	}

}

\run(PdoBenchmark::class);
