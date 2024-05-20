<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db;

use PgSql;

class Connection
{
	private Internals $internals;

	private AsyncHelper $asyncHelper;

	private Transaction|NULL $transaction = NULL;


	/**
	 * @throws Exceptions\ConnectionException
	 */
	public function __construct(
		string $connectionConfig = '',
		bool $connectForceNew = FALSE,
		bool $connectAsync = FALSE,
	)
	{
		$this->internals = new Internals($this, $connectionConfig, $connectForceNew, $connectAsync);
		$this->asyncHelper = new AsyncHelper($this->internals);
	}


	/**
	 * @throws Exceptions\ConnectionException
	 */
	public function connect(): static
	{
		$this->internals->connect();

		return $this;
	}


	/**
	 * @throws Exceptions\ConnectionException
	 */
	public function isConnected(): bool
	{
		return $this->internals->isConnected();
	}


	/**
	 * @throws Exceptions\ConnectionException
	 */
	public function ping(): bool
	{
		return \pg_ping($this->getResource());
	}


	public function setConnectionConfig(string $config): static
	{
		$this->internals->setConnectionConfig($config);

		return $this;
	}


	public function getConnectionConfig(): string
	{
		return $this->internals->getConnectionConfig();
	}


	public function setConnectForceNew(bool $forceNew = TRUE): static
	{
		$this->internals->setConnectForceNew($forceNew);

		return $this;
	}


	public function setConnectAsync(bool $async = TRUE): static
	{
		$this->internals->setConnectAsync($async);

		return $this;
	}


	public function setConnectAsyncWaitSeconds(int $seconds): static
	{
		$this->internals->setConnectAsyncWaitSeconds($seconds);

		return $this;
	}


	public function setErrorVerbosity(int $errorVerbosity): static
	{
		$this->internals->setErrorVerbosity($errorVerbosity);

		return $this;
	}


	public function addOnConnect(callable $callback): static
	{
		$this->internals->addOnConnect($callback);

		return $this;
	}


	public function addOnClose(callable $callback): static
	{
		$this->internals->addOnClose($callback);

		return $this;
	}


	public function addOnQuery(callable $callback): static
	{
		$this->internals->addOnQuery($callback);

		return $this;
	}


	public function addOnResult(callable $callback): static
	{
		$this->internals->addOnResult($callback);

		return $this;
	}


	public function close(): static
	{
		$this->internals->close();

		return $this;
	}


	public function setResultFactory(ResultFactory $resultFactory): static
	{
		$this->internals->setResultFactory($resultFactory);

		return $this;
	}


	public function setDefaultRowFactory(RowFactory $rowFactory): static
	{
		$this->internals->setDefaultRowFactory($rowFactory);

		return $this;
	}


	public function setDataTypeParser(DataTypeParser $dataTypeParser): static
	{
		$this->internals->setDataTypeParser($dataTypeParser);

		return $this;
	}


	public function setDataTypeCache(DataTypeCache $dataTypeCache): static
	{
		$this->internals->setDataTypeCache($dataTypeCache);

		return $this;
	}


	/**
	 * @throws Exceptions\ConnectionException
	 * @throws Exceptions\QueryException
	 */
	public function query(string|Query|Sql\Query $sql, mixed ...$params): Result
	{
		\assert(\array_is_list($params));
		return $this->queryArgs($sql, $params);
	}


	/**
	 * @param list<mixed> $params
	 * @throws Exceptions\ConnectionException
	 * @throws Exceptions\QueryException
	 */
	public function queryArgs(string|Query|Sql\Query $sql, array $params): Result
	{
		$query = $this->prepareQuery($this->normalizeQuery($sql, $params));

		$startTime = $this->internals->hasOnQuery() ? \hrtime(TRUE) : NULL;

		$queryParams = $query->getParams();
		if ($queryParams === []) {
			$resource = @\pg_query($this->getResource(), $query->getSql()); // intentionally @
		} else {
			$resource = @\pg_query_params($this->getResource(), $query->getSql(), $queryParams); // intentionally @
		}

		if ($resource === FALSE) {
			throw Exceptions\QueryException::queryFailed($query, $this->getLastError());
		}

		if ($startTime !== NULL) {
			$this->internals->onQuery($query, \hrtime(TRUE) - $startTime);
		}

		return $this->internals->createResult($resource, $query);
	}


	/**
	 * @throws Exceptions\ConnectionException
	 * @throws Exceptions\QueryException
	 */
	public function execute(string $sql): static
	{
		$sql = $this->prepareQuery($sql);

		$startTime = $this->internals->hasOnQuery() ? \hrtime(TRUE) : NULL;

		$resource = @\pg_query($this->getResource(), $sql); // intentionally @
		if ($resource === FALSE) {
			throw Exceptions\QueryException::queryFailed(new Query($sql, []), $this->getLastError());
		}

		if ($startTime !== NULL) {
			$this->internals->onQuery(new Query($sql, []), \hrtime(TRUE) - $startTime);
		}

		return $this;
	}


	/**
	 * @throws Exceptions\ConnectionException
	 * @throws Exceptions\QueryException
	 */
	public function asyncQuery(string|Query|Sql\Query $sql, mixed ...$params): AsyncQuery
	{
		\assert(\array_is_list($params));
		return $this->asyncQueryArgs($sql, $params);
	}


