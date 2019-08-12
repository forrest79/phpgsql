<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Fluent\Exceptions;

use Forrest79\PhPgSql\Db;

class QueryBuilderException extends Exception
{
	public const BAD_QUERY_TYPE = 1;
	public const NO_COLUMNS_TO_SELECT = 2;
	public const NO_JOIN_CONDITIONS = 3;
	public const NO_DATA_TO_INSERT = 4;
	public const NO_DATA_TO_UPDATE = 5;
	public const NO_MAIN_TABLE = 6;
	public const BAD_PARAMS_COUNT = 7;
	public const BAD_QUERYABLE = 8;


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


	public static function badQueryable(Db\Queryable $queryable): self
	{
		return new self(\sprintf('This queryable type \'%s\' is not supported.', \get_class($queryable)), self::BAD_QUERYABLE);
	}

}
