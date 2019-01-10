<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db\Exceptions;

class DataTypeCacheException extends Exception
{
	public const CANT_LOAD_TYPES = 1;


	public static function cantLoadTypes(): self
	{
		return new self('Can\'t load types from database.', self::CANT_LOAD_TYPES);
	}

}
