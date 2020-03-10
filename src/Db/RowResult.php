<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db;

class RowResult extends Row
{
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
		$this->columnsDataTypes = $columnsDataTypes;
		$this->dataTypeParser = $dataTypeParser;
		parent::__construct($values);
	}

	/**
	 * @return array<string, mixed>
	 */
	public function toArray(): array
	{
		foreach ($this->rawValues as $key => $value) { // intentionally not using array_keys($this->rawValues) as $key - this is 2x faster
			$this->parseValue($key);
		}
		return $this->values;
	}


	/**
	 * @return mixed
	 * @throws Exceptions\RowException
	 */
	protected function getValue(string $key)
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

}
