<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Fluent\Exceptions;

class FluentException extends Exception
{
	public const ONLY_ONE_MAIN_TABLE = 1;
	public const TABLE_ALIAS_ALREADY_EXISTS = 2;
	public const NON_EXISTING_PARAM_TO_RESET = 3;
	public const QUERYABLE_MUST_HAVE_ALIAS = 4;
	public const PARAM_MUST_BE_SCALAR_OR_QUERYABLE = 5;
	public const CANT_UPDATE_FLUENT_AFTER_EXECUTE = 6;
	public const YOU_MUST_EXECUTE_FLUENT_BEFORE_THAT = 7;
	public const BAD_PARAM = 7;


	public static function onlyOneMainTable(): self
	{
		return new self('There can be only one main table.', self::ONLY_ONE_MAIN_TABLE);
	}


	public static function tableAliasAlreadyExists(string $alias): self
	{
		return new self(\sprintf('This table alias "%s" alread exists.', $alias), self::TABLE_ALIAS_ALREADY_EXISTS);
	}


	public static function nonExistingParamToReset(string $param, array $params): self
	{
		return new self(\sprintf('Non existing parameter "%s" to reset. You can reset only these parameters "%s".', $param, \implode(', ', $params)), self::NON_EXISTING_PARAM_TO_RESET);
	}


	public static function queryableMustHaveAlias(): self
	{
		return new self('Queryable must have alias.', self::QUERYABLE_MUST_HAVE_ALIAS);
	}


	public static function columnMustBeScalarOrQueryable(): self
	{
		return new self('Column must be scalar or queryable.', self::PARAM_MUST_BE_SCALAR_OR_QUERYABLE);
	}


	public static function cantUpdateFluentAfterExecute(): self
	{
		return new self('Can\'t update fluent after execute.', self::CANT_UPDATE_FLUENT_AFTER_EXECUTE);
	}


	public static function youMustExecuteFluentBeforeThat(): self
	{
		return new self('You must execute fluent before that', self::YOU_MUST_EXECUTE_FLUENT_BEFORE_THAT);
	}


	public static function badParam(string $param, string $value, array $validValues): self
	{
		return new self(\sprintf('Bad param \'%s\' with value \'%s\'. Valid values are \'%s\'.', $param, $value, \implode('\', \'', $validValues)), self::YOU_MUST_EXECUTE_FLUENT_BEFORE_THAT);
	}

}
