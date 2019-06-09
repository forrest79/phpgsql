<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db\Exceptions;

class ResultException extends Exception
{
	public const NO_RESOURCE = 1;
	public const NO_COLUMN = 2;
	public const FETCH_ASSOC_PARSE_FAILED = 3;
	public const FETCH_PAIRS_FAILED = 4;


	public static function noResource(): self
	{
		return new self('No resource is available. Have you run on connection waitForAsyncQuery().', self::NO_RESOURCE);
	}


	public static function noColumn(string $key): self
	{
		return new self(\sprintf('There is no key \'%s\'.', $key), self::NO_COLUMN);
	}


	public static function fetchAssocParseFailed(string $assocDesc): self
	{
		return new self(\sprintf('Failed parsing associative descriptor \'%s\'.', $assocDesc), self::FETCH_ASSOC_PARSE_FAILED);
	}


	public static function fetchPairsBadColumns(): self
	{
		return new self('Either none or both columns must be specified.', self::FETCH_PAIRS_FAILED);
	}

}
