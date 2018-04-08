<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db\Exceptions;

class ConnectionException extends Exception
{
	const NO_EXTENSION = 1;
	const NO_CONFIG = 2;
	const CONNECTION_FAILED = 3;
	const BAD_CONNECTION = 4;
	const ASYNC_STREAM_FAILED = 5;
	const ASYNC_CONNECT_FAILED = 6;
	const ASYNC_CONNECT_TIMEOUT = 7;
	const ASYNC_NO_QUERIES_TO_SEND = 8;
	const ASYNC_WAITING_RESULTS = 9;
	const ASYNC_NO_QUERY_WAS_SENT = 10;
	const ASYNC_SEND_QUERIES_FAILED = 11;
	const ASYNC_FLUSH_RESULTS_FAILED = 12;
	const ASYNC_CONSUME_INPUT_FAILED = 14;


	public static function noExtensionException()
	{
		return new self('PHP extension \'pgsql\' is not loaded.', self::NO_CONFIG);
	}


	public static function noConfigException()
	{
		return new self('No configuration was provided.', self::NO_CONFIG);
	}


	public static function connectionFailedException($message)
	{
		return new self(sprintf('Connection failed: %s.', $message), self::CONNECTION_FAILED);
	}


	public static function badConnectionException($message)
	{
		return new self(sprintf('Connection failed (bad connection): %s.', $message), self::BAD_CONNECTION);
	}


	public static function asyncStreamFailedException()
	{
		return new self('Asynchronous connection error.', self::NO_CONFIG);
	}


	public static function asyncConnectFailedException()
	{
		return new self('Asynchronous connection error.', self::ASYNC_CONNECT_FAILED);
	}


	public static function asyncConnectTimeoutException(int $afterSecond, int $configSeconds)
	{
		return new self(\sprintf('Asynchronous connection timeout after %s seconds (%s seconds are configured).', $afterSecond, $configSeconds), self::ASYNC_CONNECT_TIMEOUT);
	}


	public static function asyncNoQueriesToSendException()
	{
		return new self('There\'re no queries to send.', self::ASYNC_NO_QUERIES_TO_SEND);
	}


	public static function asyncWaitingResultsException()
	{
		return new self('You must take results from previous async send via waitForAsyncQueriesResults().', self::ASYNC_WAITING_RESULTS);
	}


	public static function asyncNoQueriyWasSentException()
	{
		return new self('There were sent queries.', self::ASYNC_NO_QUERY_WAS_SENT);
	}


	public static function asyncSendQueriesFailed()
	{
		return new self('There were sent queries.', self::ASYNC_SEND_QUERIES_FAILED);
	}


	public static function asyncFlushResultsFailed(int $type)
	{
		return new self(sprintf('Flushing result failed #%s.', $type), self::ASYNC_FLUSH_RESULTS_FAILED);
	}


	public static function asyncConsumeInputFailed()
	{
		return new self('Consume input failed.', self::ASYNC_CONSUME_INPUT_FAILED);
	}

}
