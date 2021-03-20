<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Benchmarks;

use Forrest79\PhPgSql\Db;

require __DIR__ . '/boostrap.php';

class BasicPhpBenchmark extends BenchmarkCase
{
	private const CONDITION = 1;
	private const ARRAY = [1];
	private const NULL_ARRAY = NULL;

	/** @var int */
	protected $defaultRepeat = 1000000;

	private const TEST_ARRAY = [1, 2, 3];

	/** @var callable */
	private $updateFunction;


	protected function setUp(): void
	{
		parent::setUp();

		$this->updateFunction = static function (int $x): int {
			return $x ^ 2;
		};
	}


	protected function title(): string
	{
		return 'Basic PHP';
	}


	/**
	 * @title update array with "array_map"
	 */
	public function benchmarkUpdateArrayMap(): void
	{
		$test = self::TEST_ARRAY;
		$test = \array_map(static function (int $x): int {
			return $x ^ 2;
		}, $test);
	}


	/**
	 * @title update array with "array_map" (prepared function)
	 */
	public function benchmarkUpdateArrayMapPrepared(): void
	{
		$test = self::TEST_ARRAY;
		$test = \array_map($this->updateFunction, $test);
	}


	/**
	 * @title update array with "array_walk"
	 */
	public function benchmarkUpdateArrayWalk(): void
	{
		$test = self::TEST_ARRAY;
		\array_walk($test, static function (int &$x): void {
			$x ^= 2;
		});
	}


	/**
	 * @title update array with "foreach"
	 */
	public function benchmarkUpdateForeach(): void
	{
		$test = self::TEST_ARRAY;
		foreach ($test as $i => $x) {
			$test[$i] = $x ^ 2;
		}
	}


	/**
	 * @title update array with "foreach" (reference)
	 */
	public function benchmarkUpdateForeachReference(): void
	{
		$test = self::TEST_ARRAY;
		foreach ($test as &$x) {
			$x ^= 2;
		}
	}


	/**
	 * @title update array with "for"
	 */
	public function benchmarkUpdateFor(): void
	{
		$test = self::TEST_ARRAY;
		$length = \count($test);
		for ($i = 0; $i < $length; $i++) {
			$test[$i] ^= 2;
		}
	}


	/**
	 * @title iterate array with "array_map"
	 */
	public function benchmarkIterateArrayMap(): void
	{
		$test = [];
		\array_map(static function (int $x) use ($test): void {
			$test[] = $x;
		}, self::TEST_ARRAY);
	}


	/**
	 * @title iterate array with "array_walk"
	 */
	public function benchmarkIterateArrayWalk(): void
	{
		$source = self::TEST_ARRAY;
		$test = [];
		\array_walk($source, static function (int $x) use ($test): void {
			$test[] = $x;
		});
	}


	/**
	 * @title iterate array with "foreach"
	 */
	public function benchmarkIterateForeach(): void
	{
		$test = [];
		foreach (self::TEST_ARRAY as $value) {
			$test[] = $value;
		}
	}


	/**
	 * @title iterate array with "for"
	 */
	public function benchmarkIterateFor(): void
	{
		$test = [];
		$length = \count(self::TEST_ARRAY);
		for ($i = 0; $i < $length; $i++) {
			$test[] = self::TEST_ARRAY[$i];
		}
	}


	/**
	 * @title iterate blank array (always use foreach)
	 */
	public function benchmarkIterateBlankArrayAlways(): void
	{
		$test = [];
		foreach ($test as $item) {
		}
	}


	/**
	 * @title iterate blank array (skip iteration with if)
	 */
	public function benchmarkIterateBlankArray(): void
	{
		$test = [];
		if ($test !== []) {
			foreach ($test as $item) {
			}
		}
	}


	/**
	 * @title array change cast with "array_map"
	 */
	public function benchmarkArrayChangeCastArrayMap(): void
	{
		$test = self::TEST_ARRAY;
		$test = \array_map('floatval', $test);
	}


	/**
	 * @title array change cast with "array_map" (anonymous)
	 */
	public function benchmarkArrayChangeCastArrayMapAnonymous(): void
	{
		$test = self::TEST_ARRAY;
		$test = \array_map(static function (int $x): float {
			return \floatval($x);
		}, $test);
	}


	/**
	 * @title array change cast with "foreach"
	 */
	public function benchmarkArrayChangeCastForeach(): void
	{
		$test = self::TEST_ARRAY;
		foreach ($test as $i => $x) {
			$test[$i] = \floatval($x);
		}
	}


	/**
	 * @title microtime with miliseconds
	 */
	public function benchmarkMicrotimeMs(): void
	{
		\microtime(TRUE);
	}


	/**
	 * @title ternary operator with array access
	 */
	public function benchmarkTernaryWithArrayAccess(): void
	{
		\rand() === self::CONDITION ? NULL : self::ARRAY[0];
	}


	/**
	 * @title NULL ternary operator with array access
	 */
	public function benchmarkNullTernaryWithArrayAccess(): void
	{
		self::ARRAY[0] ?? \rand();
	}


