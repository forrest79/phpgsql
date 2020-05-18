<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db\Exceptions;

use Forrest79\PhPgSql\Db;

class ResultException extends Exception
{
	public const NO_COLUMN = 1;
	public const COLUMN_NAME_IS_ALREADY_IN_USE = 2;
	public const FETCH_ASSOC_BAD_DESCRIPTOR = 3;
	public const FETCH_ASSOC_NO_COLUMN = 4;
	public const FETCH_ASSOC_ONLY_SCALAR_AS_KEY = 5;
	public const FETCH_PAIRS_FAILED = 6;
	public const NO_OTHER_ASYNC_RESULT = 7;
	public const ANOTHER_ASYNC_QUERY_IS_RUNNING = 8;
	public const NO_OID_IN_DATA_TYPE_CACHE = 9;


	public static function noColumn(string $column): self
	{
		return new self(\sprintf('There is no column \'%s\'.', $column), self::NO_COLUMN);
	}


	public static function columnNameIsAlreadyInUse(string $column): self
	{
		return new self(\sprintf('Column \'%s\' is already used in result.', $column), self::COLUMN_NAME_IS_ALREADY_IN_USE);
	}


	public static function fetchAssocBadDescriptor(string $assocDesc): self
	{
		return new self(\sprintf('Bad associative descriptor format \'%s\'.', $assocDesc), self::FETCH_ASSOC_BAD_DESCRIPTOR);
	}


	public static function fetchAssocNoColumn(string $column, string $assocDesc): self
	{
		return new self(\sprintf('No column (or bad operator) \'%s\' in associative descriptor \'%s\'.', $column, $assocDesc), self::FETCH_ASSOC_NO_COLUMN);
	}


	/**
	 * @param mixed $value
	 */
	public static function fetchAssocOnlyScalarAsKey(string $assocDesc, string $column, $value): self
	{
		return new self(\sprintf('You can use only scalar type as a key in associative descriptor \'%s\'. Column \'%s\' was parsed as \'%s\'.', $assocDesc, $column, \gettype($value)), self::FETCH_ASSOC_ONLY_SCALAR_AS_KEY);
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


	/**
	 * @param int|FALSE $oid
	 */
	public static function noOidInDataTypeCache($oid): self
	{
		return new self(\sprintf('There is no oid \'%s\' in data type cache. Try clear your data type cache.', $oid === FALSE ? 'FALSE' : $oid), self::NO_OID_IN_DATA_TYPE_CACHE);
	}

}
