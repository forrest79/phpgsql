<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db;

/**
 * @implements \ArrayAccess<string, mixed>
 * @implements \IteratorAggregate<string, mixed>
 */
class Row implements \ArrayAccess, \IteratorAggregate, \Countable, \JsonSerializable
{
	/** @var array<string, string|NULL> */
	protected $rawValues;

	/** @var array<string, mixed> */
	protected $values;


	public function __construct(array $values)
	{
		$this->rawValues = $values;
		$this->values = \array_fill_keys(\array_keys($values), NULL);
	}


	/**
	 * @return mixed
	 * @throws Exceptions\RowException
	 */
	public function __get(string $key)
	{
		return $this->getValue($key);
	}


	/**
	 * @param mixed $value
	 */
	public function __set(string $key, $value): void
	{
		$this->setValue($key, $value);
	}


	public function __isset(string $key): bool
	{
		return $this->existsKey($key);
	}


	public function __unset(string $key): void
	{
		$this->removeValue($key);
	}


	/**
	 * @return array<string, mixed>
	 */
	public function toArray(): array
	{
		return $this->values;
	}


	public function count(): int
	{
		return \count($this->values);
	}


	/**
	 * @return \ArrayIterator<string, mixed>
	 */
	public function getIterator(): \ArrayIterator
	{
		return new \ArrayIterator($this->toArray());
	}


	/**
	 * @param mixed $key
	 * @return mixed
	 * @throws Exceptions\RowException
	 */
	public function offsetGet($key)
	{
		if (!\is_string($key)) {
			throw Exceptions\RowException::notStringKey();
		}
		return $this->getValue($key);
	}


	/**
	 * @param mixed $key
	 * @param mixed $value
	 */
	public function offsetSet($key, $value): void
	{
		if (!\is_string($key)) {
			throw Exceptions\RowException::notStringKey();
		}
		$this->setValue($key, $value);
	}


	/**
	 * @param mixed $key
	 * @return bool
	 */
	public function offsetExists($key): bool
	{
		if (!\is_string($key)) {
			throw Exceptions\RowException::notStringKey();
		}
		return $this->existsKey($key);
	}


	/**
	 * @param mixed $key
	 */
	public function offsetUnset($key): void
	{
		if (!\is_string($key)) {
			throw Exceptions\RowException::notStringKey();
		}
		$this->removeValue($key);
	}


	public function hasKey(string $key): bool
	{
		return $this->existsKey($key);
	}


	/**
	 * @return mixed
	 * @throws Exceptions\RowException
	 */
	protected function getValue(string $key)
	{
		return $this->values[$key];
	}


	/**
	 * @param mixed $value
	 * @return void
	 */
	private function setValue(string $key, $value): void
	{
		$this->values[$key] = $value;
		unset($this->rawValues[$key]);
	}


	private function existsKey(string $key): bool
	{
		return \array_key_exists($key, $this->values);
	}


	private function removeValue(string $key): void
	{
		unset($this->rawValues[$key]);
		unset($this->values[$key]);
	}


	/**
	 * @return array<string, mixed>
	 */
	public function jsonSerialize(): array
	{
		return $this->toArray();
	}

}
