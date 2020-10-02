<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Tests\Unit;

use Forrest79\PhPgSql\Db;
use Forrest79\PhPgSql\Tests;
use Tester;

require_once __DIR__ . '/../TestCase.php';

/**
 * @testCase
 */
class BasicTest extends Tests\TestCase
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


	public function testPreventConnectionSerialization(): void
	{
		Tester\Assert::exception(static function (): void {
			\serialize(new Db\Connection());
		}, \RuntimeException::class, 'You can\'t serialize or unserialize \'Forrest79\PhPgSql\Db\Connection\' instances.');
	}


	public function testDumpSqlToHtml(): void
	{
		Tester\Assert::same(
			"<pre class=\"dump\"><strong style=\"color:blue\">SELECT</strong> <strong style=\"color:green\">DISTINCT</strong> a <strong style=\"color:green\">AS</strong> column \n<strong style=\"color:blue\">FROM</strong> b JOIN c <strong style=\"color:green\">ON</strong> c.a = b.a \n<strong style=\"color:blue\">WHERE</strong> d = <strong style=\"color:brown\">'x'</strong> <strong style=\"color:green\">AND</strong> e = <strong style=\"color:brown\">'text'</strong> \n<strong style=\"color:blue\">GROUP BY</strong> a \n<strong style=\"color:blue\">HAVING</strong> e = <strong style=\"color:green\">TRUE</strong> <strong style=\"color:green\">AND</strong> f = <strong style=\"color:green\">FALSE</strong> \n<strong style=\"color:blue\">ORDER BY</strong> a \n<strong style=\"color:blue\">LIMIT</strong> 1 \n<strong style=\"color:blue\">OFFSET</strong> 2 <em style=\"color:gray\">/* comment */</em></pre>",
			Db\Helper::dump(
				'SELECT DISTINCT a AS column FROM b JOIN c ON c.a = b.a WHERE d = $1 AND e = \'text\' GROUP BY a HAVING e = TRUE AND f = FALSE ORDER BY a LIMIT 1 OFFSET 2 /* comment */',
				['x']
			)
		);
	}


	public function testDumpSqlToCli(): void
	{
		\putenv('TERM=none'); // don't use xterm in this test, if it is really used
		Tester\Assert::same(
			"SELECT DISTINCT a AS column \nFROM b JOIN c ON c.a = b.a \nWHERE d = 'x' AND e = 'text' \nGROUP BY a \nHAVING e = TRUE AND f = FALSE \nORDER BY a \nLIMIT 1 \nOFFSET 2 /* comment */",
			Db\Helper::dump(
				'SELECT DISTINCT a AS column FROM b JOIN c ON c.a = b.a WHERE d = $1 AND e = \'text\' GROUP BY a HAVING e = TRUE AND f = FALSE ORDER BY a LIMIT 1 OFFSET 2 /* comment */',
				['x'],
				'cli'
			)
		);

		\putenv('TERM=xterm'); // and now simulate xterm
		Tester\Assert::same(
			"\033[1;34mSELECT\033[0m \033[1;32mDISTINCT\033[0m a \033[1;32mAS\033[0m column \n\033[1;34mFROM\033[0m b JOIN c \033[1;32mON\033[0m c.a = b.a \n\033[1;34mWHERE\033[0m d = \033[1;35m'x'\033[0m \033[1;32mAND\033[0m e = \033[1;35m'text'\033[0m \n\033[1;34mGROUP BY\033[0m a \n\033[1;34mHAVING\033[0m e = \033[1;32mTRUE\033[0m \033[1;32mAND\033[0m f = \033[1;32mFALSE\033[0m \n\033[1;34mORDER BY\033[0m a \n\033[1;34mLIMIT\033[0m 1 \n\033[1;34mOFFSET\033[0m 2 \033[1;30m/* comment */\033[0m",
			Db\Helper::dump(
				'SELECT DISTINCT a AS column FROM b JOIN c ON c.a = b.a WHERE d = $1 AND e = \'text\' GROUP BY a HAVING e = TRUE AND f = FALSE ORDER BY a LIMIT 1 OFFSET 2 /* comment */',
				['x'],
				'cli'
			)
		);
	}

}

(new BasicTest())->run();
