<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db;

final class DummyRow implements Rowable
{
	/** @var array<string, mixed> */
	private $values;


	public function __construct(array $values)
	{
		$this->values = $values;
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
		return $this->offsetExists($key);
	}


	/**
	 * @return mixed
	 * @throws Exceptions\RowException
	 */
	protected function getValue(string $key)
	{
		return $this->values[$key];
	}


	protected function setValue(string $key, $value): void
	{
		$this->values[$key] = $value;
	}


	protected function existsKey(string $key): bool
	{
		return \array_key_exists($key, $this->values);
	}


	protected function removeValue(string $key): void
	{
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
