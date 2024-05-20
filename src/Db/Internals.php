<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db;

use PgSql;

class Internals
{
	private Connection $connection;

	private string $connectionConfig;

	private bool $connectForceNew;

	private bool $connectAsync;

	private int $connectAsyncWaitSeconds;

	private int $errorVerbosity;

	private ResultFactory|NULL $resultFactory = NULL;

	private RowFactory|NULL $defaultRowFactory = NULL;

	private DataTypeParser|NULL $dataTypeParser = NULL;

	private DataTypeCache|NULL $dataTypeCache = NULL;

	private PgSql\Connection|NULL $resource = NULL;

	private bool $connected = FALSE;

	/** @var resource */
	private $asyncStream;

	/** @var list<callable(Connection): void> function (Connection $connection) {} */
	private array $onConnect = [];

	/** @var list<callable(Connection): void> function (Connection $connection) {} */
	private array $onClose = [];

	/** @var list<callable(Connection, Query, int|float|NULL, string|NULL): void> function (Connection $connection, Query $query, int|float|NULL $timeNs, string|NULL $prepareStatementName) {} */
	private array $onQuery = [];

	/** @var list<callable(Connection, Result): void> function (Connection $connection, Result $result) {} */
	private array $onResult = [];


	public function __construct(
		Connection $connection,
		string $connectionConfig = '',
		bool $connectForceNew = FALSE,
		bool $connectAsync = FALSE,
	)
	{
		$this->connection = $connection;

		$this->connectionConfig = $connectionConfig;
		$this->connectForceNew = $connectForceNew;
		$this->connectAsync = $connectAsync;

		$this->connectAsyncWaitSeconds = 15;
		$this->errorVerbosity = \PGSQL_ERRORS_DEFAULT;
	}


	public function setResultFactory(ResultFactory $resultFactory): void
	{
		$this->resultFactory = $resultFactory;
	}


	private function getResultFactory(): ResultFactory
	{
		if ($this->resultFactory === NULL) {
			$this->resultFactory = new ResultFactories\Basic($this);
		}

		return $this->resultFactory;
	}


	public function createResult(PgSql\Result $resource, Query $query): Result
	{
		$result = $this->getResultFactory()->createResult($resource, $query);

		$this->onResult($result);

		return $result;
	}


	public function setDefaultRowFactory(RowFactory $rowFactory): void
	{
		$this->defaultRowFactory = $rowFactory;
	}


	public function getDefaultRowFactory(): RowFactory
	{
		if ($this->defaultRowFactory === NULL) {
			$this->defaultRowFactory = new RowFactories\Basic();
		}

		return $this->defaultRowFactory;
	}


	public function setDataTypeParser(DataTypeParser $dataTypeParser): void
	{
		$this->dataTypeParser = $dataTypeParser;
	}


	public function getDataTypeParser(): DataTypeParser
	{
		if ($this->dataTypeParser === NULL) {
			$this->dataTypeParser = new DataTypeParsers\Basic();
		}

		return $this->dataTypeParser;
	}


	public function setDataTypeCache(DataTypeCache $dataTypeCache): void
	{
		$this->dataTypeCache = $dataTypeCache;
	}


	/**
	 * @return array<int, string>|NULL
	 */
	public function getDataTypesCache(): array|NULL
	{
		if ($this->dataTypeCache !== NULL) {
			return $this->dataTypeCache->load($this->connection);
		}

		return NULL;
	}


	public function setConnectionConfig(string $config): void
	{
		if ($this->isConnected()) {
			throw Exceptions\ConnectionException::cantChangeWhenConnected('config');
		}

		$this->connectionConfig = $config;
	}


	public function getConnectionConfig(): string
	{
		return $this->connectionConfig;
	}


	public function setConnectForceNew(bool $forceNew = TRUE): void
	{
		if ($this->isConnected()) {
			throw Exceptions\ConnectionException::cantChangeWhenConnected('forceNew');
		}

		$this->connectForceNew = $forceNew;
	}


	public function setConnectAsync(bool $async = TRUE): void
	{
		if ($this->isConnected()) {
			throw Exceptions\ConnectionException::cantChangeWhenConnected('async');
		}

		$this->connectAsync = $async;
	}


	public function setConnectAsyncWaitSeconds(int $seconds): void
	{
		if ($this->isConnected()) {
			throw Exceptions\ConnectionException::cantChangeWhenConnected('asyncWaitSeconds');
		}

		$this->connectAsyncWaitSeconds = $seconds;
	}


	public function setErrorVerbosity(int $errorVerbosity): void
	{
		if ($this->errorVerbosity !== $errorVerbosity) {
			$this->errorVerbosity = $errorVerbosity;

			if ($this->isConnected()) {
				\pg_set_error_verbosity($this->getConnectedResource(), $this->errorVerbosity);
			}
		}
	}


	/**
	 * @throws Exceptions\ConnectionException
	 */
	public function connect(): void
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
	public function getConnectedResource(): PgSql\Connection
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


	public function close(): void
	{
		if ($this->isConnected()) {
			$this->onClose();
		}

		if ($this->resource !== NULL) {
			\pg_close($this->resource);
		}

		$this->resource = NULL;
		$this->connected = FALSE;
	}


	public function getLastError(): string
	{
		return $this->resource !== NULL
			? \pg_last_error($this->resource)
			: 'unknown error';
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


	private function onConnect(): void
	{
		foreach ($this->onConnect as $event) {
			$event($this->connection);
		}
	}


	private function onClose(): void
	{
		foreach ($this->onClose as $event) {
			$event($this->connection);
		}
	}


	public function onQuery(Query $query, float|NULL $timeNs = NULL, string|NULL $prepareStatementName = NULL): void
	{
		foreach ($this->onQuery as $event) {
			$event($this->connection, $query, $timeNs, $prepareStatementName);
		}
	}


	private function onResult(Result $result): void
	{
		foreach ($this->onResult as $event) {
			$event($this->connection, $result);
		}
	}


	public function hasOnQuery(): bool
	{
		return $this->onQuery !== [];
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

}
