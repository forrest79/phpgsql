<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Fluent;

use Forrest79\PhPgSql\Db;

class Complex implements \ArrayAccess
{
	public const TYPE_AND = 'AND';
	public const TYPE_OR = 'OR';

	/** @var Complex|NULL */
	private $parent;

	/** @var Query|NULL */
	private $query;

	/** @var string */
	private $type;

	/** @var array */
	private $conditions;


	private function __construct(string $type, array $conditions, ?Complex $parent = NULL, ?Query $query = NULL)
	{
		$this->type = $type;
		$this->conditions = $this->normalizeConditions($conditions);
		$this->parent = $parent;
		$this->query = $query;
	}


	/**
	 * @param string|self|Db\Sql $condition
	 * @param mixed ...$params
	 * @return self
	 * @throws Exceptions\ComplexException
	 */
	public function add($condition, ...$params): self
	{
		if ((($condition instanceof self) || ($condition instanceof Db\Sql)) && $params !== []) {
			throw Exceptions\ComplexException::onlyStringConditionCanHaveParams();
		}
		if ($condition instanceof self) {
			$this->conditions[] = $condition;
		} else if ($condition instanceof Db\Sql) {
			$this->conditions[] = \array_merge([$condition->getSql()], $condition->getParams());
		} else {
			\array_unshift($params, $condition);
			$this->conditions[] = $params;
		}
		return $this;
	}


	public function addComplexAnd(array $conditions = []): Complex
	{
		$complexAnd = self::createAnd($conditions, $this, $this->query);
		$this->add($complexAnd);
		return $complexAnd;
	}


	public function addComplexOr(array $conditions = []): Complex
	{
		$complexOr = self::createOr($conditions, $this, $this->query);
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
	 * @return Query|QueryExecute
	 * @throws Exceptions\ComplexException
	 */
	public function query(): Query
	{
		if ($this->query === NULL) {
			throw Exceptions\ComplexException::noQuery();
		}
		return $this->query;
	}


	public static function createAnd(array $conditions = [], ?Complex $parent = NULL, ?Query $query = NULL): self
	{
		return new self(self::TYPE_AND, $conditions, $parent, $query);
	}


	public static function createOr(array $conditions = [], ?Complex $parent = NULL, ?Query $query = NULL): self
	{
		return new self(self::TYPE_OR, $conditions, $parent, $query);
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
		foreach ($conditions as $i => $value) {
			if ($value instanceof Db\Sql) {
				$conditions[$i] = \array_merge([$value->getSql()], $value->getParams());
			} else if (!\is_array($value) && !($value instanceof self)) {
				$conditions[$i] = [$value];
			}
		}
		return $conditions;
	}

}
