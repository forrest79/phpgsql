<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db\Sql;

use Forrest79\PhPgSql\Db;

class Query implements Db\Sql
{
	private string $sql;

	/** @var list<mixed> */
	private array $params;

	private Db\Query|NULL $dbQuery = NULL;


	/**
	 * @param list<mixed> $params
	 */
	public function __construct(string $sql, array $params = [])
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


	/**
	 * Create SQL query for pg_query_params function.
	 */
	public function toDbQuery(): Db\Query
	{
		if ($this->dbQuery === NULL) {
			$this->dbQuery = Db\Query::from($this->sql, $this->params);
		}

		return $this->dbQuery;
	}


	public static function create(string $sql, mixed ...$params): self
	{
		\assert(\array_is_list($params));
		return new self($sql, $params);
	}


	/**
	 * @param list<mixed> $params
	 */
	public static function createArgs(string $sql, array $params): self
	{
		return new self($sql, $params);
	}

}
