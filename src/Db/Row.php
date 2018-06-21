<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db;

use Forrest79\PhPgSql\Db\Exceptions;

class Row implements \ArrayAccess, \IteratorAggregate, \Countable
{
	/** @var array */
	private $rawValues;

	/** @var array */
	private $columnsDataTypes;

	/** @var DataTypeParsers\DataTypeParser */
	private $dataTypeParser;

	/** @var array */
	private $values;


	public function __construct(array $values, array $columnsDataTypes, DataTypeParsers\DataTypeParser $dataTypeParser)
	{
		$this->rawValues = $values;
		$this->columnsDataTypes = $columnsDataTypes;
		$this->dataTypeParser = $dataTypeParser;

		$this->values = \array_combine(\array_keys($values), \array_fill(0, count($values), NULL));
	}


	/**
	 * @throws Exceptions\RowException
	 */
	public function __get(string $key)
	{
		return $this->getValue($key);
	}


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
	 * @throws Exceptions\RowException
	 */
	public function offsetGet($key)
	{
		return $this->getValue($key);
	}


	public function offsetSet($key, $value): void
	{
		$this->setValue($key, $value);
	}


	public function offsetExists($key): bool
	{
		return $this->existsKey($key);
	}


	public function offsetUnset($key): void
	{
		$this->removeValue($key);
	}


	public function hasKey($key): bool
	{
		return $this->existsKey($key);
	}


	/**
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


	private function setValue(string $key, $value): void
	{
		$this->values[$key] = $value;
	}


	private function existsKey(string $key): bool
	{
		return \array_key_exists($key, $this->values);
	}


	private function removeValue(string $key): void
	{
		unset($this->values[$key]);
	}

}
