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
	protected function updateQuery(): void
	{
		if ($this->result !== NULL) {
			throw Exceptions\QueryException::cantUpdateQueryAfterExecute();
		}
		parent::updateQuery();
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
		if ($this->result !== NULL) {
			$this->free();
		}
		$this->result = NULL;
		return $this->execute();
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
		return $this->execute()->getRowCount();
	}


	/**
	 * @throws Db\Exceptions\ConnectionException
	 * @throws Db\Exceptions\QueryException
	 * @throws Exceptions\QueryException
	 * @throws Exceptions\QueryBuilderException
	 */
	public function getIterator(): Db\ResultIterator
	{
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
	public function fetch(): ?Db\RowResult
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
	 * @return array<Db\RowResult>
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
	 * @return array<mixed>
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
	 * @return array<mixed, mixed>
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
	 * @throws Db\Exceptions\ConnectionException
	 * @throws Db\Exceptions\QueryException
	 * @throws Exceptions\QueryException
	 * @throws Exceptions\QueryBuilderException
	 */
	public function asyncExecute(): Db\AsyncQuery
	{
		return $this->connection->asyncQuery($this->createSqlQuery());
	}

}
