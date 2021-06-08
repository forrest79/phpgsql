<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db\Sql;

use Forrest79\PhPgSql\Db;

class Expression implements Db\Sql
{
	/** @var string */
	private $sql;

	/** @var array<mixed> */
	private $params;


	/**
	 * @param string $value
	 * @param array<mixed> $params
	 */
	public function __construct(string $value, array $params)
	{
		$this->sql = $value;
		$this->params = $params;
	}


	public function getSql(): string
	{
		return $this->sql;
	}


	/**
	 * @return array<mixed>
	 */
	public function getParams(): array
	{
		return $this->params;
	}


	/**
	 * @param mixed ...$params
	 * @return self
	 */
	public static function create(string $value, ...$params): self
	{
		return new self($value, $params);
	}


	/**
	 * @param array<mixed> $params
	 */
	public static function createArgs(string $value, array $params): self
	{
		return new self($value, $params);
	}

}
