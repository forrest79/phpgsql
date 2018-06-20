<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db;

class Literal
{
	/** @var string */
	private $value;


	public function __construct(string $value)
	{
		$this->value = $value;
	}


	public function __toString(): string
	{
		return $this->value;
	}


	public static function create(string $value): self
	{
		return new self($value);
	}

}
