<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db\Exceptions;

use Forrest79\PhPgSql\Db;

class QueryException extends Exception
{
	public const QUERY_FAILED = 1;
	public const ASYNC_QUERY_FAILED = 2;
	public const PREPARED_STATEMENT_QUERY_FAILED = 3;
	public const ASYNC_PREPARED_STATEMENT_QUERY_FAILED = 4;
	public const CANT_PASS_PARAMS = 5;
	public const NO_PARAM = 6;

	/** @var Db\Query|NULL */
	private $query;


	public function __construct(
		string $message = '',
		int $code = 0,
		?Db\Query $query = NULL,
		?\Throwable $previous = NULL
	)
	{
		parent::__construct($message, $code, $previous);
		$this->query = $query;
	}


	public function getQuery(): ?Db\Query
	{
		return $this->query;
	}


	public static function queryFailed(Db\Query $query, string $error): self
	{
		return new self(\sprintf('Query: \'%s\' failed with an error: %s.', $query->getSql(), $error), self::QUERY_FAILED, $query);
	}


	public static function asyncQueryFailed(Db\Query $query, string $error): self
	{
		return new self(\sprintf('Async query \'%s\' failed with an error: %s.', $query->getSql(), $error), self::ASYNC_QUERY_FAILED, $query);
	}


	public static function preparedStatementQueryFailed(
		string $preparedStatementName,
		Db\Query $query,
		string $error
	): self
	{
		return new self(\sprintf('Prepared statement: \'%s\', query: \'%s\' failed with an error: %s.', $preparedStatementName, $query->getSql(), $error), self::PREPARED_STATEMENT_QUERY_FAILED, $query);
	}


	public static function asyncPreparedStatementQueryFailed(
		string $preparedStatementName,
		Db\Query $query,
		string $error
	): self
	{
		return new self(\sprintf('Prepared statement: \'%s\', async query \'%s\' failed with error: %s.', $preparedStatementName, $query->getSql(), $error), self::ASYNC_PREPARED_STATEMENT_QUERY_FAILED, $query);
	}


	public static function cantPassParams(): self
	{
		return new self('Can\'t pass params when passing Query object.', self::CANT_PASS_PARAMS);
	}


	public static function noParam(int $index): self
	{
		return new self(\sprintf('There is no param for index %s. Did you escape all \'?\' characters, which you want to use as \'?\' and not as parameter?', $index), self::NO_PARAM);
	}

}
