<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Tests\Integration;

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


	protected function setUp(): void
	{
		parent::setUp();
		$this->connection = new Db\Connection(\sprintf('%s dbname=%s', $this->getConfig(), $this->getDbName()), FALSE, TRUE);
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
		if ($row1 === NULL) {
			throw new \RuntimeException('No data from database were returned');
		}

		Tester\Assert::same(['id' => 1, 'name' => 'name2'], $row1->toArray());

		$result2 = $this->connection->asyncQueryArgs('SELECT id, name FROM test WHERE id = ?', [2]);

		$this->connection->waitForAsyncQuery();

		Tester\Assert::true($result2->isFinished());

		$row2 = $result2->fetch();
		if ($row2 === NULL) {
			throw new \RuntimeException('No data from database were returned');
		}

		Tester\Assert::same(['id' => 2, 'name' => 'name1'], $row2->toArray());

		$result1->free();
		$result2->free();
	}


	public function testQueryEvent(): void
	{
		$queryDuration = 0;
		$this->connection->addOnQuery(static function (Db\Connection $connection, Db\Query $query, ?float $duration) use (&$queryDuration): void {
			$queryDuration = $duration;
		});
		$this->connection->asyncQuery('SELECT 1');
		Tester\Assert::null($queryDuration);
	}


	public function testNoAsyncQuery(): void
	{
		Tester\Assert::exception(function (): void {
			$this->connection->waitForAsyncQuery();
		}, Db\Exceptions\ConnectionException::class, NULL, Db\Exceptions\ConnectionException::ASYNC_NO_QUERY_WAS_SENT);
	}


	public function testNoResource(): void
	{
		$resource = $this->connection->asyncQuery('SELECT 1');
		Tester\Assert::exception(static function () use ($resource): void {
			$resource->getResource();
		}, Db\Exceptions\ResultException::class, NULL, Db\Exceptions\ResultException::NO_RESOURCE);
	}


	protected function tearDown(): void
	{
		$this->connection->close();
		parent::tearDown();
	}

}

(new AsyncTest())->run();
