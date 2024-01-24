<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db\Sql;

use Forrest79\PhPgSql\Db;

class Expression implements Db\Sql
{
	private string $sql;

	/** @var list<mixed> */
	private array $params;


	/**
	 * @param list<mixed> $params
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
	 * @return list<mixed>
	 */
	public function getParams(): array
	{
		return $this->params;
	}


	public static function create(string $value, mixed ...$params): self
	{
		\assert(\array_is_list($params));
		return new self($value, $params);
	}


	/**
	 * @param list<mixed> $params
	 */
	public static function createArgs(string $value, array $params): self
	{
		return new self($value, $params);
	}

}
