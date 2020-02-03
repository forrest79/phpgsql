<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db\Sql;

use Forrest79\PhPgSql\Db;

class Expression implements Db\Sql
{
	/** @var string */
	private $sql;

	/** @var array */
	private $params;


	/**
	 * @param string $value
	 * @param mixed[] $params
	 */
	public function __construct(string $value, array $params)
	{
		$this->sql = $value;
		$this->params = $params;
	}


	function getSql(): string
	{
		return $this->sql;
	}


	public function getParams(): array
	{
		return $this->params;
	}


	/**
	 * @param string $value
	 * @param mixed ...$params
	 * @return self
	 */
	public static function create(string $value, ...$params): self
	{
		return new self($value, $params);
	}


	public static function createArgs(string $value, array $params): self
	{
		return new self($value, $params);
	}

}
