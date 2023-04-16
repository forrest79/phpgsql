<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Fluent;

use Forrest79\PhPgSql\Db;

/**
 * @implements \IteratorAggregate<int, Db\Row>
 */
class QueryExecute extends Query implements \Countable, \IteratorAggregate
{
	/** @var Db\Connection */
	private $connection;

	/** @var Db\Result|NULL */
	private $result;


	public function __construct(QueryBuilder $queryBuilder, Db\Connection $connection)
	{
		$this->connection = $connection;
		parent::__construct($queryBuilder);
	}


	/**
	 * @throws Exceptions\QueryException
	 */
	protected function resetQuery(): void
	{
		if ($this->result !== NULL) {
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
		if ($this->result === NULL) {
			$this->result = $this->connection->query($this->createSqlQuery());
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


	private function releaseResult(bool $freeResult = TRUE): void
	{
		if ($freeResult && ($this->result !== NULL)) {
			$this->free();
		}

		$this->result = NULL;
	}


	/**
	 * @throws Exceptions\QueryException
	 */
	public function free(): bool
	{
		if ($this->result === NULL) {
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
	 * @deprecated Use fetchIterator() method.
	 * @throws Db\Exceptions\ConnectionException
	 * @throws Db\Exceptions\QueryException
	 * @throws Exceptions\QueryException
	 * @throws Exceptions\QueryBuilderException
	 */
	public function getIterator(): Db\ResultIterator
	{
		//trigger_error('Use fetchIterator() method.', E_USER_DEPRECATED);
		return $this->execute()->getIterator();
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
	public function fetch(): ?Db\Row
	{
		return $this->execute()->fetch();
	}


	/**
	 * @return mixed value on success, NULL if no next record
	 * @throws Db\Exceptions\ConnectionException
	 * @throws Db\Exceptions\QueryException
	 * @throws Exceptions\QueryException
	 * @throws Exceptions\QueryBuilderException
	 */
	public function fetchSingle()
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
	public function fetchAll(?int $offset = NULL, ?int $limit = NULL): array
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
	public function fetchPairs(?string $key = NULL, ?string $value = NULL): array
	{
		return $this->execute()->fetchPairs($key, $value);
	}


	/**
	 * @return Db\ResultIterator<int, Db\Row>
	 * @throws Db\Exceptions\ConnectionException
	 * @throws Db\Exceptions\QueryException
	 * @throws Exceptions\QueryException
	 * @throws Exceptions\QueryBuilderException
	 */
	public function fetchIterator(): Db\ResultIterator
	{
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
		return $this->connection->asyncQuery($this->createSqlQuery());
	}


	public function __clone()
	{
		$this->releaseResult(FALSE); // must be before parent clone - we need to allow resetQuery() first
		parent::__clone();
	}

}
