<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db\Exceptions;

class ConnectionException extends Exception
{
	public const NO_EXTENSION = 1;
	public const NO_CONFIG = 2;
	public const CONNECTION_FAILED = 3;
	public const BAD_CONNECTION = 4;
	public const ASYNC_STREAM_FAILED = 5;
	public const ASYNC_CONNECT_FAILED = 6;
	public const ASYNC_CONNECT_TIMEOUT = 7;
	public const ASYNC_NO_QUERIES_TO_SEND = 8;
	public const ASYNC_WAITING_RESULTS = 9;
	public const ASYNC_NO_QUERY_WAS_SENT = 10;
	public const ASYNC_SEND_QUERIES_FAILED = 11;
	public const ASYNC_FLUSH_RESULTS_FAILED = 12;
	public const ASYNC_CONSUME_INPUT_FAILED = 14;


	public static function noExtensionException(): self
	{
		return new self('PHP extension \'pgsql\' is not loaded.', self::NO_EXTENSION);
	}


	public static function noConfigException(): self
	{
		return new self('No configuration was provided.', self::NO_CONFIG);
	}


	public static function connectionFailedException(): self
	{
		return new self('Connection failed.', self::CONNECTION_FAILED);
	}


	public static function badConnectionException(): self
	{
		return new self('Connection failed (bad connection).', self::BAD_CONNECTION);
	}


	public static function asyncStreamFailedException(): self
	{
		return new self('Asynchronous connection error.', self::ASYNC_STREAM_FAILED);
	}


	public static function asyncConnectFailedException(): self
	{
		return new self('Asynchronous connection error.', self::ASYNC_CONNECT_FAILED);
	}


	public static function asyncConnectTimeoutException(float $afterSeconds, int $configSeconds): self
	{
		return new self(\sprintf('Asynchronous connection timeout after %f seconds (%d seconds are configured).', $afterSeconds, $configSeconds), self::ASYNC_CONNECT_TIMEOUT);
	}


	public static function asyncNoQueriesToSendException(): self
	{
		return new self('There\'re no queries to send.', self::ASYNC_NO_QUERIES_TO_SEND);
	}


	public static function asyncWaitingResultsException(): self
	{
		return new self('You must take results from previous async send via waitForAsyncQueriesResults().', self::ASYNC_WAITING_RESULTS);
	}


	public static function asyncNoQueryWasSentException(): self
	{
		return new self('No query was sent.', self::ASYNC_NO_QUERY_WAS_SENT);
	}


	public static function asyncSendQueryFailed(): self
	{
		return new self('Send query failed.', self::ASYNC_SEND_QUERIES_FAILED);
	}


	public static function asyncFlushResultsFailed(int $type): self
	{
		return new self(\sprintf('Flushing result failed #%s.', $type), self::ASYNC_FLUSH_RESULTS_FAILED);
	}


	public static function asyncConsumeInputFailed(): self
	{
		return new self('Consume input failed.', self::ASYNC_CONSUME_INPUT_FAILED);
	}

}