	/**
	 * @param list<mixed> $params
	 * @throws Exceptions\ConnectionException
	 * @throws Exceptions\QueryException
	 */
	public function asyncQueryArgs(string|Query|Sql\Query $sql, array $params): AsyncQuery
	{
		$query = $this->prepareQuery($this->normalizeQuery($sql, $params));

		$queryParams = $query->getParams();
		if ($queryParams === []) {
			$querySuccess = @\pg_send_query($this->getResource(), $query->getSql()); // intentionally @
		} else {
			$querySuccess = @\pg_send_query_params($this->getResource(), $query->getSql(), $query->getParams()); // intentionally @
		}

		if ($querySuccess === FALSE) {
			throw Exceptions\ConnectionException::asyncQuerySentFailed($this->getLastError());
		}

		if ($this->internals->hasOnQuery()) {
			$this->internals->onQuery($query);
		}

		return $this->asyncHelper->createAndSetAsyncQuery($query);
	}


	/**
	 * @throws Exceptions\ConnectionException
	 * @throws Exceptions\QueryException
	 */
	public function asyncExecute(string $sql): static
	{
		$sql = $this->prepareQuery($sql);

		$querySuccess = @\pg_send_query($this->getResource(), $sql); // intentionally @
		if ($querySuccess === FALSE) {
			throw Exceptions\ConnectionException::asyncQuerySentFailed($this->getLastError());
		}

		if ($this->internals->hasOnQuery()) {
			$this->internals->onQuery(new Query($sql, []));
		}

		$this->asyncHelper->setAsyncExecuteQuery($sql);

		return $this;
	}


	/**
	 * @throws Exceptions\ConnectionException
	 * @throws Exceptions\QueryException
	 */
	public function completeAsyncExecute(): static
	{
		$asyncExecuteQuery = $this->asyncHelper->getAsyncExecuteQuery();
		if ($asyncExecuteQuery === NULL) {
			throw Exceptions\ConnectionException::asyncNoExecuteIsSent();
		}

		while (($resource = \pg_get_result($this->getResource())) !== FALSE) {
			if (!$this->asyncHelper::checkAsyncQueryResult($resource)) {
				throw Exceptions\QueryException::asyncQueryFailed(
					new Query($asyncExecuteQuery, []),
					(string) \pg_result_error($resource),
				);
			}
		}

		$this->asyncHelper->clearQuery();

		return $this;
	}


	/**
	 * @throws Exceptions\ConnectionException
	 */
	public function cancelAsyncQuery(): static
	{
		if (!\pg_cancel_query($this->getResource())) {
			throw Exceptions\ConnectionException::asyncCancelFailed();
		}

		$this->asyncHelper->clearQuery();

		return $this;
	}


	public function prepareStatement(string $sql): PreparedStatement
	{
		return new PreparedStatement($this->internals, $this->prepareQuery($sql));
	}


	public function asyncPrepareStatement(string $sql): AsyncPreparedStatement
	{
		return new AsyncPreparedStatement($this->asyncHelper, $this->internals, $this->prepareQuery($sql));
	}


	/**
	 * @return list<string>
	 */
	public function getNotices(bool $clearAfterRead = TRUE): array
	{
		$notices = \pg_last_notice($this->getResource(), \PGSQL_NOTICE_ALL);
		if ($notices === FALSE) {
			throw Exceptions\ConnectionException::cantGetNotices();
		}

		if ($clearAfterRead) {
			\pg_last_notice($this->getResource(), \PGSQL_NOTICE_CLEAR);
		}

		/** @phpstan-var list<string> */
		return $notices;
	}


	public function transaction(): Transaction
	{
		if ($this->transaction === NULL) {
			$this->transaction = new Transaction($this);
		}

		return $this->transaction;
	}


	/**
	 * @throws Exceptions\ConnectionException
	 */
	public function isBusy(): bool
	{
		return \pg_connection_busy($this->getResource());
	}


	/**
	 * @throws Exceptions\ConnectionException
	 */
	public function isInTransaction(): bool
	{
		return !\in_array(\pg_transaction_status($this->getResource()), [\PGSQL_TRANSACTION_UNKNOWN, \PGSQL_TRANSACTION_IDLE], TRUE);
	}


	/**
	 * @throws Exceptions\ConnectionException
	 */
	public function getResource(): PgSql\Connection
	{
		return $this->internals->getConnectedResource();
	}


	/**
	 * @param list<mixed> $params
	 * @throws Exceptions\QueryException
	 */
	private function normalizeQuery(string|Query|Sql\Query $sql, array $params): Query
	{
		if (\is_string($sql)) {
			$sql = new Sql\Query($sql, $params);
		} else if ($params !== []) {
			throw Exceptions\QueryException::cantPassParams();
		}

		return $sql instanceof Query ? $sql : $sql->createQuery();
	}


	/**
	 * Extend this method to update query before execution.
	 *
	 * @return ($query is string ? string : Query)
	 */
	protected function prepareQuery(string|Query $query): string|Query
	{
		return $query;
	}


	public function getLastError(): string
	{
		return $this->internals->getLastError();
	}


	/**
	 * Prevents unserialization.
	 */
	public function __wakeup(): void
	{
		throw new \RuntimeException(\sprintf('You can\'t serialize or unserialize \'%s\' instances.', static::class));
	}


	/**
	 * Prevents serialization.
	 *
	 * @return array<string, mixed>
	 */
	public function __sleep(): array
	{
		throw new \RuntimeException(\sprintf('You can\'t serialize or unserialize \'%s\' instances.', static::class));
	}

}
