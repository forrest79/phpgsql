<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db;

use Forrest79\PhPgSql\Db\Exceptions;

class Row implements \ArrayAccess, \IteratorAggregate, \Countable
{
	/** @var array */
	private $rawValues;

	/** @var array */
	private $columnsDataTypes;

	/** @var DataTypeParser */
	private $dataTypeParser;

	/** @var array */
	private $values;


	public function __construct(array $values, array $columnsDataTypes, DataTypeParser $dataTypeParser)
	{
		$this->rawValues = $values;
		$this->columnsDataTypes = $columnsDataTypes;
		$this->dataTypeParser = $dataTypeParser;

		$this->values = \array_fill_keys(\array_keys($values), NULL);
	}


	/**
	 * @param string $key
	 * @return mixed
	 * @throws Exceptions\RowException
	 */
	public function __get(string $key)
	{
		return $this->getValue($key);
	}


	/**
	 * @param string $key
	 * @param mixed $value
	 * @return void
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


	public function toArray(): array
	{
		foreach ($this->rawValues as $key => $value) {
			$this->parseValue($key);
		}
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
		if (!is_string($key)) {
			throw Exceptions\RowException::notStringKey();
		}
		return $this->getValue($key);
	}


	/**
	 * @param mixed $key
	 * @param mixed $value
	 * @return void
	 */
	public function offsetSet($key, $value): void
	{
		if (!is_string($key)) {
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
		if (!is_string($key)) {
			throw Exceptions\RowException::notStringKey();
		}
		return $this->existsKey($key);
	}


	/**
	 * @param mixed $key
	 * @return void
	 */
	public function offsetUnset($key): void
	{
		if (!is_string($key)) {
			throw Exceptions\RowException::notStringKey();
		}
		$this->removeValue($key);
	}


	public function hasKey(string $key): bool
	{
		return $this->existsKey($key);
	}


	/**
	 * @param string $key
	 * @return mixed
	 * @throws Exceptions\RowException
	 */
	private function getValue(string $key)
	{
		if (!\array_key_exists($key, $this->values)) {
			throw Exceptions\RowException::noParam($key);
		}

		if (\array_key_exists($key, $this->rawValues)) {
			$this->parseValue($key);
		}

		return $this->values[$key];
	}


	private function parseValue(string $key): void
	{
		$this->values[$key] = $this->dataTypeParser->parse($this->columnsDataTypes[$key], $this->rawValues[$key]);
		unset($this->rawValues[$key]);
	}


	/**
	 * @param string $key
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

}
