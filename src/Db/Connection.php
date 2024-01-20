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

	private AsyncHelper $asyncHelper;

	private Events $events;

	private RowFactory|NULL $defaultRowFactory = NULL;

	private DataTypeParser|NULL $dataTypeParser = NULL;

	private DataTypeCache|NULL $dataTypeCache = NULL;

	private Transaction|NULL $transaction = NULL;

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

		$this->asyncHelper = new AsyncHelper($this);
		$this->events = new Events($this);
	}


	/**
	 * @throws Exceptions\ConnectionException
	 */
	public function connect(): self
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

			$this->events->onConnect();
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
		return \pg_ping($this->getConnectedResource());
	}


	public function setConnectionConfig(string $config): self
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


	public function setConnectForceNew(bool $forceNew = TRUE): self
	{
		if ($this->isConnected()) {
			throw Exceptions\ConnectionException::cantChangeWhenConnected('forceNew');
		}

		$this->connectForceNew = $forceNew;

		return $this;
	}


	public function setConnectAsync(bool $async = TRUE): self
	{
		if ($this->isConnected()) {
			throw Exceptions\ConnectionException::cantChangeWhenConnected('async');
		}

		$this->connectAsync = $async;

		return $this;
	}


	public function setConnectAsyncWaitSeconds(int $seconds): self
	{
		if ($this->isConnected()) {
			throw Exceptions\ConnectionException::cantChangeWhenConnected('asyncWaitSeconds');
		}

		$this->connectAsyncWaitSeconds = $seconds;

		return $this;
	}


	public function setErrorVerbosity(int $errorVerbosity): self
	{
		if ($this->errorVerbosity !== $errorVerbosity) {
			$this->errorVerbosity = $errorVerbosity;

			if ($this->isConnected()) {
				\pg_set_error_verbosity($this->getConnectedResource(), $this->errorVerbosity);
			}
		}

		return $this;
	}


	public function addOnConnect(callable $callback): self
	{
		$this->events->addOnConnect($callback);

		return $this;
	}


	public function addOnClose(callable $callback): self
	{
		$this->events->addOnClose($callback);

		return $this;
	}


	public function addOnQuery(callable $callback): self
	{
		$this->events->addOnQuery($callback);

		return $this;
	}


	public function addOnResult(callable $callback): self
	{
		$this->events->addOnResult($callback);

		return $this;
	}


	public function close(): self
	{
		if ($this->isConnected()) {
			$this->events->onClose();
		}

		if ($this->resource !== NULL) {
			\pg_close($this->resource);
		}

		$this->resource = NULL;
		$this->connected = FALSE;

		return $this;
	}


	public function setDefaultRowFactory(RowFactory $rowFactory): self
	{
		$this->defaultRowFactory = $rowFactory;

		return $this;
	}


	private function getDefaultRowFactory(): RowFactory
	{
		if ($this->defaultRowFactory === NULL) {
			$this->defaultRowFactory = new RowFactories\Basic();
		}

		return $this->defaultRowFactory;
	}


	public function setDataTypeParser(DataTypeParser $dataTypeParser): self
	{
		$this->dataTypeParser = $dataTypeParser;

		return $this;
	}


	private function getDataTypeParser(): DataTypeParser
	{
		if ($this->dataTypeParser === NULL) {
			$this->dataTypeParser = new DataTypeParsers\Basic();
		}

		return $this->dataTypeParser;
	}


	public function setDataTypeCache(DataTypeCache $dataTypeCache): self
	{
		$this->dataTypeCache = $dataTypeCache;

		return $this;
	}


	/**
	 * @return array<int, string>|NULL
	 */
	private function getDataTypesCache(): array|NULL
	{
		if ($this->dataTypeCache !== NULL) {
			return $this->dataTypeCache->load($this);
		}

		return NULL;
	}


	/**
	 * @throws Exceptions\ConnectionException
	 * @throws Exceptions\QueryException
	 */
	public function query(string|Sql\Query $sqlQuery, mixed ...$params): Result
	{
		\assert(\array_is_list($params));
		return $this->queryArgs($sqlQuery, $params);
	}


	/**
	 * @param list<mixed> $params
	 * @throws Exceptions\ConnectionException
	 * @throws Exceptions\QueryException
	 */
	public function queryArgs(string|Sql\Query $sqlQuery, array $params): Result
	{
		$query = $this->normalizeSqlQuery($sqlQuery, $params)->createQuery();

		$startTime = $this->events->hasOnQuery() ? \hrtime(TRUE) : NULL;

		$queryParams = $query->getParams();
		if ($queryParams === []) {
			$resource = @\pg_query($this->getConnectedResource(), $query->getSql()); // intentionally @
		} else {
			$resource = @\pg_query_params($this->getConnectedResource(), $query->getSql(), $queryParams); // intentionally @
		}

		if ($resource === FALSE) {
			throw Exceptions\QueryException::queryFailed($query, $this->getLastError());
		}

		if ($startTime !== NULL) {
			$this->events->onQuery($query, \hrtime(TRUE) - $startTime);
		}

		return $this->createResult($resource, $query);
	}


	/**
	 * @internal
	 */
	public function createResult(PgSql\Result $resource, Query $query): Result
	{
		$result = new Result(
			$resource,
			$query,
			$this->getDefaultRowFactory(),
			$this->getDataTypeParser(),
			$this->getDataTypesCache(),
		);

		$this->events->onResult($result);

		return $result;
	}


	/**
	 * @throws Exceptions\ConnectionException
	 * @throws Exceptions\QueryException
	 */
	public function execute(string $sql): self
	{
		$startTime = $this->events->hasOnQuery() ? \hrtime(TRUE) : NULL;

		$resource = @\pg_query($this->getConnectedResource(), $sql); // intentionally @
		if ($resource === FALSE) {
			throw Exceptions\QueryException::queryFailed(new Query($sql, []), $this->getLastError());
		}

		if ($startTime !== NULL) {
			$this->events->onQuery(new Query($sql, []), \hrtime(TRUE) - $startTime);
		}

		return $this;
	}


	/**
	 * @throws Exceptions\ConnectionException
	 * @throws Exceptions\QueryException
	 */
	public function asyncQuery(string|Sql\Query $sqlQuery, mixed ...$params): AsyncQuery
	{
		\assert(\array_is_list($params));
		return $this->asyncQueryArgs($sqlQuery, $params);
	}


	/**
	 * @param list<mixed> $params
	 * @throws Exceptions\ConnectionException
	 * @throws Exceptions\QueryException
	 */
	public function asyncQueryArgs(string|Sql\Query $sqlQuery, array $params): AsyncQuery
	{
		$query = $this->normalizeSqlQuery($sqlQuery, $params)->createQuery();

		$queryParams = $query->getParams();
		if ($queryParams === []) {
			$querySuccess = @\pg_send_query($this->getConnectedResource(), $query->getSql()); // intentionally @
		} else {
			$querySuccess = @\pg_send_query_params($this->getConnectedResource(), $query->getSql(), $query->getParams()); // intentionally @
		}

		if ($querySuccess === FALSE) {
			throw Exceptions\ConnectionException::asyncQuerySentFailed($this->getLastError());
		}

		if ($this->events->hasOnQuery()) {
			$this->events->onQuery($query);
		}

		return $this->asyncHelper->createAndSetAsyncQuery($query);
	}


	/**
	 * @throws Exceptions\ConnectionException
	 * @throws Exceptions\QueryException
	 */
	public function asyncExecute(string $sql): self
	{
		$querySuccess = @\pg_send_query($this->getConnectedResource(), $sql); // intentionally @
		if ($querySuccess === FALSE) {
			throw Exceptions\ConnectionException::asyncQuerySentFailed($this->getLastError());
		}

		if ($this->events->hasOnQuery()) {
			$this->events->onQuery(new Query($sql, []));
		}

		$this->asyncHelper->setAsyncExecuteQuery($sql);

		return $this;
	}


	/**
	 * @throws Exceptions\ConnectionException
	 * @throws Exceptions\QueryException
	 */
	public function completeAsyncExecute(): self
	{
		$asyncExecuteQuery = $this->asyncHelper->getAsyncExecuteQuery();
		if ($asyncExecuteQuery === NULL) {
			throw Exceptions\ConnectionException::asyncNoExecuteIsSent();
		}

		while (($result = \pg_get_result($this->getConnectedResource())) !== FALSE) {
			if (!$this->asyncHelper::checkAsyncQueryResult($result)) {
				throw Exceptions\QueryException::asyncQueryFailed(
					new Query($asyncExecuteQuery, []),
					(string) \pg_result_error($result),
				);
			}
		}

		$this->asyncHelper->clearQuery();

		return $this;
	}


	/**
	 * @throws Exceptions\ConnectionException
	 */
	public function cancelAsyncQuery(): self
	{
		if (!\pg_cancel_query($this->getConnectedResource())) {
			throw Exceptions\ConnectionException::asyncCancelFailed();
		}

		$this->asyncHelper->clearQuery();

		return $this;
	}


	public function prepareStatement(string $query): PreparedStatement
	{
		return new PreparedStatement($this, $this->events, $query);
	}


	public function asyncPrepareStatement(string $query): AsyncPreparedStatement
	{
		return new AsyncPreparedStatement($this, $this->asyncHelper, $this->events, $query);
	}


	/**
	 * @return list<string>
	 */
	public function getNotices(bool $clearAfterRead = TRUE): array
	{
		$notices = \pg_last_notice($this->getConnectedResource(), \PGSQL_NOTICE_ALL);
		if ($notices === FALSE) {
			throw Exceptions\ConnectionException::cantGetNotices();
		}

		if ($clearAfterRead) {
			\pg_last_notice($this->getConnectedResource(), \PGSQL_NOTICE_CLEAR);
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
		return \pg_connection_busy($this->getConnectedResource());
	}


	/**
	 * @throws Exceptions\ConnectionException
	 */
	public function isInTransaction(): bool
	{
		return !\in_array(\pg_transaction_status($this->getConnectedResource()), [\PGSQL_TRANSACTION_UNKNOWN, \PGSQL_TRANSACTION_IDLE], TRUE);
	}


	/**
	 * @throws Exceptions\ConnectionException
	 */
	public function getResource(): PgSql\Connection
	{
		return $this->getConnectedResource();
	}


	/**
	 * @param list<mixed> $params
	 * @throws Exceptions\QueryException
	 */
	private function normalizeSqlQuery(string|Sql\Query $query, array $params): Sql\Query
	{
		if ($query instanceof Sql\Query) {
			if ($params !== []) {
				throw Exceptions\QueryException::cantPassParams();
			}
		} else {
			$query = new Sql\Query($query, $params);
		}

		return $query;
	}


	public function getLastError(): string
	{
		return ($this->resource !== NULL)
			? \pg_last_error($this->resource)
			: 'unknown error';
	}


	/**
	 * @throws Exceptions\ConnectionException
	 */
	private function getConnectedResource(): PgSql\Connection
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
						$this->events->onConnect();

						return $this->resource;
				}
			} while ((($test - $start) / 1000000000) <= $this->connectAsyncWaitSeconds);
			throw Exceptions\ConnectionException::asyncConnectTimeout($test, $this->connectAsyncWaitSeconds);
		}

		return $this->resource;
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
