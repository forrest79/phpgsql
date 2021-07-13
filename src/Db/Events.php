<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db;

class Events
{
	/** @var Connection */
	private $connection;

	/** @var array<callable(Connection): void> function (Connection $connection) {} */
	private $onConnect = [];

	/** @var array<callable(Connection): void> function (Connection $connection) {} */
	private $onClose = [];

	/** @var array<callable(Connection, Query, ?float, ?string): void> function (Connection $connection, Query $query, ?float $time, ?string $prepareStatementName) {} */
	private $onQuery = [];

	/** @var array<callable(Connection, Result): void> function (Connection $connection, Result $result) {} */
	private $onResult = [];


	public function __construct(Connection $connection)
	{
		$this->connection = $connection;
	}


	public function addOnConnect(callable $callback): self
	{
		$this->onConnect[] = $callback;
		return $this;
	}


	public function addOnClose(callable $callback): self
	{
		$this->onClose[] = $callback;
		return $this;
	}


	public function addOnQuery(callable $callback): self
	{
		$this->onQuery[] = $callback;
		return $this;
	}


	public function addOnResult(callable $callback): self
	{
		$this->onResult[] = $callback;
		return $this;
	}


	public function onConnect(): void
	{
		foreach ($this->onConnect as $event) {
			$event($this->connection);
		}
	}


	public function onClose(): void
	{
		foreach ($this->onClose as $event) {
			$event($this->connection);
		}
	}


	public function onQuery(Query $query, ?float $time = NULL, ?string $prepareStatementName = NULL): void
	{
		foreach ($this->onQuery as $event) {
			$event($this->connection, $query, $time, $prepareStatementName);
		}
	}


	public function onResult(Result $result): void
	{
		foreach ($this->onResult as $event) {
			$event($this->connection, $result);
		}
	}


	public function hasOnQuery(): bool
	{
		return $this->onQuery !== [];
	}

}
