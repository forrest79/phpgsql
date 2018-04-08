<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db\Exceptions;

use Forrest79\PhPgSql\Db;

class QueryException extends Exception
{
	const QUERY_FAILED = 1;
	const ASYNC_QUERY_FAILED = 2;
	const CANT_PASS_PARAMS = 3;
	const NO_PARAM = 4;

	/** @var Db\Query */
	private $query;


	public function __construct(string $message = '', int $code = 0, ?Db\Query $query = NULL, ?\Throwable $previous = NULL)
	{
		parent::__construct($message, $code, $previous);
		$this->query = $query;
	}


	public function getQuery(): ?Db\Query
	{
		return $this->query;
	}


	public static function queryFailed(Db\Query $query, string $error)
	{
		return new self(sprintf('Query failed: \'%s\' with error: %s.', $query->getSql(), $error), self::QUERY_FAILED, $query);
	}


	public static function asyncQueryFailed(Db\Query $query, string $error)
	{

		return new self(sprintf('Async query failed? \'%s\' with error: %s.', $query->getSql(), $error), self::ASYNC_QUERY_FAILED, $query);
	}


	public static function cantPassParams()
	{
		return new self('Can\'t pass params when passing Query object.', self::CANT_PASS_PARAMS);
	}


	public static function noParam(int $index)
	{
		return new self(sprintf('There is no param for index %s.', $index), self::NO_PARAM);
	}

}
