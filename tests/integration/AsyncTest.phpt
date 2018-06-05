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
		$this->connection = new Db\Connection(sprintf('%s dbname=%s', $this->getConfig(), $this->getTableName()), FALSE, TRUE);
		$this->connection->connect();
	}


	public function testIsConnected()
	{
		Tester\Assert::true($this->connection->isConnected(TRUE));
	}


	public function testFetch()
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


	protected function tearDown(): void
	{
		$this->connection->close();
		parent::tearDown();
	}

}

(new AsyncTest())->run();
