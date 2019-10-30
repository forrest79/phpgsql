<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Benchmarks;

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


	public function __construct()
	{
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

}

\run(BasicPhpBenchmark::class);
