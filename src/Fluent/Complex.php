<?php

namespace Forrest79\PhPgSql\Fluent;

class Complex implements \ArrayAccess
{
	const TYPE_AND = 'and';
	const TYPE_OR = 'or';

	/** @var Complex */
	private $parent;

	/** @var Fluent */
	private $fluent;

	/** @var string */
	private $type;

	/** @var array */
	private $conditions;


	private function __construct(?Complex $parent, ?Fluent $fluent, string $type, array $conditions)
	{
		$this->parent = $parent;
		$this->fluent = $fluent;
		$this->type = $type;
		$this->conditions = $conditions;
	}


	public function add($condition, ...$params): self
	{
		$this->conditions[] = [$condition, $params];
		return $this;
	}


	public function complexAnd(array $conditions = []): Complex
	{
		return self::createAnd($conditions, $this->fluent, $this);
	}


	public function complexOr(array $conditions = []): Complex
	{
		return self::createOr($conditions, $this->fluent, $this);
	}


	public function getType(): string
	{
		return $this->type;
	}


	public function getConditions(): array
	{
		return $this->conditions;
	}


	/**
	 * @throws Exceptions\ComplexException
	 */
	public function parent(): Complex
	{
		if ($this->parent === NULL) {
			throw Exceptions\ComplexException::noParent();
		}
		return $this->parent;
	}


	public function fluent(): Fluent
	{
		return $this->fluent;
	}


	public static function createAnd(array $conditions = [], ?Fluent $fluent = NULL, ?Complex $parent = NULL): self
	{
		return new self($parent, $fluent, self::TYPE_AND, $conditions);
	}


	public static function createOr(array $conditions = [], ?Fluent $fluent = NULL, ?Complex $parent = NULL): self
	{
		return new self($parent, $fluent, self::TYPE_OR, $conditions);
	}


	public function offsetExists($offset)
	{
		return isset($this->conditions[$offset]);
	}


	public function offsetGet($offset)
	{
		return $this->conditions[$offset] ?? NULL;
	}


	public function offsetSet($offset, $value)
	{
		if (!is_array($value)) {
			$value = [$value];
		} else {
			$cnt = count($value);
			if ($cnt === 2) {
				[$condition, $params] = $value;
				$value = [$condition, (array) $params];
			} else if ($cnt > 2) {
				$condition = array_shift($value);
				$value = [$condition, $value];
			}
		}

		if ($offset === NULL) {
			$this->conditions[] = $value;
		} else {
			$this->conditions[$offset] = $value;
		}
	}


	public function offsetUnset($offset)
	{
		unset($this->conditions[$offset]);
	}

}
