<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db;

class Transaction
{
	protected Connection $connection;


	public function __construct(Connection $connection)
	{
		$this->connection = $connection;
	}


	/**
	 * @throws Exceptions\ConnectionException
	 * @throws Exceptions\QueryException
	 */
	public function begin(string|NULL $mode = NULL): static
	{
		$this->connection->query('BEGIN' . ($mode === NULL ? '' : (' ' . $mode)));

		return $this;
	}


	/**
	 * @throws Exceptions\ConnectionException
	 * @throws Exceptions\QueryException
	 */
	public function commit(): static
	{
		$this->connection->query('COMMIT');

		return $this;
	}


	/**
	 * @throws Exceptions\ConnectionException
	 * @throws Exceptions\QueryException
	 */
	public function rollback(): static
	{
		$this->connection->query('ROLLBACK');

		return $this;
	}


	/**
	 * @throws Exceptions\ConnectionException
	 * @throws Exceptions\QueryException
	 */
	public function savepoint(string $name): static
	{
		$this->connection->query('SAVEPOINT ' . $name);

		return $this;
	}


	/**
	 * @throws Exceptions\ConnectionException
	 * @throws Exceptions\QueryException
	 */
	public function releaseSavepoint(string $name): static
	{
		$this->connection->query('RELEASE SAVEPOINT ' . $name);

		return $this;
	}


	/**
	 * @throws Exceptions\ConnectionException
	 * @throws Exceptions\QueryException
	 */
	public function rollbackToSavepoint(string $name): static
	{
		$this->connection->query('ROLLBACK TO SAVEPOINT ' . $name);

		return $this;
	}


	public function isInTransaction(): bool
	{
		return $this->connection->isInTransaction();
	}

}
