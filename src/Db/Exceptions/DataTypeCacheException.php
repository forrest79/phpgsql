<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db\Exceptions;

class DataTypeCacheException extends Exception
{
	public const CANT_LOAD_TYPES = 1;


	public static function cantLoadTypes(string $error): self
	{
		return new self(\sprintf('Can\'t load types from database: %s.', $error), self::CANT_LOAD_TYPES);
	}

}
