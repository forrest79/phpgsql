<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db;

/**
 * @implements \ArrayAccess<string, mixed>
 * @implements \IteratorAggregate<string, mixed>
 */
class Row implements \ArrayAccess, \IteratorAggregate, \Countable, \JsonSerializable
{
	private ColumnValueParser|null $columnValueParser;

	/** @var array<string, string|null> */
	private array $rawValues;

	/** @var array<string, mixed> */
	private array $values;


	/**
	 * @param array<string, string|null> $rawValues
	 */
	public function __construct(ColumnValueParser|null $columnValueParser, array $rawValues)
	{
		if ($columnValueParser === null && $rawValues !== []) {
			throw Exceptions\RowException::columnValueParserMissing();
		}

		$this->columnValueParser = $columnValueParser;
		$this->rawValues = $rawValues;

		$this->values = \array_fill_keys(\array_keys($rawValues), null);
	}


	/**
	 * @throws Exceptions\RowException
	 */
	public function __get(string $column): mixed
	{
		return $this->getValue($column);
	}


	public function __set(string $column, mixed $value): void
	{
		$this->setValue($column, $value);
	}


	public function __isset(string $column): bool
	{
		return $this->hasColumn($column) && ($this->getValue($column) !== null);
	}


	public function __unset(string $column): void
	{
		$this->removeValue($column);
	}


	/**
	 * @return array<string, mixed>
	 */
	public function toArray(): array
	{
		// intentionally not using array_keys($this->rawValues) as $column - this is 2x faster
		foreach ($this->rawValues as $column => $value) {
			$this->parseValue($column);
		}

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
	 * @param string $column
	 * @throws Exceptions\RowException
	 */
	#[\ReturnTypeWillChange]
	public function offsetGet(mixed $column): mixed
	{
		if (!\is_string($column)) {
			throw Exceptions\RowException::notStringKey();
		}

		return $this->getValue($column);
	}


	/**
	 * @param string|null $column
	 */
	public function offsetSet(mixed $column, mixed $value): void
	{
		if (!\is_string($column)) {
			throw Exceptions\RowException::notStringKey();
		}

		$this->setValue($column, $value);
	}


	/**
	 * @param string $column
	 */
	public function offsetExists(mixed $column): bool
	{
		if (!\is_string($column)) {
			throw Exceptions\RowException::notStringKey();
		}

		return $this->hasColumn($column) && ($this->getValue($column) !== null);
	}


	/**
	 * @param string $column
	 */
	public function offsetUnset(mixed $column): void
	{
		if (!\is_string($column)) {
			throw Exceptions\RowException::notStringKey();
		}

		$this->removeValue($column);
	}


	/**
	 * @return list<string>
	 */
	public function getColumns(): array
	{
		return \array_keys($this->values);
	}


	public function hasColumn(string $column): bool
	{
		return \array_key_exists($column, $this->values);
	}


	/**
	 * @throws Exceptions\RowException
	 */
	private function getValue(string $column): mixed
	{
		if (!\array_key_exists($column, $this->values)) {
			throw Exceptions\RowException::noColumn($column);
		}

		if (\array_key_exists($column, $this->rawValues)) {
			$this->parseValue($column);
		}

		return $this->values[$column];
	}


	/**
	 * @return array<string, mixed>
	 */
	public function __serialize(): array
	{
		return $this->toArray();
	}


	/**
	 * @param array<string, mixed> $values
	 */
	public function __unserialize(array $values): void
	{
		$this->columnValueParser = null;
		$this->rawValues = [];
		$this->values = $values;
	}


	private function parseValue(string $column): void
	{
		assert($this->columnValueParser !== null);
		$this->values[$column] = $this->columnValueParser->parseColumnValue($column, $this->rawValues[$column]);
		unset($this->rawValues[$column]);
	}


	private function setValue(string $column, mixed $value): void
	{
		$this->values[$column] = $value;
		unset($this->rawValues[$column]);
	}


	private function removeValue(string $column): void
	{
		unset($this->rawValues[$column]);
		unset($this->values[$column]);
	}


	/**
	 * @return array<string, mixed>
	 */
	public function jsonSerialize(): array
	{
		return $this->toArray();
	}


	/**
	 * @param array<string, mixed> $values
	 */
	public static function from(array $values): static
	{
		$row = new static(null, []);
		$row->values = $values;
		return $row;
	}

}
