<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Fluent\Exceptions;

class FluentException extends Exception
{
	const ONLY_ONE_MAIN_TABLE = 1;
	const TABLE_ALIAS_ALREADY_EXISTS = 2;
	const NON_EXISTING_PARAM_TO_RESET = 3;
	const ALIAS_MUST_BE_SCALAR = 4;
	const QUERYABLE_MUST_HAVE_ALIAS = 5;
	const COLUMN_MUST_BE_SCALAR_OR_QUERYABLE = 6;
	const CANT_UPDATE_FLUENT_AFTER_EXECUTE = 7;
	const YOU_NEED_CONNECTION_FOR_THIS_ACTION = 8;


	public static function onlyOneMainTable(): self
	{
		return new self('There can be only one main table.', self::ONLY_ONE_MAIN_TABLE);
	}


	public static function tableAliasAlreadyExists(string $alias): self
	{
		return new self(\sprintf('This table alias "%" alread exists.', $alias), self::TABLE_ALIAS_ALREADY_EXISTS);
	}


	public static function nonExistingParamToReset(string $param, array $params): self
	{
		return new self(\sprintf('Non existing parameter "%s" to reset. You can reset only these parameters "%s".', implode(', ', $params)), self::TABLE_ALIAS_ALREADY_EXISTS);
	}


	public static function aliasMustBeScalar(): self
	{
		return new self('Alias must be scalar.', self::ALIAS_MUST_BE_SCALAR);
	}


	public static function queryableMustHaveAlias(): self
	{
		return new self('Queryable must have alias.', self::QUERYABLE_MUST_HAVE_ALIAS);
	}


	public static function columnMustBeScalarOrQueryable(): self
	{
		return new self('Column must be scalar or queryable.', self::COLUMN_MUST_BE_SCALAR_OR_QUERYABLE);
	}


	public static function cantUpdateFluentAfterExecute(): self
	{
		return new self('Can\'t update fluent after execute.', self::CANT_UPDATE_FLUENT_AFTER_EXECUTE);
	}


	public static function youNeedConnectionForThisAction(): self
	{
		return new self('You need connection for this action. Did you create this Fluent from Fluent\Connection?', self::YOU_NEED_CONNECTION_FOR_THIS_ACTION);
	}

}
