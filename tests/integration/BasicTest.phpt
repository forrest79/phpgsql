<?php declare(strict_types=1);

namespace Tests\Integration\Forrest79\PhPgSql\Db;

use Forrest79\PhPgSql\Db;
use Tester;

require_once __DIR__ . '/TestCase.php';

/**
 * @testCase
 */
class BasicTest extends TestCase
{
	/** @var Db\Connection */
	private $connection;


	/**
	 * @throws Db\Exceptions\ConnectionException
	 * @throws Db\Exceptions\QueryException
	 */
	protected function setUp(): void
	{
		parent::setUp();
		$this->connection = new Db\Connection(sprintf('%s dbname=%s', $this->getConfig(), $this->getDbName()));
	}


	public function testPing()
	{
		Tester\Assert::true($this->connection->ping());
	}


	public function testConnectedResource()
	{
		Tester\Assert::notEqual(NULL, $this->connection->getResource());
	}


	public function testConnectionNoConfig(): void
	{
		$this->connection->setConnectionConfig('');
		Tester\Assert::exception(function() {
			$this->connection->connect();
		}, Db\Exceptions\ConnectionException::class);
	}


	public function testConnectionForceNew(): void
	{
		$this->connection->setConnectForceNew(TRUE);
		Tester\Assert::true($this->connection->ping());
	}


	public function testConnectionAsync(): void
	{
		$this->connection->setConnectAsync(TRUE);
		$this->connection->setConnectAsyncWaitSeconds(10);
		Tester\Assert::true($this->connection->ping());
	}


	public function testFailedConnection(): void
	{
		$this->connection->setConnectionConfig($this->getConfig() . 'x');
		Tester\Assert::exception(function() {
			$this->connection->ping();
		}, Db\Exceptions\ConnectionException::class);
	}


	public function testConnectionEvents(): void
	{
		$connect = FALSE;
		$queryDuration = 0;
		$close = FALSE;

		$this->connection->addOnConnect(function() use (&$connect): void {
			$connect = TRUE;
		});

		$this->connection->addOnQuery(function(Db\Connection $connection, Db\Query $query, float $duration) use (&$queryDuration): void {
			$queryDuration = $duration;
		});

		$this->connection->addOnClose(function() use (&$close): void {
			$close = TRUE;
		});

		Tester\Assert::same(1, $this->connection->query('SELECT 1')->fetchSingle());

		$this->connection->close();

		Tester\Assert::true($connect);
		Tester\Assert::true($close);
		Tester\Assert::true($queryDuration > 0);
	}


	public function testFailedQuery(): void
	{
		Tester\Assert::exception(function() {
			try {
				$this->connection->query('SELECT bad_column');
			} catch (Db\Exceptions\QueryException $e) {
				Tester\Assert::true($e->getQuery() instanceof Db\Query);
				throw $e;
			}
		}, Db\Exceptions\QueryException::class);
	}


	public function testTransactions(): void
	{
		$this->connection->begin();

		Tester\Assert::true($this->connection->inTransaction());
		Tester\Assert::same(1, $this->connection->query('SELECT 1')->fetchSingle());

		$this->connection->commit();

		Tester\Assert::false($this->connection->inTransaction());

		$this->connection->begin();

		Tester\Assert::true($this->connection->inTransaction());
		Tester\Assert::same(1, $this->connection->query('SELECT 1')->fetchSingle());

		$this->connection->rollback();
	}


	public function testSavepoints(): void
	{
		$this->connection->begin();

		$this->connection->begin('test');

		Tester\Assert::true($this->connection->inTransaction());
		Tester\Assert::same(1, $this->connection->query('SELECT 1')->fetchSingle());

		$this->connection->commit('test');

		$this->connection->commit();

		Tester\Assert::false($this->connection->inTransaction());

		$this->connection->begin();

		$this->connection->begin('test');

		Tester\Assert::true($this->connection->inTransaction());
		Tester\Assert::same(1, $this->connection->query('SELECT 1')->fetchSingle());

		$this->connection->rollback('test');

		$this->connection->rollback();
	}


	public function testQueryWithParams(): void
	{
		$query = $this->connection->createQuery('SELECT 1');
		Tester\Assert::exception(function() use ($query) {
			$this->connection->query($query, 1);
		}, Db\Exceptions\QueryException::class);
	}


	protected function tearDown(): void
	{
		$this->connection->close();
		parent::tearDown();
	}

}

(new BasicTest())->run();
