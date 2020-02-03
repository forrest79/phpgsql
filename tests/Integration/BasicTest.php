<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Tests\Integration;

use Forrest79\PhPgSql\Db;
use Tester;

require_once __DIR__ . '/TestCase.php';

/**
 * @testCase
 */
class BasicTest extends TestCase
{

	public function testGetLastErrorWithNoConnection(): void
	{
		Tester\Assert::same('', $this->connection->getLastError());
	}


	public function testSetErrorVerbosity(): void
	{
		// PGSQL_ERRORS_DEFAULT
		Tester\Assert::exception(function (): void {
			$this->connection->query('SELECT bad_column');
		}, Db\Exceptions\QueryException::class, '#ERROR:  column "bad_column" does not exist#', Db\Exceptions\QueryException::QUERY_FAILED);

		// PGSQL_ERRORS_TERSE
		$this->connection->setErrorVerbosity(\PGSQL_ERRORS_TERSE);
		Tester\Assert::exception(function (): void {
			$this->connection->query('SELECT bad_column');
		}, Db\Exceptions\QueryException::class, '#ERROR:  column "bad_column" does not exist#', Db\Exceptions\QueryException::QUERY_FAILED);

		// PGSQL_ERRORS_VERBOSE
		$this->connection->setErrorVerbosity(\PGSQL_ERRORS_VERBOSE);
		Tester\Assert::exception(function (): void {
			$this->connection->query('SELECT bad_column');
		}, Db\Exceptions\QueryException::class, '#ERROR:  42703: column "bad_column" does not exist#', Db\Exceptions\QueryException::QUERY_FAILED);
	}


	public function testSetErrorVerbosityOnConnect(): void
	{
		$connection = $this->createConnection();

		Tester\Assert::false($connection->isConnected());

		$connection->setErrorVerbosity(\PGSQL_ERRORS_VERBOSE);

		Tester\Assert::exception(static function () use ($connection): void {
			$connection->query('SELECT bad_column');
		}, Db\Exceptions\QueryException::class, '#ERROR:  42703: column "bad_column" does not exist#', Db\Exceptions\QueryException::QUERY_FAILED);

		$connection->close();
	}


	public function testPing(): void
	{
		Tester\Assert::true($this->connection->ping());
	}


	public function testConnectedResource(): void
	{
		Tester\Assert::notEqual(NULL, $this->connection->getResource());
	}


	public function testConnectionNoConfig(): void
	{
		$this->connection->setConnectionConfig('');
		Tester\Assert::exception(function (): void {
			$this->connection->connect();
		}, Db\Exceptions\ConnectionException::class, NULL, Db\Exceptions\ConnectionException::NO_CONFIG);
	}


	public function testConnectionForceNew(): void
	{
		$this->connection->setConnectForceNew(TRUE);
		Tester\Assert::true($this->connection->ping());
	}


	public function testConnectionAsync(): void
	{
		$this->connection->setConnectAsync(TRUE);
		$this->connection->setConnectAsyncWaitSeconds(10);
		Tester\Assert::true($this->connection->ping());
	}


	public function testFailedConnection(): void
	{
		$this->connection->setConnectionConfig(\str_replace('user=', 'user=non-existing-user-', $this->getConfig()));
		Tester\Assert::exception(function (): void {
			$this->connection->ping();
		}, Db\Exceptions\ConnectionException::class, NULL, Db\Exceptions\ConnectionException::CONNECTION_FAILED);
	}


	public function testChangeConnectionSettingsAfterConnected(): void
	{
		Tester\Assert::true($this->connection->ping());
		Tester\Assert::exception(function (): void {
			$this->connection->setConnectionConfig('');
		}, Db\Exceptions\ConnectionException::class, NULL, Db\Exceptions\ConnectionException::CANT_CHANGE_CONNECTION_SETTINGS);
		Tester\Assert::exception(function (): void {
			$this->connection->setConnectForceNew(TRUE);
		}, Db\Exceptions\ConnectionException::class, NULL, Db\Exceptions\ConnectionException::CANT_CHANGE_CONNECTION_SETTINGS);
		Tester\Assert::exception(function (): void {
			$this->connection->setConnectAsync(TRUE);
		}, Db\Exceptions\ConnectionException::class, NULL, Db\Exceptions\ConnectionException::CANT_CHANGE_CONNECTION_SETTINGS);
		Tester\Assert::exception(function (): void {
			$this->connection->setConnectAsyncWaitSeconds(5);
		}, Db\Exceptions\ConnectionException::class, NULL, Db\Exceptions\ConnectionException::CANT_CHANGE_CONNECTION_SETTINGS);

		$this->connection->close();

		$this->connection->setConnectionConfig('');
		$this->connection->setConnectForceNew(TRUE);
		$this->connection->setConnectAsync(TRUE);
		$this->connection->setConnectAsyncWaitSeconds(5);
	}


