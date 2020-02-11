<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Fluent\Exceptions;

class QueryException extends Exception
{
	public const ONLY_ONE_MAIN_TABLE = 1;
	public const TABLE_ALIAS_ALREADY_EXISTS = 2;
	public const NON_EXISTING_PARAM_TO_RESET = 3;
	public const QUERYABLE_MUST_HAVE_ALIAS = 4;
	public const PARAM_MUST_BE_SCALAR_OR_EXPRESSION = 5;
	public const CANT_UPDATE_QUERY_AFTER_EXECUTE = 6;
	public const YOU_MUST_EXECUTE_QUERY_BEFORE_THAT = 7;


	public static function onlyOneMainTable(): self
	{
		return new self('There can be only one main table.', self::ONLY_ONE_MAIN_TABLE);
	}


	public static function tableAliasAlreadyExists(string $alias): self
	{
		return new self(\sprintf('This table alias "%s" already exists.', $alias), self::TABLE_ALIAS_ALREADY_EXISTS);
	}


	public static function nonExistingParamToReset(string $param, array $params): self
	{
		return new self(\sprintf('Non existing parameter "%s" to reset. You can reset only these parameters "%s".', $param, \implode(', ', $params)), self::NON_EXISTING_PARAM_TO_RESET);
	}


	public static function queryableMustHaveAlias(): self
	{
		return new self('Queryable must have alias.', self::QUERYABLE_MUST_HAVE_ALIAS);
	}


	public static function columnMustBeScalarOrExpression(): self
	{
		return new self('Column must be scalar, Fluent\Query or Db\Sql.', self::PARAM_MUST_BE_SCALAR_OR_EXPRESSION);
	}


	public static function cantUpdateQueryAfterExecute(): self
	{
		return new self('Can\'t update query after execute.', self::CANT_UPDATE_QUERY_AFTER_EXECUTE);
	}


	public static function youMustExecuteQueryBeforeThat(): self
	{
		return new self('You must execute query before that', self::YOU_MUST_EXECUTE_QUERY_BEFORE_THAT);
	}

}
