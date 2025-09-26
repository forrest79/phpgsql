<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db;

class ColumnValueParser
{
	private DataTypeParser $dataTypeParser;

	/** @var array<string, string> */
	private array $columnsDataTypes;

	/** @var array<string, bool> */
	private array $parsedColumns = [];


	/**
	 * @param array<string, string> $columnsDataTypes
	 */
	public function __construct(DataTypeParser $dataTypeParser, array $columnsDataTypes)
	{
		$this->dataTypeParser = $dataTypeParser;
		$this->columnsDataTypes = $columnsDataTypes;
	}


	/**
	 * @throws Exceptions\ColumnValueParserException
	 */
	public function parseColumnValue(string $column, string|null $rawValue): mixed
	{
		$value = $this->dataTypeParser->parse(
			$this->columnsDataTypes[$column] ?? throw Exceptions\ColumnValueParserException::noColumn($column),
			$rawValue,
		);

		$this->parsedColumns[$column] = true;

		return $value;
	}


	/**
	 * @return list<string>
	 */
	public function getParsedColumns(): array
	{
		return array_keys($this->parsedColumns);
	}

}
