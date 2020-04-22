<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db\Exceptions;

use Forrest79\PhPgSql\Db;

class QueryException extends Exception
{
	public const QUERY_FAILED = 1;
	public const ASYNC_QUERY_FAILED = 2;
	public const CANT_PASS_PARAMS = 3;
	public const NO_PARAM = 4;

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
		return new self(\sprintf('Query failed: \'%s\' with error: %s.', $query->getSql(), $error), self::QUERY_FAILED, $query);
	}


	public static function asyncQueryFailed(Db\Query $query, string $sqlState, string $error): self
	{
		return new self(\sprintf('Async query \'%s\' failed with state \'%s\' and error: %s.', $query->getSql(), $sqlState, $error), self::ASYNC_QUERY_FAILED, $query);
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
