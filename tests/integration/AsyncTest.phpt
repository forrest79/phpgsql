<?php declare(strict_types=1);

namespace Tests\Integration\Forrest79\PhPgSql\Db;

use Forrest79\PhPgSql\Db;
use Tester;

require_once __DIR__ . '/TestCase.php';

/**
 * @testCase
 */
class AsyncTest extends TestCase
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
		$this->connection = new Db\Connection(sprintf('%s dbname=%s', $this->getConfig(), $this->getDbName()), FALSE, TRUE);
		$this->connection->connect();
	}


	public function testIsConnected(): void
	{
		Tester\Assert::true($this->connection->isConnected(TRUE));
	}


	public function testFetch(): void
	{
		$this->connection->query('
			CREATE TABLE test(
				id serial,
  				name text
			);
		');
		$this->connection->query('INSERT INTO test(name) SELECT \'name\' || generate_series FROM generate_series(2, 1, -1)');

		$result1 = $this->connection->asyncQuery('SELECT id, name FROM test WHERE id = ?', 1);
		$this->connection->waitForAsyncQuery();

		$row1 = $result1->fetch();

		Tester\Assert::same(['id' => 1, 'name' => 'name2'], $row1->toArray());

		$result2 = $this->connection->asyncQueryArray('SELECT id, name FROM test WHERE id = ?', [2]);

		$this->connection->waitForAsyncQuery();

		Tester\Assert::true($result2->isFinished());

		$row2 = $result2->fetch();

		Tester\Assert::same(['id' => 2, 'name' => 'name1'], $row2->toArray());

		$result1->free();
		$result2->free();
	}


	public function testQueryEvent(): void
	{
		$queryDuration = 0;
		$this->connection->addOnQuery(function(Db\Connection $connection, Db\Query $query, ?float $duration) use (&$queryDuration) {
			$queryDuration = $duration;
		});
		$this->connection->asyncQuery('SELECT 1');
		Tester\Assert::null($queryDuration);
	}


	public function testNoAsyncQuery(): void
	{
		Tester\Assert::exception(function(): void {
			$this->connection->waitForAsyncQuery();
		}, Db\Exceptions\ConnectionException::class);
	}


	public function testNoResource(): void
	{
		$resource = $this->connection->asyncQuery('SELECT 1');
		Tester\Assert::exception(function() use ($resource): void {
			$resource->getResource();
		}, Db\Exceptions\ResultException::class);
	}


	protected function tearDown(): void
	{
		$this->connection->close();
		parent::tearDown();
	}

}

(new AsyncTest())->run();
