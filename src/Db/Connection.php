<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db;

use PgSql;

class Connection
{
	private string $connectionConfig;

	private bool $connectForceNew;

	private bool $connectAsync;

	private int $connectAsyncWaitSeconds;

	private int $errorVerbosity;

	private Internals $internals;

	private AsyncHelper $asyncHelper;

	private ResultFactory|NULL $resultFactory = NULL;

	private RowFactory|NULL $defaultRowFactory = NULL;

	private DataTypeParser|NULL $dataTypeParser = NULL;

	private DataTypeCache|NULL $dataTypeCache = NULL;

	private Transaction|NULL $transaction = NULL;

	/** @var list<callable(Connection): void> function (static $connection) {} */
	private array $onConnect = [];

	/** @var list<callable(Connection): void> function (static $connection) {} */
	private array $onClose = [];

	private PgSql\Connection|NULL $resource = NULL;

	private bool $connected = FALSE;

	/** @var resource */
	private $asyncStream;


	/**
	 * @throws Exceptions\ConnectionException
	 */
	public function __construct(
		string $connectionConfig = '',
		bool $connectForceNew = FALSE,
		bool $connectAsync = FALSE,
	)
	{
		$this->connectionConfig = $connectionConfig;
		$this->connectForceNew = $connectForceNew;
		$this->connectAsync = $connectAsync;

		$this->connectAsyncWaitSeconds = 15;
		$this->errorVerbosity = \PGSQL_ERRORS_DEFAULT;

		$this->internals = new Internals($this);
		$this->asyncHelper = new AsyncHelper($this->internals);
	}


	/**
	 * @throws Exceptions\ConnectionException
	 */
	public function connect(): static
	{
		if ($this->connectionConfig === '') {
			throw Exceptions\ConnectionException::noConfig();
		}

		$connectType = 0;
		if ($this->connectForceNew === TRUE) {
			$connectType |= \PGSQL_CONNECT_FORCE_NEW;
		}
		if ($this->connectAsync === TRUE) {
			$connectType |= \PGSQL_CONNECT_ASYNC;
		}

		$resource = @\pg_connect($this->connectionConfig, $connectType); // intentionally @
		if ($resource === FALSE) {
			throw Exceptions\ConnectionException::connectionFailed();
		} elseif (\pg_connection_status($resource) === \PGSQL_CONNECTION_BAD) {
			throw Exceptions\ConnectionException::badConnection();
		}

		$this->resource = $resource;

		if ($this->connectAsync === TRUE) {
			$stream = \pg_socket($resource);
			if ($stream === FALSE) {
				throw Exceptions\ConnectionException::asyncStreamFailed();
			}

			$this->asyncStream = $stream;
		} else {
			if ($this->errorVerbosity !== \PGSQL_ERRORS_DEFAULT) {
				\pg_set_error_verbosity($this->resource, $this->errorVerbosity);
			}

			$this->connected = TRUE;

			$this->onConnect();
		}

		return $this;
	}


	/**
	 * @throws Exceptions\ConnectionException
	 */
	public function isConnected(): bool
	{
		return $this->connected;
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
		if ($this->isConnected()) {
			throw Exceptions\ConnectionException::cantChangeWhenConnected('config');
		}

		$this->connectionConfig = $config;

		return $this;
	}


	public function getConnectionConfig(): string
	{
		return $this->connectionConfig;
	}


	public function setConnectForceNew(bool $forceNew = TRUE): static
	{
		if ($this->isConnected()) {
			throw Exceptions\ConnectionException::cantChangeWhenConnected('forceNew');
		}

		$this->connectForceNew = $forceNew;

		return $this;
	}


	public function setConnectAsync(bool $async = TRUE): static
	{
		if ($this->isConnected()) {
			throw Exceptions\ConnectionException::cantChangeWhenConnected('async');
		}

		$this->connectAsync = $async;

		return $this;
	}


	public function setConnectAsyncWaitSeconds(int $seconds): static
	{
		if ($this->isConnected()) {
			throw Exceptions\ConnectionException::cantChangeWhenConnected('asyncWaitSeconds');
		}

		$this->connectAsyncWaitSeconds = $seconds;

		return $this;
	}


	public function setErrorVerbosity(int $errorVerbosity): static
	{
		if ($this->errorVerbosity !== $errorVerbosity) {
			$this->errorVerbosity = $errorVerbosity;

			if ($this->isConnected()) {
				\pg_set_error_verbosity($this->getResource(), $this->errorVerbosity);
			}
		}

		return $this;
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
		$this->internals->addOnQuery($callback);

		return $this;
	}


