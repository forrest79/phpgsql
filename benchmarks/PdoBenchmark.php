<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Benchmarks;

use PgSql;

require __DIR__ . '/boostrap.php';

final class PdoBenchmark extends BenchmarkCase
{
	private PgSql\Connection $connection;

	private \PDO $pdo;

	private \PDO $pdoEmulate;

	/** @var \PDOStatement<mixed> */
	private \PDOStatement $pdoPrepareStatement;

	/** @var \PDOStatement<mixed> */
	private \PDOStatement $pdoEmulatePrepareStatement;


	protected function setUp(): void
	{
		parent::setUp();

		$connection = \pg_connect(\PHPGSQL_CONNECTION_CONFIG);
		if ($connection === false) {
			throw new \RuntimeException('pg_connect failed');
		}
		$this->connection = $connection;

		$pdoConfig = 'pgsql:' . \str_replace(' ', ';', \PHPGSQL_CONNECTION_CONFIG);

		$this->pdo = new \PDO($pdoConfig);

		$this->pdoEmulate = new \PDO(
			$pdoConfig,
			null,
			null,
			[\PDO::ATTR_EMULATE_PREPARES => true],
		);

		$prepareResult = \pg_prepare($connection, 'test', 'SELECT ' . \rand(0, 1000) . ' WHERE 1 = $1');
		if ($prepareResult === false) {
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
		$queryResource = \pg_query($this->connection, 'SELECT ' . \rand(0, 1000));
		if ($queryResource === false) {
			throw new \RuntimeException('pg_query failed');
		}
	}


	/**
	 * @title simple query - "PDO::query"
	 */
	public function benchmarkSimplePdoQuery(): void
	{
		$queryResource = $this->pdo->query('SELECT ' . \rand(0, 1000));
		if ($queryResource === false) {
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
		$queryResource = \pg_query_params($this->connection, 'SELECT ' . \rand(0, 1000) . ' WHERE 1 = $1', [1]);
		if ($queryResource === false) {
			throw new \RuntimeException('pg_query_params failed');
		}

		\pg_fetch_all($queryResource);
	}


	/**
	 * @title params query - "PDO::prepare->execute"
	 */
	public function benchmarkParamsPdoPrepareExecute(): void
	{
		$queryResource = $this->pdo->prepare('SELECT ' . \rand(0, 1000) . ' WHERE 1 = ?');
		$result = $queryResource->execute([1]);
		if ($result === false) {
			throw new \RuntimeException('PDO::execute failed');
		}

		$queryResource->fetchAll();
	}


	/**
	 * @title params query - "PDO::prepare->execute" (emulate)
	 */
	public function benchmarkParamsPdoPrepareExecuteEmulate(): void
	{
		$queryResource = $this->pdoEmulate->prepare('SELECT ' . \rand(0, 1000) . ' WHERE 1 = ?');
		$result = $queryResource->execute([1]);
		if ($result === false) {
			throw new \RuntimeException('PDO::execute failed');
		}

		$queryResource->fetchAll();
	}


	/**
	 * @title repeat statement - "pg_prepare->pg_execute"
	 */
	public function benchmarkRepeatStatementPgPrepare(): void
	{
		$queryResource = \pg_execute($this->connection, 'test', [1]);
		if ($queryResource === false) {
			throw new \RuntimeException('pg_execute failed');
		}

		\pg_fetch_all($queryResource);
	}


	/**
	 * @title repeat statement - "PDO::execute"
	 */
	public function benchmarkRepeatStatementPdoExecute(): void
	{
		$result = $this->pdoPrepareStatement->execute([1]);
		$this->pdoPrepareStatement->fetchAll();
		if ($result === false) {
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
		if ($result === false) {
			throw new \RuntimeException('PDO::execute failed');
		}
	}


	protected function tearDown(): void
	{
		parent::tearDown();

		\pg_close($this->connection);
	}

}

(new PdoBenchmark())->run();
