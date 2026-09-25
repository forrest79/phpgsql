<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db;

/**
 * @template T of object
 * @implements \Iterator<int, T>
 */
class ObjectIterator implements \Iterator
{
	private Result $result;

	/** @var class-string<T> */
	private string $class;

	/** @var T|null */
	private object|null $object = null;

	private int $pointer;


	/**
	 * @param class-string<T> $class
	 */
	public function __construct(Result $result, string $class)
	{
		$this->result = $result;
		$this->class = $class;
	}


	/**
	 * @throws Exceptions\ResultException
	 */
	public function rewind(): void
	{
		$this->pointer = 0;
		$this->result->seek(0);
		$this->object = $this->result->fetchObject($this->class);
	}


	public function key(): int
	{
		return $this->pointer;
	}


	/**
	 * @return T
	 */
	public function current(): object
	{
		return $this->object;
	}


	/**
	 * @throws Exceptions\ResultException
	 */
	public function next(): void
	{
		$this->object = $this->result->fetchObject($this->class);
		$this->pointer++;
	}


	public function valid(): bool
	{
		return $this->object !== null;
	}

}
