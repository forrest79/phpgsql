<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Fluent\Exceptions;

class ComplexException extends Exception
{
	private const NO_PARENT = 1;
	private const NO_FLUENT = 2;
	private const COMPLEX_CANT_HAVE_PARAMS = 3;


	public static function noParent(): self
	{
		return new self('This complex has no parent assigned.', self::NO_PARENT);
	}


	public static function noFluent(): self
	{
		return new self('This complex has no fluent assigned.', self::NO_FLUENT);
	}


	public static function complexCantHaveParams(): self
	{
		return new self('Complex can\'t be add with params.', self::COMPLEX_CANT_HAVE_PARAMS);
	}

}
