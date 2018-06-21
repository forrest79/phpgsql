<?php declare(strict_types=1);

namespace Tests\Unit\Forrest79\PhPgSql\Db;

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
		Tester\Assert::same('{\'1\',\'2\',\'3\'}', Db\Helper::createPgArray([1, 2, 3]));
		Tester\Assert::same('{\'A\',\'B\',\'C\'}', Db\Helper::createPgArray(['A', 'B', 'C']));
	}


	public function testCreateBlankArray(): void
	{
		Tester\Assert::same('{}', Db\Helper::createPgArray([]));
	}


	public function testDumpSql(): void
	{
		Tester\Assert::same("SELECT a \nFROM b JOIN c ON c.a = b.a \nWHERE d = 'x' \nGROUP BY a \nHAVING e = 2 \nORDER BY a \nLIMIT 1 \nOFFSET 2", Db\Helper::dump('SELECT a FROM b JOIN c ON c.a = b.a WHERE d = $1 GROUP BY a HAVING e = 2 ORDER BY a LIMIT 1 OFFSET 2', ['x']));
	}

}

(new BasicTest())->run();
