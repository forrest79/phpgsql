<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db;

class Query
{
	private string $sql;

	/** @var list<mixed> */
	private array $params;


	/**
	 * @param list<mixed> $params
	 */
	public function __construct(string $sql, array $params)
	{
		$this->sql = $sql;
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

}
