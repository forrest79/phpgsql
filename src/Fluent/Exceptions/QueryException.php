<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Fluent\Exceptions;

class QueryException extends Exception
{
	public const int ONLY_ONE_MAIN_TABLE = 1;
	public const int TABLE_ALIAS_ALREADY_EXISTS = 2;
	public const int NON_EXISTING_QUERY_PARAM = 3;
	public const int SQL_MUST_HAVE_ALIAS = 4;
	public const int PARAM_MUST_BE_SCALAR_OR_ENUM_OR_EXPRESSION = 5;
	public const int CANT_UPDATE_QUERY_AFTER_EXECUTE = 6;
	public const int YOU_MUST_EXECUTE_QUERY_BEFORE_THAT = 7;
	public const int MERGE_ONLY_ONE_USING = 8;
	public const int ON_CONFLICT_WHERE_NOT_FOR_CONSTRAINT = 9;


	public static function onlyOneMainTable(): self
	{
		return new self('There can be only one main table.', self::ONLY_ONE_MAIN_TABLE);
	}


	public static function tableAliasAlreadyExists(string $alias): self
	{
		return new self(\sprintf('This table alias "%s" already exists.', $alias), self::TABLE_ALIAS_ALREADY_EXISTS);
	}


	/**
	 * @param list<string> $params
	 */
	public static function nonExistingQueryParam(string $param, array $params): self
	{
		return new self(\sprintf('Non existing query parameter "%s". You can use only these parameters "%s".', $param, \implode(', ', $params)), self::NON_EXISTING_QUERY_PARAM);
	}


	public static function sqlMustHaveAlias(): self
	{
		return new self('Sql must have an alias.', self::SQL_MUST_HAVE_ALIAS);
	}


	public static function columnMustBeScalarOrEnumOrExpression(): self
	{
		return new self('Column must be a scalar, an enum, Fluent\Query or Db\Sql.', self::PARAM_MUST_BE_SCALAR_OR_ENUM_OR_EXPRESSION);
	}


	public static function cantUpdateQueryAfterExecute(): self
	{
		return new self('Can\'t update query after execute.', self::CANT_UPDATE_QUERY_AFTER_EXECUTE);
	}


	public static function youMustExecuteQueryBeforeThat(): self
	{
		return new self('You must execute query before that', self::YOU_MUST_EXECUTE_QUERY_BEFORE_THAT);
	}


	public static function mergeOnlyOneUsing(): self
	{
		return new self('USING can be set only once.', self::MERGE_ONLY_ONE_USING);
	}


	public static function onConflictWhereNotForConstraint(): self
	{
		return new self('ON CONFLICT with constraint can\'t have a WHERE clause.', self::ON_CONFLICT_WHERE_NOT_FOR_CONSTRAINT);
	}

}
