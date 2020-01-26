<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Fluent\Exceptions;

class ComplexException extends Exception
{
	public const NO_PARENT = 1;
	public const NO_QUERY = 2;
	public const COMPLEX_CANT_HAVE_PARAMS = 3;


	public static function noParent(): self
	{
		return new self('This complex has no parent assigned.', self::NO_PARENT);
	}


	public static function noQuery(): self
	{
		return new self('This complex has no query assigned.', self::NO_QUERY);
	}


	public static function complexCantHaveParams(): self
	{
		return new self('Complex can\'t be add with params.', self::COMPLEX_CANT_HAVE_PARAMS);
	}

}
