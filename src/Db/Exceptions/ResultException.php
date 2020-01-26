<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db\Exceptions;

use Forrest79\PhPgSql\Db;

class ResultException extends Exception
{
	public const NO_COLUMN = 1;
	public const COLUMN_NAME_IS_ALREADY_IN_USE = 2;
	public const FETCH_ASSOC_PARSE_FAILED = 3;
	public const FETCH_PAIRS_FAILED = 4;
	public const NO_OTHER_ASYNC_RESULT = 5;
	public const ANOTHER_ASYNC_QUERY_IS_RUNNING = 6;


	public static function noColumn(string $key): self
	{
		return new self(\sprintf('There is no key \'%s\'.', $key), self::NO_COLUMN);
	}


	public static function columnNameIsAlreadyInUse(string $key): self
	{
		return new self(\sprintf('Key \'%s\' is already used in result.', $key), self::COLUMN_NAME_IS_ALREADY_IN_USE);
	}


	public static function fetchAssocParseFailed(string $assocDesc): self
	{
		return new self(\sprintf('Failed parsing associative descriptor \'%s\'.', $assocDesc), self::FETCH_ASSOC_PARSE_FAILED);
	}


	public static function fetchPairsBadColumns(): self
	{
		return new self('Either none or both columns must be specified.', self::FETCH_PAIRS_FAILED);
	}


	public static function noOtherAsyncResult(Db\Query $query): self
	{
		return new self(\sprintf('No other result for async query \'%s\'.', $query->getSql()), self::NO_OTHER_ASYNC_RESULT);
	}


	public static function anotherAsyncQueryIsRunning(Db\Query $resultQuery, string $connectionQuery): self
	{
		return new self(
			\sprintf(
				'Result async query \'%s\' is different from actual connection async query \'%s\', we can\'t no longer read result async query results.',
				$resultQuery->getSql(),
				$connectionQuery
			),
			self::ANOTHER_ASYNC_QUERY_IS_RUNNING
		);
	}

}
