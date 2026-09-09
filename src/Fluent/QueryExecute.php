<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Fluent;

use Forrest79\PhPgSql\Db;

class QueryExecute extends Query implements \Countable
{
	private Db\Connection $connection;

	private Db\Result|null $result = null;

	/** @var list<\Closure(Db\Row): void> */
	private array $rowFetchMutators = [];

	/** @var array<string, list<callable>> */
	private array $columnsFetchMutators = [];


	public function __construct(QueryBuilder $queryBuilder, Db\Connection $connection)
	{
		$this->connection = $connection;
		parent::__construct($queryBuilder);
	}


	/**
	 * @param \Closure(Db\Row): void $rowFetchMutator
	 */
	public function addRowFetchMutator(\Closure $rowFetchMutator): static
	{
		$this->rowFetchMutators[] = $rowFetchMutator;

		if ($this->result !== null) {
			$this->result->addRowFetchMutator($rowFetchMutator);
		}

		return $this;
	}


	/**
	 * @param non-empty-array<string, callable> $columnsFetchMutator
	 */
	public function addColumnsFetchMutator(array $columnsFetchMutator): static
	{
		foreach ($columnsFetchMutator as $column => $mutator) {
			$this->columnsFetchMutators[$column][] = $mutator;
		}

		if ($this->result !== null) {
			$this->result->addColumnsFetchMutator($columnsFetchMutator);
		}

		return $this;
	}


	/**
	 * @param \Closure(Db\Row): void $rowFetchMutator
	 * @deprecated use addRowFetchMutator() instead
	 */
	public function setRowFetchMutator(\Closure $rowFetchMutator): static
	{
		$this->rowFetchMutators = [$rowFetchMutator];

		if ($this->result !== null) {
			$this->result->setRowFetchMutator($rowFetchMutator);
		}

		return $this;
	}


	/**
	 * @param non-empty-array<string, callable> $columnsFetchMutator
	 * @deprecated use addColumnsFetchMutator() instead
	 */
	public function setColumnsFetchMutator(array $columnsFetchMutator): static
	{
		$this->columnsFetchMutators = [];

		foreach ($columnsFetchMutator as $column => $mutator) {
			$this->columnsFetchMutators[$column] = [$mutator];
		}

		if ($this->result !== null) {
			$this->result->setColumnsFetchMutator($columnsFetchMutator);
		}

		return $this;
	}


	/**
	 * @throws Exceptions\QueryException
	 */
	protected function resetQuery(): void
	{
		if ($this->result !== null) {
			throw Exceptions\QueryException::cantUpdateQueryAfterExecute();
		}

		parent::resetQuery();
	}


	/**
	 * @throws Db\Exceptions\ConnectionException
	 * @throws Db\Exceptions\QueryException
	 * @throws Exceptions\QueryBuilderException
	 * @throws Exceptions\QueryException
	 */
	public function execute(): Db\Result
	{
		if ($this->result === null) {
			$this->result = $this->connection->query($this);

			foreach ($this->rowFetchMutators as $rowFetchMutator) {
				$this->result->addRowFetchMutator($rowFetchMutator);
			}

			foreach ($this->columnsFetchMutators as $column => $mutators) {
				foreach ($mutators as $mutator) {
					$this->result->addColumnsFetchMutator([$column => $mutator]);
				}
			}
		}

		return $this->result;
	}


	/**
	 * @throws Db\Exceptions\ConnectionException
	 * @throws Db\Exceptions\QueryException
	 * @throws Exceptions\QueryBuilderException
	 * @throws Exceptions\QueryException
	 */
	public function reexecute(): Db\Result
	{
		$this->releaseResult();
		return $this->execute();
	}


	private function releaseResult(bool $freeResult = true): void
	{
		if ($freeResult && ($this->result !== null)) {
			$this->free();
		}

		$this->result = null;
	}


	/**
	 * @throws Exceptions\QueryException
	 */
	public function free(): bool
	{
		if ($this->result === null) {
			throw Exceptions\QueryException::youMustExecuteQueryBeforeThat();
		}

		return $this->result->free();
	}


	/**
	 * @throws Db\Exceptions\ConnectionException
	 * @throws Db\Exceptions\QueryException
	 * @throws Exceptions\QueryBuilderException
	 * @throws Exceptions\QueryException
	 */
	public function count(): int
	{
		/** @phpstan-var int<0, max> */
		return $this->execute()->getRowCount();
	}


	/**
	 * @throws Db\Exceptions\ConnectionException
	 * @throws Db\Exceptions\QueryException
	 * @throws Exceptions\QueryBuilderException
	 * @throws Exceptions\QueryException
	 */
	public function getAffectedRows(): int
	{
		return $this->execute()->getAffectedRows();
	}


	/**
	 * @throws Db\Exceptions\ConnectionException
	 * @throws Db\Exceptions\QueryException
	 * @throws Exceptions\QueryBuilderException
	 * @throws Exceptions\QueryException
	 */
	public function fetch(): Db\Row|null
	{
		return $this->execute()->fetch();
	}


	/**
	 * @return mixed value on success, null if no next record
	 * @throws Db\Exceptions\ConnectionException
	 * @throws Db\Exceptions\QueryException
	 * @throws Exceptions\QueryBuilderException
	 * @throws Exceptions\QueryException
	 */
	public function fetchSingle(): mixed
	{
		return $this->execute()->fetchSingle();
	}


	/**
	 * @return list<Db\Row>
	 * @throws Db\Exceptions\ConnectionException
	 * @throws Db\Exceptions\QueryException
	 * @throws Exceptions\QueryBuilderException
	 * @throws Exceptions\QueryException
	 */
	public function fetchAll(int|null $offset = null, int|null $limit = null): array
	{
		return $this->execute()->fetchAll($offset, $limit);
	}


	/**
	 * @return array<int|string, mixed>
	 * @throws Db\Exceptions\ConnectionException
	 * @throws Db\Exceptions\QueryException
	 * @throws Exceptions\QueryBuilderException
	 * @throws Exceptions\QueryException
	 */
	public function fetchAssoc(string $assoc): array
	{
		return $this->execute()->fetchAssoc($assoc);
	}


	/**
	 * @return array<int|string, mixed>
	 * @throws Db\Exceptions\ConnectionException
	 * @throws Db\Exceptions\QueryException
	 * @throws Exceptions\QueryBuilderException
	 * @throws Exceptions\QueryException
	 */
	public function fetchPairs(string|null $key = null, string|null $value = null): array
	{
		return $this->execute()->fetchPairs($key, $value);
	}


	/**
	 * @return Db\RowIterator<int, Db\Row>
	 * @throws Db\Exceptions\ConnectionException
	 * @throws Db\Exceptions\QueryException
	 * @throws Exceptions\QueryBuilderException
	 * @throws Exceptions\QueryException
	 */
	public function fetchIterator(): Db\RowIterator
	{
		/**	@phpstan-var Db\RowIterator<int, Db\Row> */
		return $this->execute()->fetchIterator();
	}


	/**
	 * @throws Db\Exceptions\ConnectionException
	 * @throws Db\Exceptions\QueryException
	 * @throws Exceptions\QueryBuilderException
	 * @throws Exceptions\QueryException
	 */
	public function asyncExecute(): Db\AsyncQuery
	{
		return $this->connection->asyncQuery($this);
	}


	public function __clone()
	{
		$this->releaseResult(false); // must be before parent clone - we need to allow resetQuery() first

		parent::__clone();
	}

}