	/**
	 * @title NULL ternary operator with array access on NULL array
	 */
	public function benchmarkNullTernaryWithNullArrayAccess(): void
	{
		self::NULL_ARRAY[0] ?? \rand();
	}


	/**
	 * @title string concatenation
	 */
	public function benchmarkStringConcatenation(): void
	{
		$i = 'a';
		$i = '$' . $i . ', ';
	}


	/**
	 * @title string concatenation (double quotes)
	 */
	public function benchmarkStringConcatenationDoubleQuotes(): void
	{
		$i = 'a';
		$i = "$$i, ";
	}


	/**
	 * @title string concatenation (with sprintf)
	 */
	public function benchmarkStringConcatenationSprintf(): void
	{
		$i = 'a';
		$i = \sprintf('$%d, ', $i);
	}


	/**
	 * @title more strings concatenation
	 */
	public function benchmarkMoreStringsConcatenation(): void
	{
		$x1 = 'a';
		$y1 = 'b';
		$z1 = 'c';
		$x2 = 'a';
		$y2 = 'b';
		$z2 = 'c';
		$z2 = '$' . $x1 . ', $' . $y1 . ', $' . $z1 . ', $' . $x2 . ', $' . $y2 . ', $' . $z2;
	}


	/**
	 * @title more strings concatenation (double quotes)
	 */
	public function benchmarkMoreStringsConcatenationDoubleQuotes(): void
	{
		$x1 = 'a';
		$y1 = 'b';
		$z1 = 'c';
		$x2 = 'a';
		$y2 = 'b';
		$z2 = 'c';
		$z2 = "$$x1, $$y1, $$z1, $$x2, $$y2, $$z2";
	}


	/**
	 * @title more strings concatenation (with sprintf)
	 */
	public function benchmarkMoreStringsConcatenationSprintf(): void
	{
		$x1 = 'a';
		$y1 = 'b';
		$z1 = 'c';
		$x2 = 'a';
		$y2 = 'b';
		$z2 = 'c';
		$z2 = \sprintf('$%d, $%d, $%d, $%d, $%d, $%d', $x1, $y1, $z1, $x2, $y2, $z2);
	}


	/**
	 * @title string concatenation in cycle via array
	 */
	public function benchmarkStringConcatenationArray(): void
	{
		$keys = [];
		for ($i = 0; $i < 10; $i++) {
			$keys[] = '$' . $i;
		}
		$keys = \implode(', ', $keys);
	}


	/**
	 * @title string concatenation in cycle via array (with sprintf)
	 */
	public function benchmarkStringConcatenationArraySprintf(): void
	{
		$keys = [];
		for ($i = 0; $i < 10; $i++) {
			$keys[] = \sprintf('$%d, ', $i);
		}
		$keys = \implode(', ', $keys);
	}


	/**
	 * @title string detect not set items in array (array_key_exists)
	 */
	public function benchmarkDetectNotSetItemsInArray(): void
	{
		$keys = ['testKey1' => NULL, 'testKey2' => ''];
		\array_key_exists('testKey1', $keys);
		\array_key_exists('testKey2', $keys);
	}


	/**
	 * @title string detect not set items in array (isset || array_key_exists) (no items)
	 */
	public function benchmarkDetectNotSetItemsInArrayNoItems(): void
	{
		$keys = ['testKey1' => NULL, 'testKey2' => ''];
		isset($keys['testKey']) || \array_key_exists('testKey', $keys);
		isset($keys['testKey']) || \array_key_exists('testKey', $keys);
	}


	/**
	 * @title string detect not set items in array (isset || array_key_exists) (mixed items)
	 */
	public function benchmarkDetectNotSetItemsInArrayNullsAndNoItems(): void
	{
		$keys = ['testKey1' => NULL, 'testKey2' => ''];
		isset($keys['testKey1']) || \array_key_exists('testKey1', $keys);
		isset($keys['testKey2']) || \array_key_exists('testKey2', $keys);
	}


	/**
	 * @title string detect not set items in array (isset || array_key_exists) (NULL items)
	 */
	public function benchmarkDetectNotSetItemsInArrayNulls(): void
	{
		$keys = ['testKey1' => NULL, 'testKey2' => ''];
		isset($keys['testKey1']) || \array_key_exists('testKey1', $keys);
		isset($keys['testKey1']) || \array_key_exists('testKey1', $keys);
	}


	/**
	 * @title string detect not set items in array (isset || array_key_exists) (all items)
	 */
	public function benchmarkDetectNotSetItemsInArrayAll(): void
	{
		$keys = ['testKey1' => NULL, 'testKey2' => ''];
		isset($keys['testKey2']) || \array_key_exists('testKey2', $keys);
		isset($keys['testKey2']) || \array_key_exists('testKey2', $keys);
	}


	/**
	 * @title create new instance via new operator
	 */
	public function benchmarkCreateNewInstanceViaNewOperator(): void
	{
		new Db\Sql\Literal('now()');
	}


	/**
	 * @title create new instance via static method
	 */
	public function benchmarkCreateNewInstanceViaStaticMethod(): void
	{
		Db\Sql\Literal::create('now()');
	}

}

(new BasicPhpBenchmark())->run();
