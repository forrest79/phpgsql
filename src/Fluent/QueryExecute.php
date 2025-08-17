<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Fluent;

use Forrest79\PhPgSql\Db;

class QueryExecute extends Query implements \Countable
{
	private Db\Connection $connection;

	private Db\Result|null $result = null;

	/** @template T of Db\Row @var \Closure(T): void|null */
	private \Closure|null $rowFetchMutator = null;

	/** @var array<string, callable> */
	private array $columnsFetchMutator = [];


	public function __construct(QueryBuilder $queryBuilder, Db\Connection $connection)
	{
		$this->connection = $connection;
		parent::__construct($queryBuilder);
	}


	/**
	 * @template T of Db\Row
	 * @param \Closure(T): void $rowFetchMutator
	 */
	public function setRowFetchMutator(\Closure $rowFetchMutator): static
	{
		$this->rowFetchMutator = $rowFetchMutator;

		if ($this->result !== null) {
			$this->result->setRowFetchMutator($rowFetchMutator);
		}

		return $this;
	}


	/**
	 * @param non-empty-array<string, callable> $columnsFetchMutator
	 */
	public function setColumnsFetchMutator(array $columnsFetchMutator): static
	{
		$this->columnsFetchMutator = $columnsFetchMutator;

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
	 * @throws Exceptions\QueryException
	 * @throws Exceptions\QueryBuilderException
	 */
	public function execute(): Db\Result
	{
		if ($this->result === null) {
			$this->result = $this->connection->query($this);

			if ($this->rowFetchMutator !== null) {
				$this->result->setRowFetchMutator($this->rowFetchMutator);
			}

			if ($this->columnsFetchMutator !== []) {
				$this->result->setColumnsFetchMutator($this->columnsFetchMutator);
			}
		}

		return $this->result;
	}


	/**
	 * @throws Db\Exceptions\ConnectionException
	 * @throws Db\Exceptions\QueryException
	 * @throws Exceptions\QueryException
	 * @throws Exceptions\QueryBuilderException
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
	 * @throws Exceptions\QueryException
	 * @throws Exceptions\QueryBuilderException
	 */
	public function count(): int
	{
		/** @phpstan-var int<0, max> */
		return $this->execute()->getRowCount();
	}


	/**
	 * @throws Db\Exceptions\ConnectionException
	 * @throws Db\Exceptions\QueryException
	 * @throws Exceptions\QueryException
	 * @throws Exceptions\QueryBuilderException
	 */
	public function getAffectedRows(): int
	{
		return $this->execute()->getAffectedRows();
	}


	/**
	 * @throws Db\Exceptions\ConnectionException
	 * @throws Db\Exceptions\QueryException
	 * @throws Exceptions\QueryException
	 * @throws Exceptions\QueryBuilderException
	 */
	public function fetch(): Db\Row|null
	{
		return $this->execute()->fetch();
	}


	/**
	 * @return mixed value on success, null if no next record
	 * @throws Db\Exceptions\ConnectionException
	 * @throws Db\Exceptions\QueryException
	 * @throws Exceptions\QueryException
	 * @throws Exceptions\QueryBuilderException
	 */
	public function fetchSingle(): mixed
	{
		return $this->execute()->fetchSingle();
	}


	/**
	 * @return list<Db\Row>
	 * @throws Db\Exceptions\ConnectionException
	 * @throws Db\Exceptions\QueryException
	 * @throws Exceptions\QueryException
	 * @throws Exceptions\QueryBuilderException
	 */
	public function fetchAll(int|null $offset = null, int|null $limit = null): array
	{
		return $this->execute()->fetchAll($offset, $limit);
	}


	/**
	 * @return array<int|string, mixed>
	 * @throws Db\Exceptions\ConnectionException
	 * @throws Db\Exceptions\QueryException
	 * @throws Exceptions\QueryException
	 * @throws Exceptions\QueryBuilderException
	 */
	public function fetchAssoc(string $assoc): array
	{
		return $this->execute()->fetchAssoc($assoc);
	}


	/**
	 * @return array<int|string, mixed>
	 * @throws Db\Exceptions\ConnectionException
	 * @throws Db\Exceptions\QueryException
	 * @throws Exceptions\QueryException
	 * @throws Exceptions\QueryBuilderException
	 */
	public function fetchPairs(string|null $key = null, string|null $value = null): array
	{
		return $this->execute()->fetchPairs($key, $value);
	}


	/**
	 * @return Db\RowIterator<int, Db\Row>
	 * @throws Db\Exceptions\ConnectionException
	 * @throws Db\Exceptions\QueryException
	 * @throws Exceptions\QueryException
	 * @throws Exceptions\QueryBuilderException
	 */
	public function fetchIterator(): Db\RowIterator
	{
		/**	@phpstan-var Db\RowIterator<int, Db\Row> */
		return $this->execute()->fetchIterator();
	}


	/**
	 * @throws Db\Exceptions\ConnectionException
	 * @throws Db\Exceptions\QueryException
	 * @throws Exceptions\QueryException
	 * @throws Exceptions\QueryBuilderException
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
