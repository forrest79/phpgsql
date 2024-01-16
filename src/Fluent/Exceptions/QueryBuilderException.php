<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Fluent\Exceptions;

class QueryBuilderException extends Exception
{
	public const BAD_QUERY_TYPE = 1;
	public const NO_COLUMNS_TO_SELECT = 2;
	public const NO_ON_CONDITION = 3;
	public const NO_DATA_TO_INSERT = 4;
	public const NO_DATA_TO_UPDATE = 5;
	public const DATA_CANT_CONTAIN_ARRAY = 6;
	public const NO_MAIN_TABLE = 7;
	public const BAD_PARAMS_COUNT = 8;
	public const NO_CORRESPONDING_TABLE = 9;
	public const SELECT_ALL_COLUMNS_CANT_BE_COMBINED_WITH_CONCRETE_COLUMN_FOR_INSERT_SELECT_WITH_COLUMN_DETECTION = 10;
	public const NO_USING = 11;
	public const NO_WHEN = 12;


	public static function badQueryType(string $type): self
	{
		return new self(\sprintf('Bad query type \'%s\'.', $type), self::BAD_QUERY_TYPE);
	}


	public static function noColumnsToSelect(): self
	{
		return new self('No columns to select.', self::NO_COLUMNS_TO_SELECT);
	}


	public static function noOnCondition(string $alias): self
	{
		return new self(\sprintf('There is no conditions for ON for table alias \'%s\'.', $alias), self::NO_ON_CONDITION);
	}


	public static function noDataToInsert(): self
	{
		return new self('No data (values, rows or select) to insert.', self::NO_DATA_TO_INSERT);
	}


	public static function noDataToUpdate(): self
	{
		return new self('No data to update.', self::NO_DATA_TO_UPDATE);
	}


	public static function dataCantContainArray(): self
	{
		return new self('You can\'t use array in data for INSERT or UPDATE. Convert array with PhPgSql\Db\Helper::createPgArray() or ::createStringPgArray().', self::DATA_CANT_CONTAIN_ARRAY);
	}


	public static function noMainTable(): self
	{
		return new self('No main table defined.', self::NO_MAIN_TABLE);
	}


	public static function badParamsCount(string $condition, int $expected, int $actual): self
	{
		return new self(\sprintf('In condition \'%s\' is expected %d paramerters, but %d was passed.', $condition, $expected, $actual), self::BAD_PARAMS_COUNT);
	}


	/**
	 * @param array<string> $aliases
	 */
	public static function noCorrespondingTable(array $aliases): self
	{
		return new self('There are extra join conditions without corresponding tables: ' . implode(', ', $aliases), self::NO_CORRESPONDING_TABLE);
	}


	public static function selectAllColumnsCantBeCombinedWithConcreteColumnForInsertSelectWithColumnDetection(): self
	{
		return new self('You can\'t use \'SELECT *\' and also some concrete column for INSERT - SELECT with column detection.', self::SELECT_ALL_COLUMNS_CANT_BE_COMBINED_WITH_CONCRETE_COLUMN_FOR_INSERT_SELECT_WITH_COLUMN_DETECTION);
	}


	public static function noUsing(): self
	{
		return new self('No USING for MERGE.', self::NO_USING);
	}


	public static function noWhen(): self
	{
		return new self('No WHEN for MERGE.', self::NO_WHEN);
	}

}
