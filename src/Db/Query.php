<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db;

class Query implements Queryable
{
	/** @var string */
	private $sql;

	/** @var array */
	private $params;


	public function __construct(string $sql, array $params = [])
	{
		$this->sql = $sql;
		$this->params = $params;
	}


	public function getSql(): string
	{
		return $this->sql;
	}


	public function getParams(): array
	{
		return $this->params;
	}


	/**
	 * @param string $sql
	 * @param mixed ...$params
	 * @return self
	 */
	public static function create(string $sql, ...$params): self
	{
		return new self($sql, $params);
	}


	public static function createArgs(string $sql, array $params): self
	{
		return new self($sql, $params);
	}

}
