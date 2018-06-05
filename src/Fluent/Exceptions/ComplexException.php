<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Fluent\Exceptions;

class ComplexException extends Exception
{
	const NO_PARENT = 1;


	public static function noParent(): self
	{
		return new self('This is top complex, has no parent.', self::NO_PARENT);
	}

}