	public function addOnResult(callable $callback): static
	{
		$this->internals->addOnResult($callback);

		return $this;
	}


	private function onConnect(): void
	{
		foreach ($this->onConnect as $event) {
			$event($this);
		}
	}


	private function onClose(): void
	{
		foreach ($this->onClose as $event) {
			$event($this);
		}
	}


	public function close(): static
	{
		if ($this->isConnected()) {
			$this->onClose();
		}

		if ($this->resource !== NULL) {
			\pg_close($this->resource);
		}

		$this->resource = NULL;
		$this->connected = FALSE;

		return $this;
	}


	public function setResultFactory(ResultFactory $resultFactory): static
	{
		$this->resultFactory = $resultFactory;

		return $this;
	}


	public function getResultFactory(): ResultFactory
	{
		if ($this->resultFactory === NULL) {
			$this->resultFactory = new ResultFactories\Basic($this);
		}

		return $this->resultFactory;
	}


	public function setDefaultRowFactory(RowFactory $rowFactory): static
	{
		$this->defaultRowFactory = $rowFactory;

		return $this;
	}


	public function getDefaultRowFactory(): RowFactory
	{
		if ($this->defaultRowFactory === NULL) {
			$this->defaultRowFactory = new RowFactories\Basic();
		}

		return $this->defaultRowFactory;
	}


	public function setDataTypeParser(DataTypeParser $dataTypeParser): static
	{
		$this->dataTypeParser = $dataTypeParser;

		return $this;
	}


	public function getDataTypeParser(): DataTypeParser
	{
		if ($this->dataTypeParser === NULL) {
			$this->dataTypeParser = new DataTypeParsers\Basic();
		}

		return $this->dataTypeParser;
	}


	public function setDataTypeCache(DataTypeCache $dataTypeCache): static
	{
		$this->dataTypeCache = $dataTypeCache;

		return $this;
	}


	/**
	 * @return array<int, string>|NULL
	 */
	public function getDataTypesCache(): array|NULL
	{
		return $this->dataTypeCache?->load($this) ?? NULL;
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
		if ($this->resource === NULL) {
			$this->connect();
		}

		\assert($this->resource !== NULL);

		if ($this->connected === FALSE) {
			$start = \hrtime(TRUE);
			do {
				$test = \hrtime(TRUE);
				switch (\pg_connect_poll($this->resource)) {
					case \PGSQL_POLLING_READING:
						while (!self::asyncIsReadable($this->asyncStream));
						break;
					case \PGSQL_POLLING_WRITING:
						while (!self::asyncIsWritable($this->asyncStream));
						break;
					case \PGSQL_POLLING_FAILED:
						throw Exceptions\ConnectionException::asyncConnectFailed();
					case \PGSQL_POLLING_OK:
					case \PGSQL_POLLING_ACTIVE: // this can't happen?
						if ($this->errorVerbosity !== \PGSQL_ERRORS_DEFAULT) {
							\pg_set_error_verbosity($this->resource, $this->errorVerbosity);
						}
						$this->connected = TRUE;
						$this->onConnect();

						\assert($this->resource !== NULL);
						return $this->resource;
				}
			} while ((($test - $start) / 1000000000) <= $this->connectAsyncWaitSeconds);
			throw Exceptions\ConnectionException::asyncConnectTimeout($test, $this->connectAsyncWaitSeconds);
		}

		return $this->resource;
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
		return $this->resource !== NULL
			? \pg_last_error($this->resource)
			: 'unknown error';
	}


	/**
	 * @param resource $stream
	 */
	private static function asyncIsReadable($stream): bool
	{
		$read = [$stream];
		$write = $ex = [];

		return (bool) \stream_select($read, $write, $ex, $usec = 1, 0);
	}


	/**
	 * @param resource $stream
	 */
	private static function asyncIsWritable($stream): bool
	{
		$write = [$stream];
		$read = $ex = [];

		return (bool) \stream_select($read, $write, $ex, $usec = 1, 0);
	}


	/**
	 * Prevent unserialization.
	 */
	public function __wakeup(): void
	{
		throw new \RuntimeException(\sprintf('You can\'t serialize or unserialize \'%s\' instances.', static::class));
	}


	/**
	 * Prevent serialization.
	 *
	 * @return array<string, mixed>
	 */
	public function __sleep(): array
	{
		throw new \RuntimeException(\sprintf('You can\'t serialize or unserialize \'%s\' instances.', static::class));
	}

}
