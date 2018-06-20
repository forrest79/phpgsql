<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db\Exceptions;

use Forrest79\PhPgSql;

class RowException extends Exception
{
	const NO_KEY = 1;


	public static function noParam(string $key)
	{
		return new self(\sprintf('There is no key \'%s\'.', $key), self::NO_KEY);
	}

}
