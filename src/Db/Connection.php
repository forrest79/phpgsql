<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db;

class Connection
{
	/** @var string */
	private $connectionConfig = '';

	/** @var bool */
	private $connectForceNew = FALSE;

	/** @var bool */
	private $connectAsync = FALSE;

	/** @var int */
	private $connectAsyncWaitSeconds = 15;

	/** @var resource|NULL */
	private $resource;

	/** @var bool */
	private $connected = FALSE;

	/** @var resource */
	private $asyncStream;

	/** @var RowFactory */
	private $defaultRowFactory;

	/** @var DataTypeParser */
	private $dataTypeParser;

	/** @var DataTypeCache|NULL */
	private $dataTypeCache;

	/** @var Query */
	private $asyncQuery;

	/** @var AsyncResult|NULL */
	private $asyncResult;

	/** @var callable[] function (Connection $connection) {} */
	private $onConnect = [];

	/** @var callable[] function (Connection $connection) {} */
	private $onClose = [];

	/** @var callable[] function (Connection $connection, Query $query, float $time) {} */
	private $onQuery = [];


	/**
	 * @throws Exceptions\ConnectionException
	 */
	public function __construct(string $connectionConfig = '', bool $connectForceNew = FALSE, bool $connectAsync = FALSE)
	{
		if (!\extension_loaded('pgsql')) {
			throw Exceptions\ConnectionException::noExtensionException();
		}

		$this->connectionConfig = $connectionConfig;
		$this->connectForceNew = $connectForceNew;
		$this->connectAsync = $connectAsync;
	}


	/**
	 * @throws Exceptions\ConnectionException
	 */
	public function connect(): self
	{
		if ($this->connectionConfig === '') {
			throw Exceptions\ConnectionException::noConfigException();
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
			throw Exceptions\ConnectionException::connectionFailedException();
		} elseif (\pg_connection_status($resource) === \PGSQL_CONNECTION_BAD) {
			throw Exceptions\ConnectionException::badConnectionException();
		}

		$this->resource = $resource;

		if ($this->connectAsync === TRUE) {
			$stream = \pg_socket($resource);
			if ($stream === FALSE) {
				throw Exceptions\ConnectionException::asyncStreamFailedException();
			}
			$this->asyncStream = $stream;
		} else {
			$this->connected = TRUE;
			if ($this->onConnect !== []) {
				$this->onConnect();
			}
		}

		return $this;
	}


