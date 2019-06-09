<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Fluent;

use Forrest79\PhPgSql\Db;

/**
 * @method self table($table, ?string $alias = NULL)
 * @method self select(array $columns)
 * @method self distinct()
 * @method self from($from, ?string $alias = NULL)
 * @method self join($join, ?string $alias = NULL, $onCondition = NULL)
 * @method self innerJoin($join, ?string $alias = NULL, $onCondition = NULL)
 * @method self leftJoin($join, ?string $alias = NULL, $onCondition = NULL)
 * @method self leftOuterJoin($join, ?string $alias = NULL, $onCondition = NULL)
 * @method self rightJoin($join, ?string $alias = NULL, $onCondition = NULL)
 * @method self rightOuterJoin($join, ?string $alias = NULL, $onCondition = NULL)
 * @method self fullJoin($join, ?string $alias = NULL, $onCondition = NULL)
 * @method self fullOuterJoin($join, ?string $alias = NULL, $onCondition = NULL)
 * @method self crossJoin($join, ?string $alias = NULL)
 * @method self on(string $alias, $condition)
 * @method self where($condition, ...$params)
 * @method self groupBy(array $columns)
 * @method self having($condition, ...$params)
 * @method self orderBy(array $colums)
 * @method self limit(int $limit)
 * @method self offset(int $offset)
 * @method self union($query)
 * @method self unionAll($query)
 * @method self intersect($query)
 * @method self except($query)
 * @method self insert(?string $into = NULL, ?array $columns = [])
 * @method self values(array $data)
 * @method self rows(array $rows)
 * @method self update(?string $table = NULL, ?string $alias = NULL)
 * @method self set(array $data)
 * @method self delete(?string $from = NULL, ?string $alias = NULL)
 * @method self returning(array $returning)
 * @method self truncate(?string $table = NULL)
 * @method self prefix(string $queryPrefix, ...$params)
 * @method self sufix(string $querySufix, ...$params)
 */
class FluentExecute extends Fluent implements \Countable, \IteratorAggregate
{
	/** @var Db\Connection */
	private $connection;

	/** @var Db\Result|NULL */
	private $result;


	public function __construct(Db\Connection $connection)
	{
		$this->connection = $connection;
	}


	/**
	 * @throws Exceptions\FluentException
	 */
	protected function updateFluent(): void
	{
		if ($this->result !== NULL) {
			throw Exceptions\FluentException::cantUpdateFluentAfterExecute();
		}
		parent::updateFluent();
	}


	/**
	 * @throws Db\Exceptions\ConnectionException
	 * @throws Db\Exceptions\QueryException
	 * @throws Exceptions\FluentException
	 * @throws Exceptions\QueryBuilderException
	 */
	public function execute(): Db\Result
	{
		if ($this->result === NULL) {
			$this->result = $this->connection->query($this->getQuery());
		}
		return $this->result;
	}


	/**
	 * @throws Db\Exceptions\ConnectionException
	 * @throws Db\Exceptions\QueryException
	 * @throws Exceptions\FluentException
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
	 * @throws Exceptions\FluentException
	 */
	public function free(): bool
	{
		if ($this->result === NULL) {
			throw Exceptions\FluentException::youMustExecuteFluentBeforeThat();
		}
		return $this->result->free();
	}


	/**
	 * @throws Db\Exceptions\ConnectionException
	 * @throws Db\Exceptions\QueryException
	 * @throws Exceptions\FluentException
	 * @throws Exceptions\QueryBuilderException
	 */
	public function count(): int
	{
		return $this->execute()->getRowCount();
	}


	/**
	 * @throws Db\Exceptions\ConnectionException
	 * @throws Db\Exceptions\QueryException
	 * @throws Exceptions\FluentException
	 * @throws Exceptions\QueryBuilderException
	 */
	public function getIterator(): Db\ResultIterator
	{
		return $this->execute()->getIterator();
	}


	/**
	 * @throws Db\Exceptions\ConnectionException
	 * @throws Db\Exceptions\QueryException
	 * @throws Exceptions\FluentException
	 * @throws Exceptions\QueryBuilderException
	 */
	public function getAffectedRows(): int
	{
		return $this->execute()->getAffectedRows();
	}


	/**
	 * @throws Db\Exceptions\ConnectionException
	 * @throws Db\Exceptions\QueryException
	 * @throws Exceptions\FluentException
	 * @throws Exceptions\QueryBuilderException
	 */
	public function fetch(): ?Db\Row
	{
		return $this->execute()->fetch();
	}


	/**
	 * @throws Db\Exceptions\ConnectionException
	 * @throws Db\Exceptions\QueryException
	 * @throws Exceptions\FluentException
	 * @throws Exceptions\QueryBuilderException
	 * @return mixed value on success, NULL if no next record
	 */
	public function fetchSingle()
	{
		return $this->execute()->fetchSingle();
	}


	/**
	 * @throws Db\Exceptions\ConnectionException
	 * @throws Db\Exceptions\QueryException
	 * @throws Exceptions\FluentException
	 * @throws Exceptions\QueryBuilderException
	 * @return Db\Row[]
	 */
	public function fetchAll(?int $offset = NULL, ?int $limit = NULL): array
	{
		return $this->execute()->fetchAll($offset, $limit);
	}


	/**
	 * @throws Db\Exceptions\ConnectionException
	 * @throws Db\Exceptions\QueryException
	 * @throws Exceptions\FluentException
	 * @throws Exceptions\QueryBuilderException
	 */
	public function fetchAssoc(string $assoc): array
	{
		return $this->execute()->fetchAssoc($assoc);
	}


	/**
	 * @throws Db\Exceptions\ConnectionException
	 * @throws Db\Exceptions\QueryException
	 * @throws Exceptions\FluentException
	 * @throws Exceptions\QueryBuilderException
	 */
	public function fetchPairs(?string $key = NULL, ?string $value = NULL): array
	{
		return $this->execute()->fetchPairs($key, $value);
	}

}
