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
		$literal1 = Db\Sql\Literal::create('now()');
		Tester\Assert::same('now()', $literal1->getSql());
		Tester\Assert::same([], $literal1->getParams());
	}


	public function testExpression(): void
	{
		$expression = Db\Sql\Expression::create('generate_series(?, ?, ?)', 1, 2, 3);
		Tester\Assert::same('generate_series(?, ?, ?)', $expression->getSql());
		Tester\Assert::same([1, 2, 3], $expression->getParams());

		$expression2 = Db\Sql\Expression::createArgs('generate_series(?, ?, ?)', [1, 2, 3]);
		Tester\Assert::same('generate_series(?, ?, ?)', $expression2->getSql());
		Tester\Assert::same([1, 2, 3], $expression2->getParams());
	}


	public function testDumpSql(): void
	{
		\putenv('TERM=none'); // don't use xterm in this test, if is really used
		Tester\Assert::same("SELECT a \nFROM b JOIN c ON c.a = b.a \nWHERE d = 'x' \nGROUP BY a \nHAVING e = 2 \nORDER BY a \nLIMIT 1 \nOFFSET 2", Db\Helper::dump('SELECT a FROM b JOIN c ON c.a = b.a WHERE d = $1 GROUP BY a HAVING e = 2 ORDER BY a LIMIT 1 OFFSET 2', ['x']));
	}

}

\run(BasicTest::class);