	/**
	 * @throws Exceptions\ConnectionException
	 */
	public function isConnected(bool $waitForConnect = FALSE): bool
	{
		if ($waitForConnect === TRUE) {
			$this->getConnectedResource();
		}
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
			throw Exceptions\ConnectionException::cantChangeConnectionSettings();
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
			throw Exceptions\ConnectionException::cantChangeConnectionSettings();
		}
		$this->connectForceNew = $forceNew;
		return $this;
	}


	public function setConnectAsync(bool $async = TRUE): self
	{
		if ($this->isConnected()) {
			throw Exceptions\ConnectionException::cantChangeConnectionSettings();
		}
		$this->connectAsync = $async;
		return $this;
	}


	public function setConnectAsyncWaitSeconds(int $seconds): self
	{
		if ($this->isConnected()) {
			throw Exceptions\ConnectionException::cantChangeConnectionSettings();
		}
		$this->connectAsyncWaitSeconds = $seconds;
		return $this;
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


	public function close(): self
	{
		if ($this->isConnected()) {
			$this->onClose();
		}

		if ($this->resource !== NULL) {
			\pg_close($this->resource);
			$this->resource = NULL;
		}

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


	private function getDataTypesCache(): ?array
	{
		return $this->dataTypeCache === NULL ? NULL : $this->dataTypeCache->load($this);
	}


	/**
	 * @param string|Query $query
	 * @param mixed ...$params
	 * @return Result
	 * @throws Exceptions\ConnectionException
	 * @throws Exceptions\QueryException
	 */
	public function query($query, ...$params): Result
	{
		return $this->queryArgs($query, $params);
	}


	/**
	 * @param string|Query $query
	 * @param array $params
	 * @return Result
	 * @throws Exceptions\ConnectionException
	 * @throws Exceptions\QueryException
	 */
	public function queryArgs($query, array $params): Result
	{
		$query = Helper::prepareSql($this->normalizeQuery($query, $params));

		$start = $this->onQuery !== [] ? \microtime(TRUE) : NULL;

		$resource = @\pg_query_params($this->getConnectedResource(), $query->getSql(), $query->getParams()); // intentionally @
		if ($resource === FALSE) {
			throw Exceptions\QueryException::queryFailed($query, $this->getLastError());
		}

		if ($start !== NULL) {
			$this->onQuery($query, \microtime(TRUE) - $start);
		}

		return new Result($resource, $this->getDefaultRowFactory(), $this->getDataTypeParser(), $this->getDataTypesCache());
	}


	/**
	 * @param string $query
	 * @param mixed ...$params
	 * @return Query
	 */
	public function createQuery(string $query, ...$params): Query
	{
		return $this->createQueryArgs($query, $params);
	}


	public function createQueryArgs(string $query, array $params): Query
	{
		return new Query($query, $params);
	}


	/**
	 * @param string|Query $query
	 * @param mixed ...$params
	 * @return AsyncResult
	 * @throws Exceptions\ConnectionException
	 * @throws Exceptions\QueryException
	 */
	public function asyncQuery($query, ...$params): AsyncResult
	{
		return $this->asyncQueryArgs($query, $params);
	}


	/**
	 * @param string|Query $query
	 * @param array $params
	 * @return AsyncResult
	 * @throws Exceptions\ConnectionException
	 * @throws Exceptions\QueryException
	 */
	public function asyncQueryArgs($query, array $params): AsyncResult
	{
		$this->asyncQuery = $query = Helper::prepareSql($this->normalizeQuery($query, $params));
		if (@\pg_send_query_params($this->getConnectedResource(), $query->getSql(), $query->getParams()) === FALSE) { // intentionally @
			throw Exceptions\QueryException::asyncQueryFailed($query, $this->getLastError());
		}

		if ($this->onQuery !== []) {
			$this->onQuery($query);
		}

		return $this->asyncResult = new AsyncResult($this->getDefaultRowFactory(), $this->getDataTypeParser(), $this->getDataTypesCache());
	}


	/**
	 * @throws Exceptions\ConnectionException
	 * @throws Exceptions\QueryException
	 */
	public function waitForAsyncQuery(): self
	{
		if ($this->asyncResult === NULL) {
			throw Exceptions\ConnectionException::asyncNoQueryWasSentException();
		}

		$resource = \pg_get_result($this->getConnectedResource());
		if ($resource === FALSE) {
			throw Exceptions\QueryException::asyncQueryFailed($this->asyncQuery, $this->getLastError());
		}
		$this->asyncResult->finishAsyncQuery($resource);

		$this->asyncResult = NULL;

		return $this;
	}


	/**
	 * @throws Exceptions\ConnectionException
	 * @throws Exceptions\QueryException
	 */
	public function begin(?string $savepoint = NULL): self
	{
		$this->query($savepoint === NULL ? 'BEGIN' : ('SAVEPOINT ' . $savepoint));
		return $this;
	}


	/**
	 * @throws Exceptions\ConnectionException
	 * @throws Exceptions\QueryException
	 */
	public function commit(?string $savepoint = NULL): self
	{
		$this->query($savepoint === NULL ? 'COMMIT' : ('RELEASE SAVEPOINT ' . $savepoint));
		return $this;
	}


	/**
	 * @throws Exceptions\ConnectionException
	 * @throws Exceptions\QueryException
	 */
	public function rollback(?string $savepoint = NULL): self
	{
		$this->query($savepoint === NULL ? 'ROLLBACK' : ('ROLLBACK TO SAVEPOINT ' . $savepoint));
		return $this;
	}


	/**
	 * @throws Exceptions\ConnectionException
	 */
	public function isInTransaction(): bool
	{
		return !\in_array(\pg_transaction_status($this->getConnectedResource()), [\PGSQL_TRANSACTION_UNKNOWN, \PGSQL_TRANSACTION_IDLE], TRUE);
	}


	/**
	 * @return resource
	 * @throws Exceptions\ConnectionException
	 */
	public function getResource()
	{
		return $this->getConnectedResource();
	}


	/**
	 * @param string|Query $query
	 * @param array $params
	 * @return Query
	 * @throws Exceptions\QueryException
	 */
	private function normalizeQuery($query, array $params): Query
	{
		if ($query instanceof Query) {
			if ($params !== []) {
				throw Exceptions\QueryException::cantPassParams();
			}
		} else {
			$query = $this->createQueryArgs($query, $params);
		}

		return $query;
	}


	private function getLastError(): string
	{
		return $this->resource === NULL ? \pg_last_error() : \pg_last_error($this->resource);
	}


	/**
	 * @return resource
	 * @throws Exceptions\ConnectionException
	 */
	private function getConnectedResource()
	{
		if ($this->resource === NULL) {
			$this->connect();
		}

		if ($this->connected === FALSE) {
			$start = \microtime(TRUE);
			do {
				$test = \microtime(TRUE);
				switch (\pg_connect_poll($this->resource)) {
					case \PGSQL_POLLING_READING:
						while (!self::isReadable($this->asyncStream));
						break;
					case \PGSQL_POLLING_WRITING:
						while (!self::isWritable($this->asyncStream));
						break;
					case \PGSQL_POLLING_FAILED:
						throw Exceptions\ConnectionException::asyncConnectFailedException();
					case \PGSQL_POLLING_OK:
					case \PGSQL_POLLING_ACTIVE: // this can't happen?
						$this->connected = TRUE;
						$this->onConnect();
						return $this->resource;
				}
			} while (($test - $start) <= $this->connectAsyncWaitSeconds);
			throw Exceptions\ConnectionException::asyncConnectTimeoutException($test, $this->connectAsyncWaitSeconds);
		}

		return $this->resource;
	}


	private function onConnect(): void
	{
		\array_walk($this->onConnect, function (callable $event): void {
			$event($this);
		});
	}


	private function onClose(): void
	{
		\array_walk($this->onClose, function (callable $event): void {
			$event($this);
		});
	}


	private function onQuery(Query $query, ?float $time = NULL): void
	{
		\array_walk($this->onQuery, function (callable $event) use ($query, $time): void {
			$event($this, $query, $time);
		});
	}


	/**
	 * @param resource $stream
	 * @return bool
	 */
	private static function isReadable($stream): bool
	{
		$read = [$stream];
		$write = $ex = [];
		return (bool) \stream_select($read, $write, $ex, $usec = 1, 0);
	}


	/**
	 * @param resource $stream
	 * @return bool
	 */
	private static function isWritable($stream): bool
	{
		$write = [$stream];
		$read = $ex = [];
		return (bool) \stream_select($read, $write, $ex, $usec = 1, 0);
	}


	/**
	 * @param string $value
	 * @param mixed ...$params
	 * @return Literal
	 */
	public static function literal(string $value, ...$params): Literal
	{
		return new Literal($value, ...$params);
	}


	public static function literalArgs(string $value, array $params): Literal
	{
		return new Literal($value, ...$params);
	}

}
