<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db;

class Query
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

}
