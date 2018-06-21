<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Fluent\Exceptions;

class QueryBuilderException extends Exception
{
	private const BAD_QUERY_TYPE = 1;
	private const NO_COLUMNS_TO_SELECT = 2;
	private const NO_JOIN_CONDITIONS = 3;
	private const NO_DATA_TO_INSERT = 4;
	private const NO_DATA_TO_UPDATE = 5;
	private const NO_MAIN_TABLE = 6;
	private const BAD_PARAMS_COUNT = 7;


	public static function badQueryType(string $type): self
	{
		return new self(\sprintf('Bad query type \'%s\'.', $type), self::BAD_QUERY_TYPE);
	}


	public static function noColumnsToSelect(): self
	{
		return new self('No columns to select.', self::NO_COLUMNS_TO_SELECT);
	}


	public static function noJoinConditions(string $alias): self
	{
		return new self(\sprintf('No join conditions for table alias \'%s\'.', $alias), self::NO_JOIN_CONDITIONS);
	}


	public static function noDataToInsert(): self
	{
		return new self('No data (values, rows or select) to insert.', self::NO_DATA_TO_INSERT);
	}


	public static function noDataToUpdate(): self
	{
		return new self('No data to update.', self::NO_DATA_TO_UPDATE);
	}


	public static function noMainTable(): self
	{
		return new self('No main table defined.', self::NO_MAIN_TABLE);
	}


	public static function badParamsCount(string $condition, int $expected, int $actual): self
	{
		return new self(\sprintf('In condition \'%s\' is expected %d paramerters, but %d was passed.', $condition, $expected, $actual), self::BAD_PARAMS_COUNT);
	}

}
