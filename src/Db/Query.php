<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db;

final class Query
{
	/** @var string */
	private $sql;

	/** @var array<mixed> */
	private $params;


	/**
	 * @param array<mixed> $params
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
	 * @return array<mixed>
	 */
	public function getParams(): array
	{
		return $this->params;
	}

}
