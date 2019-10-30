<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Tests\Unit;

use Forrest79\PhPgSql\Db;
use Tester;

require_once __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class BasicTest extends Tester\TestCase
{

	public function testCreateArray(): void
	{
		Tester\Assert::same('{1,2,3}', Db\Helper::createPgArray([1, 2, 3]));
		Tester\Assert::same('{"A","B","C"}', Db\Helper::createStringPgArray(['A', 'B', 'C']));
		Tester\Assert::same('{"A, B","C","D"}', Db\Helper::createStringPgArray(['A, B', 'C', 'D']));
		Tester\Assert::same('{"A","\"B\"","C"}', Db\Helper::createStringPgArray(['A', '"B"', 'C']));
		Tester\Assert::same('{"1","2","3"}', Db\Helper::createStringPgArray([1, 2, 3]));
	}


	public function testCreateBlankArray(): void
	{
		Tester\Assert::same('{}', Db\Helper::createPgArray([]));
		Tester\Assert::same('{}', Db\Helper::createStringPgArray([]));
	}


	public function testLiteral(): void
	{
		$literal1 = Db\Literal::create('now()');
		Tester\Assert::same('now()', (string) $literal1);
		Tester\Assert::same([], $literal1->getParams());

		$literal2 = Db\Literal::create('generate_series(?, ?, ?)', 1, 2, 3);
		Tester\Assert::same('generate_series(?, ?, ?)', (string) $literal2);
		Tester\Assert::same([1, 2, 3], $literal2->getParams());

		$literal3 = Db\Literal::createArgs('generate_series(?, ?, ?)', [1, 2, 3]);
		Tester\Assert::same('generate_series(?, ?, ?)', (string) $literal3);
		Tester\Assert::same([1, 2, 3], $literal3->getParams());
	}


	public function testDumpSql(): void
	{
		\putenv('TERM=none'); // don't use xterm in this test, if is really used
		Tester\Assert::same("SELECT a \nFROM b JOIN c ON c.a = b.a \nWHERE d = 'x' \nGROUP BY a \nHAVING e = 2 \nORDER BY a \nLIMIT 1 \nOFFSET 2", Db\Helper::dump('SELECT a FROM b JOIN c ON c.a = b.a WHERE d = $1 GROUP BY a HAVING e = 2 ORDER BY a LIMIT 1 OFFSET 2', ['x']));
	}

}

\run(BasicTest::class);
