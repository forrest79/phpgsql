<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Fluent;

use Forrest79\PhPgSql\Db;

/**
 * @implements \ArrayAccess<int, string|list<mixed>|self|Db\Sql>
 */
class Complex implements \ArrayAccess
{
	public const TYPE_AND = 'AND';
	public const TYPE_OR = 'OR';

	private string $type;

	/** @var list<self|list<mixed>> */
	private array $conditions;

	private self|NULL $parent;

	private Query|NULL $query;


	/**
	 * @param list<self|string|list<mixed>|Db\Sql> $conditions
	 */
	private function __construct(string $type, array $conditions, self|NULL $parent = NULL, Query|NULL $query = NULL)
	{
		$this->type = $type;
		$this->conditions = $this->normalizeConditions($conditions);
		$this->parent = $parent;
		$this->query = $query;
	}


	/**
	 * @throws Exceptions\ComplexException
	 */
	public function add(self|string|Db\Sql $condition, mixed ...$params): self
	{
		if ((($condition instanceof self) || ($condition instanceof Db\Sql)) && $params !== []) {
			throw Exceptions\ComplexException::onlyStringConditionCanHaveParams();
		}

		if ($condition instanceof self) {
			$this->conditions[] = $condition;
		} else if ($condition instanceof Db\Sql) {
			$this->conditions[] = \array_merge([$condition->getSql()], $condition->getParams());
		} else {
			\assert(\array_is_list($params));

			\array_unshift($params, $condition);
			$this->conditions[] = $params;
		}

		return $this;
	}


	/**
	 * @param list<self|string|list<mixed>|Db\Sql> $conditions
	 */
	public function addComplexAnd(array $conditions = []): self
	{
		$complexAnd = self::createAnd($conditions, $this, $this->query);

		$this->add($complexAnd);

		return $complexAnd;
	}


	/**
	 * @param list<self|string|list<mixed>|Db\Sql> $conditions
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
	 * @return list<self|list<mixed>>
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
	 * @param list<self|string|list<mixed>|Db\Sql> $conditions
	 */
	public static function createAnd(array $conditions = [], self|NULL $parent = NULL, Query|NULL $query = NULL): self
	{
		return new self(self::TYPE_AND, $conditions, $parent, $query);
	}


	/**
	 * @param list<self|string|list<mixed>|Db\Sql> $conditions
	 */
	public static function createOr(array $conditions = [], self|NULL $parent = NULL, Query|NULL $query = NULL): self
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
	 * @return string|list<mixed>|self|Db\Sql|NULL
	 */
	#[\ReturnTypeWillChange]
	public function offsetGet(mixed $offset): string|array|self|Db\Sql|NULL
	{
		return $this->conditions[$offset] ?? NULL;
	}


	/**
	 * @param int|NULL $offset
	 * @param string|list<mixed>|self|Db\Sql $value
	 */
	public function offsetSet(mixed $offset, mixed $value): void
	{
		if (!\is_array($value)) {
			$value = [$value];
		}

		if ($offset === NULL) {
			$this->conditions[] = $value;
		} else {
			if (!isset($this->conditions[$offset])) {
				throw new \RuntimeException('Can\'t set non-existing offset.');
			}

			$this->conditions[$offset] = $value;
		}
	}


	/**
	 * @param int $offset
	 */
	public function offsetUnset(mixed $offset): void
	{
		$conditions = $this->conditions;
		unset($conditions[$offset]);
		$this->conditions = array_values($conditions);
	}


	/**
	 * @param list<self|string|list<mixed>|Db\Sql> $conditions
	 * @return list<self|list<mixed>>
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

		/** @phpstan-var list<self|list<mixed>> */
		return $conditions;
	}

}