	public function testConnectionEvents(): void
	{
		$hasConnect = FALSE;
		$hasClose = FALSE;
		$hasQuery = FALSE;
		$hasExecute = FALSE;
		$queryDuration = 0;

		$this->connection->addOnConnect(static function (Db\Connection $connection) use (&$hasConnect): void {
			$hasConnect = $connection->query('SELECT TRUE')->fetchSingle();
		});

		$this->connection->addOnQuery(static function (
			Db\Connection $connection,
			Db\Query $query,
			float $duration
		) use (
			&$hasQuery,
			&$hasExecute,
			&$queryDuration
		): void {
			if ($query->getSql() === 'SELECT 1') {
				$hasQuery = TRUE;
			} else if ($query->getSql() === 'SELECT 2') {
				$hasExecute = TRUE;
			}
			$queryDuration = $duration;
		});

		$this->connection->addOnClose(static function () use (&$hasClose): void {
			$hasClose = TRUE;
		});

		Tester\Assert::same(1, $this->connection->query('SELECT 1')->fetchSingle());

		$this->connection->execute('SELECT 2');

		$this->connection->close();

		Tester\Assert::true($hasConnect);
		Tester\Assert::true($hasClose);
		Tester\Assert::true($hasQuery);
		Tester\Assert::true($hasExecute);
		Tester\Assert::true($queryDuration > 0);
	}


	public function testFailedQuery(): void
	{
		Tester\Assert::exception(function (): void {
			try {
				$this->connection->query('SELECT bad_column');
			} catch (Db\Exceptions\QueryException $e) {
				Tester\Assert::true($e->getQuery() instanceof Db\Query);
				throw $e;
			}
		}, Db\Exceptions\QueryException::class, NULL, Db\Exceptions\QueryException::QUERY_FAILED);
	}


	public function testPassParamToQuery(): void
	{
		Tester\Assert::exception(function (): void {
			$query = Db\Sql\Query::create('SELECT 1');
			$this->connection->query($query, 1);
		}, Db\Exceptions\QueryException::class, NULL, Db\Exceptions\QueryException::CANT_PASS_PARAMS);
	}


	public function testQueryWithFluentWithParams(): void
	{
		$result = $this->connection->query(
			'SELECT generate_series FROM ?',
			Db\Sql\Expression::createArgs('generate_series(?::integer, ?::integer, ?::integer)', [2, 1, -1])
		);

		$rows = $result->fetchAssoc('generate_series');

		Tester\Assert::same(2, \count($rows));

		Tester\Assert::same(2, $rows[2]->generate_series);
		Tester\Assert::same(1, $rows[1]->generate_series);

		$result->free();
	}


	public function testOnlyOneQueryForPreparedStatement(): void
	{
		Tester\Assert::exception(
			function (): void {
				$this->connection->query('SELECT 1 WHERE 1 = ?; SELECT 2 WHERE 2 = ?', 1, 2);
			},
			Db\Exceptions\QueryException::class,
			'#^.+ERROR:  cannot insert multiple commands into a prepared statement\.$#',
			Db\Exceptions\QueryException::QUERY_FAILED
		);
	}


	public function testExecute(): void
	{
		Tester\Assert::noError(function (): void {
			$this->connection->execute('SELECT 1; SELECT 2');
		});
	}


	public function testFailedExecute(): void
	{
		Tester\Assert::exception(function (): void {
			$this->connection->execute('SELECT bad_column');
		}, Db\Exceptions\QueryException::class, NULL, Db\Exceptions\QueryException::QUERY_FAILED);
	}


	public function testGetNotifications(): void
	{
		$this->skipOnTravis();

		$this->connection->execute('DO $BODY$ BEGIN RAISE NOTICE \'Test notice\'; END; $BODY$ LANGUAGE plpgsql;');
		Tester\Assert::same(['NOTICE:  Test notice'], $this->connection->getNotices());
		Tester\Assert::same([], $this->connection->getNotices());
	}


	public function testGetNotificationsWithouClearing(): void
	{
		$this->skipOnTravis();

		$this->connection->execute('DO $BODY$ BEGIN RAISE NOTICE \'Test notice\'; END; $BODY$ LANGUAGE plpgsql;');
		Tester\Assert::same(['NOTICE:  Test notice'], $this->connection->getNotices(FALSE));
		Tester\Assert::same(['NOTICE:  Test notice'], $this->connection->getNotices());
	}

}

\run(BasicTest::class);
