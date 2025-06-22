<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Tests\Integration;

use Forrest79\PhPgSql\Db;
use Tester;

require_once __DIR__ . '/TestCase.php';

/**
 * @testCase
 */
final class PreparedStatementTest extends TestCase
{

	public function testFetch(): void
	{
		$this->connection->query('
			CREATE TABLE test(
				id serial,
  				name text
			);
		');
		$this->connection->query('INSERT INTO test(name) SELECT \'name\' || generate_series FROM generate_series(2, 1, -1)');

		$preparedStatement = $this->connection->prepareStatement('SELECT id, name || \'\\?\' AS name FROM test WHERE id = ?');

		$result1 = $preparedStatement->execute(1);

		$row1 = $result1->fetch();
		if ($row1 === NULL) {
			throw new \RuntimeException('No data from database were returned');
		}

		Tester\Assert::same(['id' => 1, 'name' => 'name2?'], $row1->toArray());

		$result2 = $preparedStatement->execute(2);

		$row2 = $result2->fetch();
		if ($row2 === NULL) {
			throw new \RuntimeException('No data from database were returned');
		}

		Tester\Assert::same(['id' => 2, 'name' => 'name1?'], $row2->toArray());

		$result1->free();
		$result2->free();
	}


	public function testParameters(): void
	{
		$preparedStatement = $this->connection->prepareStatement('SELECT 1 AS clm1 WHERE 1 = $1 AND TRUE = $2 AND NULL::integer IS NOT DISTINCT FROM $3::integer');
		$row = $preparedStatement->execute(1, TRUE, NULL)->fetch();
		if ($row === NULL) {
			throw new \RuntimeException('No data from database were returned');
		}

		Tester\Assert::same(['clm1' => 1], $row->toArray());
	}


	public function testErrors(): void
	{
		$preparedStatement1 = $this->connection->prepareStatement('SELECTs 1');

		Tester\Assert::exception(static function () use ($preparedStatement1): void {
			$preparedStatement1->execute();
		}, Db\Exceptions\QueryException::class, NULL, Db\Exceptions\QueryException::PREPARED_STATEMENT_QUERY_FAILED);

		$preparedStatement2 = $this->connection->prepareStatement('SELECT 1 AS clm1 WHERE 1 = $1');

		Tester\Assert::exception(static function () use ($preparedStatement2): void {
			$preparedStatement2->execute(0, 1);
		}, Db\Exceptions\QueryException::class, NULL, Db\Exceptions\QueryException::PREPARED_STATEMENT_QUERY_FAILED);
	}


	public function testQueryEvent(): void
	{
		$queryDuration = 0;
		$queryPrapareStatementName = NULL;
		$this->connection->addOnQuery(static function (Db\Connection $connection, Db\Query $query, float|NULL $timeNs, string|NULL $prepareStatementName) use (&$queryDuration, &$queryPrapareStatementName): void {
			$queryDuration = $timeNs;
			$queryPrapareStatementName = $prepareStatementName;
		});

		$preparedStatement = $this->connection->prepareStatement('SELECT pg_sleep(1)');
		$preparedStatement->execute();

		Tester\Assert::true(($queryDuration ?? 0) > 0);
		Tester\Assert::same('phpgsql1', $queryPrapareStatementName);
	}


	public function testAsyncFetch(): void
	{
		$this->connection->query('
			CREATE TABLE test(
				id serial,
  				name text
			);
		');

		$this->connection->query('INSERT INTO test(name) SELECT \'name\' || generate_series FROM generate_series(2, 1, -1)');

		$preparedStatement = $this->connection->asyncPrepareStatement('SELECT id, name FROM test WHERE id = ?');

		$result1 = $preparedStatement->execute(1)->getNextResult();

		$row1 = $result1->fetch();
		if ($row1 === NULL) {
			throw new \RuntimeException('No data from database were returned');
		}

		Tester\Assert::same(['id' => 1, 'name' => 'name2'], $row1->toArray());

		$result2 = $preparedStatement->execute(2)->getNextResult();

		$row2 = $result2->fetch();
		if ($row2 === NULL) {
			throw new \RuntimeException('No data from database were returned');
		}

		Tester\Assert::same(['id' => 2, 'name' => 'name1'], $row2->toArray());

		$result1->free();
		$result2->free();
	}


	public function testAsyncErrors(): void
	{
		$preparedStatement1 = $this->connection->asyncPrepareStatement('SELECTs 1');

		Tester\Assert::exception(static function () use ($preparedStatement1): void {
			$preparedStatement1->execute();
		}, Db\Exceptions\QueryException::class, NULL, Db\Exceptions\QueryException::ASYNC_PREPARED_STATEMENT_QUERY_FAILED);

		$preparedStatement2 = $this->connection->asyncPrepareStatement('SELECT 1 AS clm1 WHERE 1 = $1');

		Tester\Assert::exception(static function () use ($preparedStatement2): void {
			$preparedStatement2->execute(0, 1)->getNextResult();
		}, Db\Exceptions\QueryException::class, NULL, Db\Exceptions\QueryException::ASYNC_PREPARED_STATEMENT_QUERY_FAILED);

		$preparedStatement2->execute(2);
		Tester\Assert::exception(static function () use ($preparedStatement2): void {
			$preparedStatement2->execute(3)->getNextResult();
		}, Db\Exceptions\QueryException::class, NULL, Db\Exceptions\QueryException::ASYNC_PREPARED_STATEMENT_QUERY_FAILED);
	}


	public function testAsyncQueryEvent(): void
	{
		$queryDuration = NULL;
		$queryPrapareStatementName = NULL;
		$this->connection->addOnQuery(static function (Db\Connection $connection, Db\Query $query, float|NULL $timeNs, string|NULL $prepareStatementName) use (&$queryDuration, &$queryPrapareStatementName): void {
			$queryDuration = $timeNs;
			$queryPrapareStatementName = $prepareStatementName;
		});

		$preparedStatement = $this->connection->asyncPrepareStatement('SELECT pg_sleep(1)');
		$preparedStatement->execute();

		Tester\Assert::null($queryDuration);
		Tester\Assert::same('phpgsql1', $queryPrapareStatementName);
	}

}

(new PreparedStatementTest())->run();
