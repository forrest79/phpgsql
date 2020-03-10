<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db;

class Row extends DummyRow
{
	/** @var array<string, string|NULL> */
	private $rawValues;

	/** @var array<string, string> */
	private $columnsDataTypes;

	/** @var DataTypeParser */
	private $dataTypeParser;


	/**
	 * @param array<string, mixed> $values
	 * @param array<string, string> $columnsDataTypes
	 */
	public function __construct(array $values, array $columnsDataTypes, DataTypeParser $dataTypeParser)
	{
		$this->rawValues = $values;
		$this->columnsDataTypes = $columnsDataTypes;
		$this->dataTypeParser = $dataTypeParser;

		parent::__construct(\array_fill_keys(\array_keys($values), NULL));
	}


	/**
	 * @return array<string, mixed>
	 */
	public function toArray(): array
	{
		foreach ($this->rawValues as $key => $value) { // intentionally not using array_keys($this->rawValues) as $key - this is 2x faster
			$this->parseValue($key);
		}
		return parent::toArray();
	}


	/**
	 * @return mixed
	 * @throws Exceptions\RowException
	 */
	protected function getValue(string $key)
	{
		if (!$this->existsKey($key)) {
			throw Exceptions\RowException::noParam($key);
		}

		if (\array_key_exists($key, $this->rawValues)) {
			$this->parseValue($key);
		}

		return parent::getValue($key);
	}


	private function parseValue(string $key): void
	{
		$this->setValue($key, $this->dataTypeParser->parse($this->columnsDataTypes[$key], $this->rawValues[$key]));
		unset($this->rawValues[$key]);
	}


	/**
	 * @param mixed $value
	 * @return void
	 */
	protected function setValue(string $key, $value): void
	{
		parent::setValue($key, $value);
		unset($this->rawValues[$key]);
	}


	protected function removeValue(string $key): void
	{
		unset($this->rawValues[$key]);
		parent::removeValue($key);
	}

}
