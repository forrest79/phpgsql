<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db\Exceptions;

class RowException extends Exception
{
	public const NO_KEY = 1;
	public const NOT_STRING_KEY = 2;
	public const ISSET_IS_NOT_IMPLEMENTED = 3;


	public static function noParam(string $key): self
	{
		return new self(\sprintf('There is no key \'%s\'.', $key), self::NO_KEY);
	}


	public static function notStringKey(): self
	{
		return new self('Requested key must be string.', self::NOT_STRING_KEY);
	}


	public static function issetIsNotImplemented(string $key): self
	{
		return new self(
			\sprintf('__isset() is not implemented. Use hasKey(\'%s\') method or compare key value with NULL.', $key),
			self::ISSET_IS_NOT_IMPLEMENTED
		);
	}

}
