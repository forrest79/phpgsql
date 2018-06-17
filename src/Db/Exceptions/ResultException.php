<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db\Exceptions;

class ResultException extends Exception
{
	const NO_COLUMN = 1;
	const NO_RESOURCE = 2;


	public static function noColumn(string $key): self
	{
		return new self(\sprintf('There is no key \'%s\'.', $key), self::NO_COLUMN);
	}


	public static function noResource(): self
	{
		return new self('No resource is available. Have you run on connection waitForAsyncQuery().', self::NO_RESOURCE);
	}

}
