<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db;

class Events
{
	private Connection $connection;

	/** @var list<callable(Connection): void> function (Connection $connection) {} */
	private array $onConnect = [];

	/** @var list<callable(Connection): void> function (Connection $connection) {} */
	private array $onClose = [];

	/** @var list<callable(Connection, Query, int|float|null, string|null): void> function (Connection $connection, Query $query, int|float|null $timeNs, string|null $prepareStatementName) {} */
	private array $onQuery = [];

	/** @var list<callable(Connection, Result): void> function (Connection $connection, Result $result) {} */
	private array $onResult = [];


	public function __construct(Connection $connection)
	{
		$this->connection = $connection;
	}


	public function addOnConnect(callable $callback): static
	{
		$this->onConnect[] = $callback;

		return $this;
	}


	public function addOnClose(callable $callback): static
	{
		$this->onClose[] = $callback;

		return $this;
	}


	public function addOnQuery(callable $callback): static
	{
		$this->onQuery[] = $callback;

		return $this;
	}


	public function addOnResult(callable $callback): static
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


	public function onQuery(Query $query, float|null $timeNs = null, string|null $prepareStatementName = null): void
	{
		foreach ($this->onQuery as $event) {
			$event($this->connection, $query, $timeNs, $prepareStatementName);
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
