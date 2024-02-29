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

	public function testCreateArray(): void
	{
		Tester\Assert::same('{1,2,3}', Db\Helper::createPgArray([1, 2, 3]));
		Tester\Assert::same('{NULL,2,NULL,4}', Db\Helper::createPgArray([NULL, 2, NULL, 4]));
		Tester\Assert::same('{NULL,2,NULL,1}', Db\Helper::createPgArray([NULL, 2, NULL, Tests\TestEnum::One]));
		Tester\Assert::same('{"A","B","C"}', Db\Helper::createStringPgArray(['A', 'B', 'C']));
		Tester\Assert::same('{"A, B","C","D"}', Db\Helper::createStringPgArray(['A, B', 'C', 'D']));
		Tester\Assert::same('{"A","\"B\"","C"}', Db\Helper::createStringPgArray(['A', '"B"', 'C']));
		Tester\Assert::same('{"1","2","3"}', Db\Helper::createStringPgArray([1, 2, 3]));
		Tester\Assert::same('{"1","2","3"}', Db\Helper::createStringPgArray([1, Tests\TestEnum::Two, 3]));
		Tester\Assert::same('{"1",NULL,"3",NULL}', Db\Helper::createStringPgArray([1, NULL, 3, NULL]));
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


	public function testPreventConnectionUnserialization(): void
	{
		Tester\Assert::exception(static function (): void {
			\unserialize("O:31:\"Forrest79\PhPgSql\Db\Connection\":14:{s:49:\"\00Forrest79\PhPgSql\Db\Connection\00connectionConfig\";s:0:\"\";s:48:\"\00Forrest79\PhPgSql\Db\Connection\00connectForceNew\";b:0;s:45:\"\00Forrest79\PhPgSql\Db\Connection\00connectAsync\";b:0;s:56:\"\00Forrest79\PhPgSql\Db\Connection\00connectAsyncWaitSeconds\";i:15;s:47:\"\00Forrest79\PhPgSql\Db\Connection\00errorVerbosity\";i:1;s:41:\"\00Forrest79\PhPgSql\Db\Connection\00resource\";N;s:42:\"\00Forrest79\PhPgSql\Db\Connection\00connected\";b:0;s:44:\"\00Forrest79\PhPgSql\Db\Connection\00asyncStream\";N;s:50:\"\00Forrest79\PhPgSql\Db\Connection\00defaultRowFactory\";N;s:47:\"\00Forrest79\PhPgSql\Db\Connection\00dataTypeParser\";N;s:46:\"\00Forrest79\PhPgSql\Db\Connection\00dataTypeCache\";N;s:44:\"\00Forrest79\PhPgSql\Db\Connection\00transaction\";N;s:44:\"\00Forrest79\PhPgSql\Db\Connection\00asyncHelper\";O:32:\"Forrest79\PhPgSql\Db\AsyncHelper\":3:{s:44:\"\00Forrest79\PhPgSql\Db\AsyncHelper\00connection\";r:1;s:44:\"\00Forrest79\PhPgSql\Db\AsyncHelper\00asyncQuery\";N;s:51:\"\00Forrest79\PhPgSql\Db\AsyncHelper\00asyncExecuteQuery\";N;}s:39:\"\00Forrest79\PhPgSql\Db\Connection\00events\";O:27:\"Forrest79\PhPgSql\Db\Events\":5:{s:39:\"\00Forrest79\PhPgSql\Db\Events\00connection\";r:1;s:38:\"\00Forrest79\PhPgSql\Db\Events\00onConnect\";a:0:{}s:36:\"\00Forrest79\PhPgSql\Db\Events\00onClose\";a:0:{}s:36:\"\00Forrest79\PhPgSql\Db\Events\00onQuery\";a:0:{}s:37:\"\00Forrest79\PhPgSql\Db\Events\00onResult\";a:0:{}}}");
		}, \RuntimeException::class, 'You can\'t serialize or unserialize \'Forrest79\PhPgSql\Db\Connection\' instances.');
	}

}

(new BasicTest())->run();
