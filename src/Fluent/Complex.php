<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Fluent;

class Complex implements \ArrayAccess
{
	public const TYPE_AND = 'AND';
	public const TYPE_OR = 'OR';

	/** @var Complex|NULL */
	private $parent;

	/** @var Fluent|NULL */
	private $fluent;

	/** @var string */
	private $type;

	/** @var array */
	private $conditions;


	private function __construct(string $type, array $conditions, ?Complex $parent = NULL, ?Fluent $fluent = NULL)
	{
		$this->type = $type;
		$this->conditions = $this->normalizeConditions($conditions);
		$this->parent = $parent;
		$this->fluent = $fluent;
	}


	/**
	 * @param string|self $condition
	 * @param mixed ...$params
	 * @return self
	 * @throws Exceptions\ComplexException
	 */
	public function add($condition, ...$params): self
	{
		if (($condition instanceof self) && $params !== []) {
			throw Exceptions\ComplexException::complexCantHaveParams();
		}
		if ($condition instanceof self) {
			$this->conditions[] = $condition;
		} else {
			\array_unshift($params, $condition);
			$this->conditions[] = $params;
		}
		return $this;
	}


	public function addComplexAnd(array $conditions = []): Complex
	{
		$complexAnd = self::createAnd($conditions, $this, $this->fluent);
		$this->add($complexAnd);
		return $complexAnd;
	}


	public function addComplexOr(array $conditions = []): Complex
	{
		$complexOr = self::createOr($conditions, $this, $this->fluent);
		$this->add($complexOr);
		return $complexOr;
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


	/**
	 * @throws Exceptions\ComplexException
	 * @return Fluent|FluentExecute
	 */
	public function fluent(): Fluent
	{
		if ($this->fluent === NULL) {
			throw Exceptions\ComplexException::noFluent();
		}
		return $this->fluent;
	}


	public static function createAnd(array $conditions = [], ?Complex $parent = NULL, ?Fluent $fluent = NULL): self
	{
		return new self(self::TYPE_AND, $conditions, $parent, $fluent);
	}


	public static function createOr(array $conditions = [], ?Complex $parent = NULL, ?Fluent $fluent = NULL): self
	{
		return new self(self::TYPE_OR, $conditions, $parent, $fluent);
	}


	/**
	 * @param mixed $offset
	 * @return bool
	 */
	public function offsetExists($offset)
	{
		return isset($this->conditions[$offset]);
	}


	/**
	 * @param mixed $offset
	 * @return mixed|NULL
	 */
	public function offsetGet($offset)
	{
		return $this->conditions[$offset] ?? NULL;
	}


	/**
	 * @param mixed $offset
	 * @param mixed $value
	 * @return void
	 */
	public function offsetSet($offset, $value)
	{
		if (!\is_array($value)) {
			$value = [$value];
		}

		if ($offset === NULL) {
			$this->conditions[] = $value;
		} else {
			$this->conditions[$offset] = $value;
		}
	}


	/**
	 * @param mixed $offset
	 * @return void
	 */
	public function offsetUnset($offset)
	{
		unset($this->conditions[$offset]);
	}


	private function normalizeConditions(array $conditions): array
	{
		\array_walk($conditions, static function (&$value): void {
			if (!\is_array($value) && !($value instanceof self)) {
				$value = [$value];
			}
		});
		return $conditions;
	}

}
