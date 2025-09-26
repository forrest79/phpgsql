<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Tests\Unit;

use Forrest79\PhPgSql\Db;
use Forrest79\PhPgSql\Tests;
use Tester;

require_once __DIR__ . '/../TestCase.php';

/**
 * @testCase
 */
final class BasicTest extends Tests\TestCase
{

	public function testRowWithoutColumnValueParser(): void
	{
		Tester\Assert::noError(static function (): void {
			new Db\Row(null, []);
		});

		Tester\Assert::exception(static function (): void {
			new Db\Row(null, ['test' => '1']);
		}, Db\Exceptions\RowException::class, code: Db\Exceptions\RowException::COLUMN_VALUE_PARSER_MISSING);
	}


	public function testRowFrom(): void
	{
		$row = Db\Row::from(['column1' => 1, 'column2' => 'text', 'column3' => true, 'column4' => null]);

		Tester\Assert::same(1, $row->column1);
		Tester\Assert::same('text', $row->column2);
		Tester\Assert::same(true, $row->column3);
		Tester\Assert::same(null, $row->column4);

		Tester\Assert::true(isset($row->column3));
		Tester\Assert::false(isset($row->column4));
		Tester\Assert::true($row->hasColumn('column4'));

		$blankRow = Db\Row::from([]);
		Tester\Assert::same([], $blankRow->toArray());
	}


	public function testCreateArray(): void
	{
		Tester\Assert::same('{1,2,3}', Db\Helper::createPgArray([1, 2, 3]));
		Tester\Assert::same('{NULL,2,NULL,4}', Db\Helper::createPgArray([null, 2, null, 4]));
		Tester\Assert::same('{NULL,2,NULL,1}', Db\Helper::createPgArray([null, 2, null, Tests\TestEnum::One]));
		Tester\Assert::same('{"A","B","C"}', Db\Helper::createStringPgArray(['A', 'B', 'C']));
		Tester\Assert::same('{"A, B","C","D"}', Db\Helper::createStringPgArray(['A, B', 'C', 'D']));
		Tester\Assert::same('{"A","\"B\"","C"}', Db\Helper::createStringPgArray(['A', '"B"', 'C']));
		Tester\Assert::same('{"1","2","3"}', Db\Helper::createStringPgArray([1, 2, 3]));
		Tester\Assert::same('{"1","2","3"}', Db\Helper::createStringPgArray([1, Tests\TestEnum::Two, 3]));
		Tester\Assert::same('{"1",NULL,"3",NULL}', Db\Helper::createStringPgArray([1, null, 3, null]));
	}


	public function testCreateBlankArray(): void
	{
		Tester\Assert::same('{}', Db\Helper::createPgArray([]));
		Tester\Assert::same('{}', Db\Helper::createStringPgArray([]));
	}


	public function testLiteral(): void
	{
		$literal1 = Db\Sql\Literal::create('now()')->getSqlDefinition();
		Tester\Assert::same('now()', $literal1->sql);
		Tester\Assert::same([], $literal1->params);
	}


	public function testExpression(): void
	{
		$expression = Db\Sql\Expression::create('generate_series(?, ?, ?)', 1, 2, 3)->getSqlDefinition();
		Tester\Assert::same('generate_series(?, ?, ?)', $expression->sql);
		Tester\Assert::same([1, 2, 3], $expression->params);

		$expression2 = Db\Sql\Expression::createArgs('generate_series(?, ?, ?)', [1, 2, 3])->getSqlDefinition();
		Tester\Assert::same('generate_series(?, ?, ?)', $expression2->sql);
		Tester\Assert::same([1, 2, 3], $expression2->params);
	}


	public function testPreventConnectionSerialization(): void
	{
		Tester\Assert::exception(static function (): void {
			\serialize(new Db\Connection());
		}, \RuntimeException::class, 'You can\'t serialize or unserialize \'Forrest79\PhPgSql\Db\Connection\' instances.');
	}


	public function testPreventConnectionUnserialization(): void
	{
		Tester\Assert::exception(static function (): void {
			\unserialize('O:31:"Forrest79\PhPgSql\Db\Connection":0:{}');
		}, \RuntimeException::class, 'You can\'t serialize or unserialize \'Forrest79\PhPgSql\Db\Connection\' instances.');
	}


	public function testPrepareConnectionConfigHelper(): void
	{
		Tester\Assert::same('dbname=\'test_db\' port=\'5432\' connection_timeout=\'1.5\'', Db\Helper::prepareConnectionConfig(['dbname' => 'test_db', 'port' => 5432, 'connection_timeout' => 1.5, 'password' => null]));
	}

}

(new BasicTest())->run();
