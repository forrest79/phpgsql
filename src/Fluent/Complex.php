<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Fluent;

use Forrest79\PhPgSql\Db;

/**
 * @implements \ArrayAccess<int, string|array<mixed>|self|Db\Sql>
 */
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

	/** @var array<self|array<mixed>> */
	private $conditions;


	/**
	 * @param array<self|string|array<mixed>|Db\Sql> $conditions
	 */
	private function __construct(string $type, array $conditions, ?self $parent = NULL, ?Query $query = NULL)
	{
		$this->type = $type;
		$this->conditions = $this->normalizeConditions($conditions);
		$this->parent = $parent;
		$this->query = $query;
	}


	/**
	 * @param self|string|Db\Sql $condition
	 * @param mixed ...$params
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
		} else if (\is_string($condition)) {
			\array_unshift($params, $condition);
			$this->conditions[] = $params;
		} else {
			throw Exceptions\ComplexException::unsupportedConditionType($condition);
		}
		return $this;
	}


	/**
	 * @param array<self|string|array<mixed>|Db\Sql> $conditions
	 */
	public function addComplexAnd(array $conditions = []): self
	{
		$complexAnd = self::createAnd($conditions, $this, $this->query);
		$this->add($complexAnd);
		return $complexAnd;
	}


	/**
	 * @param array<self|string|array<mixed>|Db\Sql> $conditions
	 */
	public function addComplexOr(array $conditions = []): self
	{
		$complexOr = self::createOr($conditions, $this, $this->query);
		$this->add($complexOr);
		return $complexOr;
	}


	public function getType(): string
	{
		return $this->type;
	}


	/**
	 * @return array<self|array<mixed>>
	 */
	public function getConditions(): array
	{
		return $this->conditions;
	}


	/**
	 * @throws Exceptions\ComplexException
	 */
	public function parent(): self
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


	/**
	 * @param array<self|string|array<mixed>|Db\Sql> $conditions
	 */
	public static function createAnd(array $conditions = [], ?self $parent = NULL, ?Query $query = NULL): self
	{
		return new self(self::TYPE_AND, $conditions, $parent, $query);
	}


	/**
	 * @param array<self|string|array<mixed>|Db\Sql> $conditions
	 */
	public static function createOr(array $conditions = [], ?self $parent = NULL, ?Query $query = NULL): self
	{
		return new self(self::TYPE_OR, $conditions, $parent, $query);
	}


	/**
	 * @param int $offset
	 */
	public function offsetExists($offset): bool
	{
		return isset($this->conditions[$offset]);
	}


	/**
	 * @param int $offset
	 * @return string|array<mixed>|self|Db\Sql|NULL
	 */
	#[\ReturnTypeWillChange]
	public function offsetGet($offset)
	{
		return $this->conditions[$offset] ?? NULL;
	}


	/**
	 * @param int|NULL $offset
	 * @param string|array<mixed>|self|Db\Sql $value
	 */
	public function offsetSet($offset, $value): void
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
	 * @param int $offset
	 */
	public function offsetUnset($offset): void
	{
		unset($this->conditions[$offset]);
	}


	/**
	 * @param array<self|string|array<mixed>|Db\Sql> $conditions
	 * @return array<self|array<mixed>>
	 */
	private function normalizeConditions(array $conditions): array
	{
		foreach ($conditions as $i => $value) {
			if ($value instanceof Db\Sql) {
				$conditions[$i] = \array_merge([$value->getSql()], $value->getParams());
			} else if (!\is_array($value) && !($value instanceof self)) {
				$conditions[$i] = [$value];
			}
		}

		/** @var array<self|array<mixed>> $conditions */
		return $conditions;
	}

}
