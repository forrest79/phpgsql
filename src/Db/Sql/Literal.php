<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db\Sql;

use Forrest79\PhPgSql\Db;

class Literal implements Db\Sql
{
	private string $value;


	public function __construct(string $value)
	{
		$this->value = $value;
	}


	public function getSql(): string
	{
		return $this->value;
	}


	/**
	 * @return list<mixed>
	 */
	public function getParams(): array
	{
		return [];
	}


	public static function create(string $value): self
	{
		return new self($value);
	}

}
