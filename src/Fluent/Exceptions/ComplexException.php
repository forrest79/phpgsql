<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Fluent\Exceptions;

class ComplexException extends Exception
{
	public const NO_PARENT = 1;
	public const NO_QUERY = 2;
	public const UNSUPPORTED_CONDITION_TYPE = 3;
	public const ONLY_STRING_CONDITION_CAN_HAVE_PARAMS = 4;


	public static function noParent(): self
	{
		return new self('This complex has no parent assigned.', self::NO_PARENT);
	}


	public static function noQuery(): self
	{
		return new self('This complex has no query assigned.', self::NO_QUERY);
	}


	/**
	 * @param mixed $condition
	 * @return static
	 */
	public static function unsupportedConditionType($condition): self
	{
		$type = \gettype($condition);
		if (!\is_scalar($condition) && !\is_array($condition)) {
			$type = \get_class($condition);
		}
		return new self(
			\sprintf('Only string, Fluent\Complex or Db\Sql can be used in condition. Type \'%s\' was given.', $type),
			self::UNSUPPORTED_CONDITION_TYPE
		);
	}


	public static function onlyStringConditionCanHaveParams(): self
	{
		return new self('Only string condition can be add with params.', self::ONLY_STRING_CONDITION_CAN_HAVE_PARAMS);
	}

}
