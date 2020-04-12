<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Tests\Integration;

use Forrest79\PhPgSql\Db;
use Tester;

require_once __DIR__ . '/TestCase.php';

/**
 * @testCase
 */
class CollectingResultsTest extends TestCase
{

	public function testResultCollector(): void
	{
		/** @var array<Db\Result> $results */
		$results = [];

		$this->connection->addOnResult(static function (Db\Connection $connection, Db\Result $result) use (&$results): void {
			$results[] = $result;
		});

		$this->connection->query('
			CREATE TABLE test(
				id serial,
  				name text
			);
		');
		$this->connection->query('INSERT INTO test(name) VALUES(?)', 'phpgsql');

		/** @var Db\Row $row */
		$row = $this->connection->query('SELECT id, name FROM test')->fetch();

		Tester\Assert::same('phpgsql', $row->name);

		Tester\Assert::same(3, \count($results));

		/** @var Db\Result $resultInsert */
		$resultInsert = $results[1];

		$queryInsert = $resultInsert->getQuery();

		Tester\Assert::same('INSERT INTO test(name) VALUES($1)', $queryInsert->getSql());
		Tester\Assert::same(['phpgsql'], $queryInsert->getParams());

		Tester\Assert::null($resultInsert->getParsedColumns());

		/** @var Db\Result $resultSelect */
		$resultSelect = $results[2];

		// Try also parse some non-existing column
		Tester\Assert::exception(static function () use ($resultSelect): void {
			$resultSelect->parseColumnValue('non_existing_column', 'someValue');
		}, Db\Exceptions\ResultException::class, NULL, Db\Exceptions\ResultException::NO_COLUMN);

		$querySelect = $resultSelect->getQuery();

		Tester\Assert::same('SELECT id, name FROM test', $querySelect->getSql());
		Tester\Assert::same([], $querySelect->getParams());

		Tester\Assert::equal(['id' => FALSE, 'name' => TRUE], $resultSelect->getParsedColumns());
	}

}

\run(CollectingResultsTest::class);
