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

	public function testIsConnected(): void
	{
		Tester\Assert::true($this->connection->isConnected(TRUE));
	}


	public function testSetErrorVerbosityOnConnect(): void
	{
		$connection = $this->createConnection();

		Tester\Assert::false($connection->isConnected());

		$connection->setErrorVerbosity(\PGSQL_ERRORS_VERBOSE);

		Tester\Assert::exception(static function () use ($connection): void {
			$connection->query('SELECT bad_column');
		}, Db\Exceptions\QueryException::class, '#ERROR:  42703: column "bad_column" does not exist#', Db\Exceptions\QueryException::QUERY_FAILED);

		$connection->close();
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

		$this->connection->asyncQuery('SELECT id, name FROM test WHERE id = ?', 1);
		$result1 = $this->connection->getNextAsyncQueryResult();

		$row1 = $result1->fetch();
		if ($row1 === NULL) {
			throw new \RuntimeException('No data from database were returned');
		}

		Tester\Assert::same(['id' => 1, 'name' => 'name2'], $row1->toArray());

		$this->connection->asyncQueryArgs('SELECT id, name FROM test WHERE id = ?', [2]);

		$result2 = $this->connection->getNextAsyncQueryResult();

		Tester\Assert::false($this->connection->isBusy());

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


	public function testExecuteEvent(): void
	{
		$wasEvent = FALSE;
		$this->connection->addOnQuery(static function () use (&$wasEvent): void {
			$wasEvent = TRUE;
		});
		$this->connection->asyncExecute('SELECT 1')->completeAsyncExecute();
		Tester\Assert::true($wasEvent);
	}


	public function testExecute(): void
	{
		Tester\Assert::noError(function (): void {
			$this->connection->asyncExecute('SELECT 1')->completeAsyncExecute();
		});
	}


	public function testExecutesWithNoComplete(): void
	{
		Tester\Assert::exception(function (): void {
			$this->connection->asyncExecute('SELECT 1')->asyncExecute('SELECT 2');
		}, Db\Exceptions\ConnectionException::class, NULL, Db\Exceptions\ConnectionException::ASYNC_QUERY_ALREADY_SENT);
	}


	public function testCompleteExecuteWithQuery(): void
	{
		Tester\Assert::exception(function (): void {
			$this->connection->asyncQuery('SELECT 1');
			$this->connection->completeAsyncExecute();
		}, Db\Exceptions\ConnectionException::class, NULL, Db\Exceptions\ConnectionException::ASYNC_NO_EXECUTE_WAS_SENT);
	}


	public function testGetQueryResultWithExecute(): void
	{
		Tester\Assert::exception(function (): void {
			$this->connection->asyncExecute('SELECT 1')->getNextAsyncQueryResult();
		}, Db\Exceptions\ConnectionException::class, NULL, Db\Exceptions\ConnectionException::ASYNC_NO_QUERY_WAS_SENT);
	}


	public function testCancelAsyncQuery(): void
	{
		$this->connection->asyncQuery('SELECT 1');
		$this->connection->cancelAsyncQuery();
		Tester\Assert::exception(function (): void {
			$this->connection->getNextAsyncQueryResult();
		}, Db\Exceptions\ConnectionException::class, NULL, Db\Exceptions\ConnectionException::ASYNC_NO_QUERY_WAS_SENT);
	}


	public function testMoreAsyncQueries(): void
	{
		$this->connection->asyncQuery('SELECT 1');
		$result1 = $this->connection->getNextAsyncQueryResult();

		Tester\Assert::same(1, $result1->fetchSingle());
		$this->connection->asyncQuery('SELECT 2');

		$result2 = $this->connection->getNextAsyncQueryResult();
		Tester\Assert::same(2, $result2->fetchSingle());
	}


	public function testMoreAsyncQueriesWithoutWaitForAsyncQuery(): void
	{
		$this->connection->asyncQuery('SELECT 1');
		Tester\Assert::exception(function (): void {
			$this->connection->asyncQuery('SELECT 2');
		}, Db\Exceptions\ConnectionException::class, NULL, Db\Exceptions\ConnectionException::ASYNC_QUERY_ALREADY_SENT);
	}


	public function testMoreAsyncQueriesInOne(): void
	{
		$asyncQuery = $this->connection->asyncQuery('SELECT 1; SELECT 2');

		$result1 = $asyncQuery->getNextResult();
		$result2 = $asyncQuery->getNextResult();

		Tester\Assert::same(1, $result1->fetchSingle());
		Tester\Assert::same(2, $result2->fetchSingle());

		Tester\Assert::exception(static function () use ($asyncQuery): void {
			$asyncQuery->getNextResult();
		}, Db\Exceptions\ResultException::class, NULL, Db\Exceptions\ResultException::NO_OTHER_ASYNC_RESULT);

		$this->connection->asyncQuery('SELECT 3');

		Tester\Assert::exception(static function () use ($asyncQuery): void {
			$asyncQuery->getNextResult();
		}, Db\Exceptions\ResultException::class, NULL, Db\Exceptions\ResultException::ANOTHER_ASYNC_QUERY_IS_RUNNING);

		$result3 = $this->connection->getNextAsyncQueryResult();
		Tester\Assert::same(3, $result3->fetchSingle());

		$this->connection->asyncExecute('SELECT 4');

		Tester\Assert::exception(static function () use ($asyncQuery): void {
			$asyncQuery->getNextResult();
		}, Db\Exceptions\ResultException::class, NULL, Db\Exceptions\ResultException::ANOTHER_ASYNC_QUERY_IS_RUNNING);
	}


	public function testIsBusy(): void
	{
		$this->connection->asyncExecute('SELECT pg_sleep(1)');
		Tester\Assert::true($this->connection->isBusy());
		$this->connection->completeAsyncExecute();
		Tester\Assert::false($this->connection->isBusy());
	}


	public function testError(): void
	{
		$this->connection->asyncExecute('SELECT bad_column');
		Tester\Assert::exception(function (): void {
			$this->connection->completeAsyncExecute();
		}, Db\Exceptions\QueryException::class, NULL, Db\Exceptions\QueryException::ASYNC_QUERY_FAILED);
	}


	protected function createConnection(): Db\Connection
	{
		return new Db\Connection($this->getTestConnectionConfig(), FALSE, TRUE);
	}

}

\run(AsyncTest::class);
