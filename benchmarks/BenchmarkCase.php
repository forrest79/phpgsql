<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Benchmarks;

abstract class BenchmarkCase
{
	/** @var int */
	protected $defaultRepeat = 10000;


	public function run(): void
	{
		if (\defined('__PHPSTAN_RUNNING__')) {
			return;
		}

		$class = new \ReflectionClass($this);
		$methods = $class->getMethods(\ReflectionMethod::IS_PUBLIC);

		echo \sprintf('|----------------------------------------------------------------------------------|--------------|-------------|') . \PHP_EOL;
		echo \sprintf('| %-80s | Time per run |      Repeat |', $this->title()) . \PHP_EOL;
		echo \sprintf('|----------------------------------------------------------------------------------|--------------|-------------|') . \PHP_EOL;

		foreach ($methods as $method) {
			$benchmarkMethod = $method->name;

			if (\strpos($benchmarkMethod, 'benchmark') !== 0) {
				continue;
			}

			$docComment = (string) $method->getDocComment();

			$method = [$this, $benchmarkMethod];
			if (!\is_callable($method)) {
				throw new \RuntimeException(\sprintf('Method \'%s\' is not callable.', $benchmarkMethod));
			}

			$repeat = (int) self::getAnotation($docComment, 'repeat');

			$this->runBenchmark(
				$method,
				self::getAnotation($docComment, 'title') ?? \substr($benchmarkMethod, 9),
				$repeat > 0 ? $repeat : $this->defaultRepeat
			);
		}

		echo \sprintf('|----------------------------------------------------------------------------------|--------------|-------------|') . \PHP_EOL . \PHP_EOL;

		$this->tearDown();
	}


	/**
	 * Example anotations for benchmarks methods:
	 *
	 * @title if you avoid this, method name is used
	 * @repeat if you avoid this, default repeat is used
	 */
	private function runBenchmark(callable $method, string $title, int $repeat): void
	{
		$start = \microtime(TRUE);

		for ($i = 0; $i < $repeat; $i++) {
			$method();
		}

		echo \sprintf('| %-80s | %012.10f | %11d |', \substr($title, 0, 80), (\microtime(TRUE) - $start) / $repeat, $repeat) . \PHP_EOL;
	}


	protected function tearDown(): void
	{
	}


	private static function getAnotation(string $docComment, string $name): ?string
	{
		if ((int) \preg_match(\sprintf('#[\\s*]@%s[\\s*](.+)#', \preg_quote($name, '#')), $docComment, $m) === 0) {
			return NULL;
		}
		return \trim($m[1]);
	}


	abstract protected function title(): string;

}
