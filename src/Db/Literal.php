<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db;

class Literal
{
	/** @var string */
	private $value;

	/** @var array */
	private $params;


	/**
	 * @param string $value
	 * @param mixed ...$params
	 */
	public function __construct(string $value, ...$params)
	{
		$this->value = $value;
		$this->params = $params;
	}


	public function __toString(): string
	{
		return $this->value;
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
		return new self($value, ...$params);
	}


	public static function createArgs(string $value, array $params): self
	{
		return new self($value, ...$params);
	}

}
