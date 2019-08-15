<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db\Exceptions;

class ConnectionException extends Exception
{
	public const NO_EXTENSION = 1;
	public const NO_CONFIG = 2;
	public const CANT_CHANGE_CONNECTION_SETTINGS = 3;
	public const CONNECTION_FAILED = 4;
	public const BAD_CONNECTION = 5;
	public const ASYNC_STREAM_FAILED = 6;
	public const ASYNC_CONNECT_FAILED = 7;
	public const ASYNC_CONNECT_TIMEOUT = 8;
	public const ASYNC_NO_QUERY_WAS_SENT = 8;


	public static function noExtensionException(): self
	{
		return new self('PHP extension \'pgsql\' is not loaded.', self::NO_EXTENSION);
	}


	public static function noConfigException(): self
	{
		return new self('No configuration was provided.', self::NO_CONFIG);
	}


	public static function cantChangeConnectionSettings(): self
	{
		return new self('You can\'t change connection settings when connected.', self::CANT_CHANGE_CONNECTION_SETTINGS);
	}


	public static function connectionFailedException(): self
	{
		$message = '.';
		$lastPhpError = \error_get_last();
		if ($lastPhpError !== NULL && $lastPhpError['type'] === \E_WARNING) {
			$message = ': ' . $lastPhpError['message'];
		}
		return new self('Connection failed' . $message, self::CONNECTION_FAILED);
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


	public static function asyncNoQueryWasSentException(): self
	{
		return new self('No query was sent.', self::ASYNC_NO_QUERY_WAS_SENT);
	}

}
