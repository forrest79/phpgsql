<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Tests\Unit;

use Forrest79\PhPgSql\Db;
use Forrest79\PhPgSql\Tests;
use Tester;

require_once __DIR__ . '/../TestCase.php';

/**
 * @testCase
 */
class RowTest extends Tests\TestCase
{

	public function testSupportingMultipleIterations(): void
	{
		$fakeResult = new class extends Db\Result
		{

			public function __construct()
			{
			}


			/**
			 * @param mixed $rawValue
			 */
			public function parseColumnValue(string $column, $rawValue): string
			{
				return $rawValue;
			}

		};
		$row = new Db\Row($fakeResult, ['column1' => 'foo', 'column2' => 'bar']);

		$firstIterator = $row->getIterator();
		$firstIterator->rewind();
		Tester\Assert::same('foo', $firstIterator->current());
		Tester\Assert::same('column1', $firstIterator->key());
		$firstIterator->next();
		Tester\Assert::same('bar', $firstIterator->current());
		Tester\Assert::same('column2', $firstIterator->key());
		$firstIterator->next();
		Tester\Assert::false($firstIterator->valid());

		$secondIterator = $row->getIterator();
		$secondIterator->rewind();
		Tester\Assert::same('foo', $secondIterator->current());
		Tester\Assert::same('column1', $secondIterator->key());
		$secondIterator->next();
		Tester\Assert::same('bar', $secondIterator->current());
		Tester\Assert::same('column2', $secondIterator->key());
		$secondIterator->next();
		Tester\Assert::false($secondIterator->valid());

		Tester\Assert::same(2, \iterator_count($row));
		Tester\Assert::same(['column1' => 'foo', 'column2' => 'bar'], \iterator_to_array($row));
	}


	public function testCachingIterator(): void
	{
		$fakeResult = new class extends Db\Result
		{

			public function __construct()
			{
			}


			/**
			 * @param mixed $rawValue
			 */
			public function parseColumnValue(string $column, $rawValue): string
			{
				return $rawValue;
			}

		};
		$row = new Db\Row($fakeResult, ['column1' => 'foo', 'column2' => 'bar']);
		Tester\Assert::type(\Generator::class, $row->getIterator());
		Tester\Assert::same(['column1' => 'foo', 'column2' => 'bar'], \iterator_to_array($row));
		Tester\Assert::type(\ArrayIterator::class, $row->getIterator());
		Tester\Assert::same(['column1' => 'foo', 'column2' => 'bar'], \iterator_to_array($row));
	}

}

(new RowTest())->run();
