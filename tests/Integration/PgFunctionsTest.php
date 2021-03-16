<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Tests\Integration;

use Tester;

require_once __DIR__ . '/TestCase.php';

/**
 * Special test for internals pg_* functions and scenarious.
 *
 * @testCase
 */
class PgFunctionsTest extends TestCase
{

	public function testBasic(): void
	{
		$result1 = \pg_query($this->getConnectionResource(), 'SELECT 1 AS clm1, \'test\' AS clm2');

		Tester\Assert::same(1, \pg_affected_rows($result1)); // Affected rows for INSERT/UPDATE/DELETE, since PostgreSQL 9.0 return also number rows for SELECT
		Tester\Assert::same(1, \pg_num_rows($result1));
		Tester\Assert::same(2, \pg_num_fields($result1));

		Tester\Assert::same('clm1', \pg_field_name($result1, 0));
		Tester\Assert::same('clm2', \pg_field_name($result1, 1));

		Tester\Assert::same(23, \pg_field_type_oid($result1, 0)); // integer
		Tester\Assert::same(25, \pg_field_type_oid($result1, 1)); // text

		Tester\Assert::same('int4', \pg_field_type($result1, 0));
		Tester\Assert::same('text', \pg_field_type($result1, 1));

		$success1 = \pg_free_result($result1);

		Tester\Assert::true($success1);

		if (\PHP_VERSION_ID < 80000) {
			$success2 = @\pg_free_result($result1); // intentionally @ - `E_WARNING: pg_free_result(): supplied resource is not a valid PostgreSQL result resource`

			Tester\Assert::false($success2);
		} else {
			Tester\Assert::exception(static function () use ($result1): void {
				\pg_free_result($result1);
			}, \TypeError::class, 'pg_free_result(): supplied resource is not a valid PostgreSQL result resource');
		}
	}


	public function testGetNotice(): void
	{
		\pg_query($this->getConnectionResource(), 'DO $BODY$ BEGIN RAISE NOTICE \'Test notice\'; END; $BODY$ LANGUAGE plpgsql');
		Tester\Assert::same('NOTICE:  Test notice', \pg_last_notice($this->getConnectionResource()));
	}


	public function testTransactionStatus(): void
	{
		Tester\Assert::same(\PGSQL_TRANSACTION_IDLE, \pg_transaction_status($this->getConnectionResource()));

		\pg_query($this->getConnectionResource(), 'SELECT 1 AS clm1, \'test\' AS clm2');

		Tester\Assert::same(\PGSQL_TRANSACTION_IDLE, \pg_transaction_status($this->getConnectionResource()));

		\pg_query($this->getConnectionResource(), 'BEGIN');

		/*
		 * Non transatcion statuses:
		 * -------------------------
		 * PGSQL_TRANSACTION_IDLE
		 * PGSQL_TRANSACTION_UNKNOWN
		 *
		 * Active transaction states:
		 * --------------------------
		 * PGSQL_TRANSACTION_ACTIVE
		 * PGSQL_TRANSACTION_INTRANS
		 * PGSQL_TRANSACTION_INERROR
		 */

		Tester\Assert::same(\PGSQL_TRANSACTION_INTRANS, \pg_transaction_status($this->getConnectionResource()));

		\pg_query($this->getConnectionResource(), 'ROLLBACK');

		Tester\Assert::same(\PGSQL_TRANSACTION_IDLE, \pg_transaction_status($this->getConnectionResource()));
	}


	public function testErrors(): void
	{
		$result1 = @\pg_query($this->getConnectionResource(), 'SELECTx 1 AS clm1, \'test\' AS clm2');
		Tester\Assert::contains('ERROR:  syntax error at or near "SELECTx"', \pg_last_error($this->getConnectionResource()));
		if (\PHP_VERSION_ID < 80000) {
			Tester\Assert::false(\pg_result_error($result1)); // pg_result_error() is just for async queries
		} else {
			Tester\Assert::false($result1);
		}

		// ---

		$success1 = @\pg_send_query($this->getConnectionResource(), 'SELECTx 1 AS clm1, \'test\' AS clm2');
		Tester\Assert::true($success1);

		$result2 = \pg_get_result($this->getConnectionResource());
		Tester\Assert::true(\is_resource($result2));
		Tester\Assert::false(\pg_fetch_assoc($result2));
		Tester\Assert::contains('ERROR:  syntax error at or near "SELECTx"', self::pgResultError($result2));

		Tester\Assert::same('ERROR', \pg_result_error_field($result2, \PGSQL_DIAG_SEVERITY));
		Tester\Assert::same('42601', \pg_result_error_field($result2, \PGSQL_DIAG_SQLSTATE));
		Tester\Assert::same('syntax error at or near "SELECTx"', \pg_result_error_field($result2, \PGSQL_DIAG_MESSAGE_PRIMARY));
		Tester\Assert::null(\pg_result_error_field($result2, \PGSQL_DIAG_MESSAGE_DETAIL));
		Tester\Assert::null(\pg_result_error_field($result2, \PGSQL_DIAG_MESSAGE_HINT));
		Tester\Assert::same('1', \pg_result_error_field($result2, \PGSQL_DIAG_STATEMENT_POSITION));
		Tester\Assert::null(\pg_result_error_field($result2, \PGSQL_DIAG_INTERNAL_POSITION));
		Tester\Assert::null(\pg_result_error_field($result2, \PGSQL_DIAG_INTERNAL_QUERY));
		Tester\Assert::null(\pg_result_error_field($result2, \PGSQL_DIAG_CONTEXT));
		Tester\Assert::same('scan.l', \pg_result_error_field($result2, \PGSQL_DIAG_SOURCE_FILE));
		Tester\Assert::true(\in_array(\pg_result_error_field($result2, \PGSQL_DIAG_SOURCE_LINE), ['1149', '1180'], TRUE)); // PG12 => 1149, PG13 => 1180
		Tester\Assert::same('scanner_yyerror', \pg_result_error_field($result2, \PGSQL_DIAG_SOURCE_FUNCTION));

		// ---

		Tester\Assert::false(\pg_set_error_verbosity(\PGSQL_ERRORS_TERSE));
		@\pg_query($this->getConnectionResource(), 'SELECTx 1 AS clm1, \'test\' AS clm2');
		Tester\Assert::contains('ERROR:  syntax error at or near "SELECTx"', \pg_last_error($this->getConnectionResource()));

		@\pg_send_query($this->getConnectionResource(), 'SELECTx 1 AS clm1, \'test\' AS clm2');
		$result3 = \pg_get_result($this->getConnectionResource());
		Tester\Assert::contains('ERROR:  syntax error at or near "SELECTx"', self::pgResultError($result3));

		// ---

		Tester\Assert::same(\PGSQL_ERRORS_DEFAULT, \pg_set_error_verbosity(\PGSQL_ERRORS_DEFAULT)); // strange, according to doc, last verbosity should be returned, so PGSQL_ERRORS_TERSE
		@\pg_query($this->getConnectionResource(), 'SELECTx 1 AS clm1, \'test\' AS clm2');
		Tester\Assert::contains('ERROR:  syntax error at or near "SELECTx"', \pg_last_error($this->getConnectionResource()));

		@\pg_send_query($this->getConnectionResource(), 'SELECTx 1 AS clm1, \'test\' AS clm2');
		$result4 = \pg_get_result($this->getConnectionResource());
		Tester\Assert::contains('ERROR:  syntax error at or near "SELECTx"', self::pgResultError($result4));

		// ---

		Tester\Assert::same(\PGSQL_ERRORS_DEFAULT, \pg_set_error_verbosity(\PGSQL_ERRORS_VERBOSE));
		@\pg_query($this->getConnectionResource(), 'SELECTx 1 AS clm1, \'test\' AS clm2');
		Tester\Assert::contains('ERROR:  42601: syntax error at or near "SELECTx"', \pg_last_error($this->getConnectionResource()));

		@\pg_send_query($this->getConnectionResource(), 'SELECTx 1 AS clm1, \'test\' AS clm2');
		$result5 = \pg_get_result($this->getConnectionResource());
		Tester\Assert::contains('ERROR:  42601: syntax error at or near "SELECTx"', self::pgResultError($result5));

		// async errors are also in last error

		$success2 = \pg_send_query($this->getConnectionResource(), 'SELECTx 1 AS clm1');
		Tester\Assert::true($success2);
		Tester\Assert::same('', \pg_last_error($this->getConnectionResource()));

		\pg_get_result($this->getConnectionResource());

		// strange, error is filled after first get result
		Tester\Assert::contains('syntax error at or near "SELECTx"', \pg_last_error($this->getConnectionResource()));
	}


	public function testPgQueryParams(): void
	{
		$result1 = \pg_query_params($this->getConnectionResource(), 'SELECT 1 AS clm1', []);

		Tester\Assert::true(\is_resource($result1));

		// This is just for async
		Tester\Assert::false(\pg_connection_busy($this->getConnectionResource())); // connection is not bussy after sync query
		Tester\Assert::false(\pg_get_result($this->getConnectionResource())); // there is no waiting result for sync query

		Tester\Assert::same(['clm1' => '1'], \pg_fetch_assoc($result1));

		$result2 = \pg_query_params($this->getConnectionResource(), 'SELECT 1 AS clm1 WHERE 1 = $1', [1]);

		Tester\Assert::true(\is_resource($result2));
		Tester\Assert::same(['clm1' => '1'], \pg_fetch_assoc($result2));

		$result3 = @\pg_query_params($this->getConnectionResource(), 'SELECT 1 AS clm1 WHERE 1 = $1; SELECT 2;', [1]); // intentionally @ - `E_WARNING: pg_query_params(): Query failed: ERROR:  cannot insert multiple commands into a prepared statement`

		Tester\Assert::false($result3);
	}


	public function testPgQuery(): void
	{
		$result1 = \pg_query($this->getConnectionResource(), 'SELECT 1 AS clm1');

		Tester\Assert::true(\is_resource($result1));

		// This is just for async
		Tester\Assert::false(\pg_connection_busy($this->getConnectionResource())); // connection is not bussy after sync query
		Tester\Assert::false(\pg_get_result($this->getConnectionResource())); // there is no waiting result for sync query

		Tester\Assert::same(['clm1' => '1'], \pg_fetch_assoc($result1));

		$result2 = \pg_query($this->getConnectionResource(), 'SELECT 1 AS clm1; SELECT 2 AS clm2;');

		Tester\Assert::false(\pg_get_result($this->getConnectionResource())); // there is no waiting result for sync query, even if there is more queries in `pg_query`
		Tester\Assert::true(\is_resource($result2));
		Tester\Assert::same(['clm2' => '2'], \pg_fetch_assoc($result2)); // only last query is fetched
	}


	public function testConnectionBusy(): void
	{
		\pg_send_query_params($this->getConnectionResource(), 'SELECT pg_sleep(1)', []);

		Tester\Assert::true(\pg_connection_busy($this->getConnectionResource()));

		\sleep(2);

		Tester\Assert::false(\pg_connection_busy($this->getConnectionResource()));
	}


	public function testAsyncPgQuery(): void
	{
		$success1 = \pg_send_query($this->getConnectionResource(), 'SELECT 1 AS clm1');

		Tester\Assert::true($success1);

		$result1 = \pg_get_result($this->getConnectionResource());

		Tester\Assert::same(['clm1' => '1'], \pg_fetch_assoc($result1));

		$success2 = \pg_send_query($this->getConnectionResource(), 'SELECT 1 AS clm1; SELECT 2 AS clm2');

		Tester\Assert::true($success2);

		$result2 = \pg_get_result($this->getConnectionResource());

		Tester\Assert::true(\is_resource($result2));
		Tester\Assert::same(['clm1' => '1'], \pg_fetch_assoc($result2));

		$result3 = \pg_get_result($this->getConnectionResource());

		Tester\Assert::true(\is_resource($result3));
		Tester\Assert::same(['clm2' => '2'], \pg_fetch_assoc($result3));

		$result4 = \pg_get_result($this->getConnectionResource());

		Tester\Assert::false(\is_resource($result4));
	}


	public function testAsyncPgQueryParams(): void
	{
		$success1 = \pg_send_query_params($this->getConnectionResource(), 'SELECT 1 AS clm1', []);

		Tester\Assert::true($success1);

		$result1 = \pg_get_result($this->getConnectionResource());

		Tester\Assert::same(['clm1' => '1'], \pg_fetch_assoc($result1));

		$success2 = \pg_send_query_params($this->getConnectionResource(), 'SELECT 1 AS clm1 WHERE 1 = $1', [1]);

		Tester\Assert::true($success2);

		$result2 = \pg_get_result($this->getConnectionResource());

		Tester\Assert::same(['clm1' => '1'], \pg_fetch_assoc($result2));

		$success3 = \pg_send_query_params($this->getConnectionResource(), 'SELECT 1 AS clm1 WHERE 1 = $1; SELECT 2 AS clm2 WHERE 2 = $2;', [1, 2]);

		Tester\Assert::true($success3);

		Tester\Assert::same('', \pg_last_error($this->getConnectionResource()));

		$result3 = \pg_get_result($this->getConnectionResource());

		// strange, error is filled after first get result
		Tester\Assert::contains('cannot insert multiple commands into a prepared statement', \pg_last_error($this->getConnectionResource()));

		Tester\Assert::true(\is_resource($result3));
		Tester\Assert::false(\pg_fetch_assoc($result3));

		$result4 = \pg_get_result($this->getConnectionResource());
		Tester\Assert::false($result4);
	}


	public function testCancelAsyncQuery(): void
	{
		// cancel after sync query - nothing is done, no error

		\pg_query($this->getConnectionResource(), 'SELECT 1');

		$success1 = \pg_cancel_query($this->getConnectionResource());

		Tester\Assert::true($success1);

		// cancel async query with got results - nothing is done, no error

		\pg_send_query($this->getConnectionResource(), 'SELECT 1 AS clm1');

		\pg_get_result($this->getConnectionResource());

		$success2 = \pg_cancel_query($this->getConnectionResource());

		Tester\Assert::true($success2);

		// can't run another async/sync query if there is waiting result from previous async query

		\pg_send_query($this->getConnectionResource(), 'SELECT 1 AS clm1; SELECT 2 AS clm2;');

		Tester\Assert::same('', \pg_last_error($this->getConnectionResource()));

		$result1 = @\pg_send_query($this->getConnectionResource(), 'SELECT 3 AS clm3'); // intentionally @ - `E_NOTICE: pg_send_query(): There are results on this connection. Call pg_get_result() until it returns FALSE`

		Tester\Assert::false($result1);

		// strange, error is filled after first get result
		Tester\Assert::contains('another command is already in progress', \pg_last_error($this->getConnectionResource()));

		$result2 = @\pg_query($this->getConnectionResource(), 'SELECT 3 AS clm3'); // intentionally @ - `E_NOTICE: pg_query(): Found results on this connection. Use pg_get_result() to get these results first`

		Tester\Assert::true(\is_resource($result2)); // but sync SELECT is done

		Tester\Assert::contains('', \pg_last_error($this->getConnectionResource()));

		Tester\Assert::same(['clm3' => '3'], \pg_fetch_assoc($result2));

		// and previous async results are discarded

		$result3 = \pg_get_result($this->getConnectionResource());
		Tester\Assert::false($result3);

		// cancel existing async before running new

		\pg_send_query($this->getConnectionResource(), 'SELECT 1 AS clm1; SELECT 2 AS clm2;');

		Tester\Assert::same('', \pg_last_error($this->getConnectionResource()));

		\pg_cancel_query($this->getConnectionResource());

		$success3 = \pg_send_query($this->getConnectionResource(), 'SELECT 3 AS clm3');

		Tester\Assert::true($success3);

		Tester\Assert::contains('', \pg_last_error($this->getConnectionResource()));

		$result4 = \pg_get_result($this->getConnectionResource());

		Tester\Assert::same(['clm3' => '3'], \pg_fetch_assoc($result4));
	}


	public function testPrepareStatements(): void
	{
		$resource1 = @\pg_prepare($this->getConnectionResource(), 'stm1', 'SELECTx 1 AS clm1'); // intentionally @ - E_WARNING: pg_prepare(): Query failed: ERROR:  syntax error at or near "SELECTx"
		Tester\Assert::false($resource1);
		Tester\Assert::contains('ERROR:  syntax error at or near "SELECTx"', \pg_last_error($this->getConnectionResource()));

		$resource2 = @\pg_prepare($this->getConnectionResource(), 'stm2', 'SELECT 1 AS clm1; SELECT 2 AS clm2'); // intentionally @ - E_WARNING: pg_prepare(): Query failed: ERROR:  cannot insert multiple commands into a prepared statement
		Tester\Assert::false($resource2);
		Tester\Assert::contains('ERROR:  cannot insert multiple commands into a prepared statement', \pg_last_error($this->getConnectionResource()));

		$resource3 = \pg_prepare($this->getConnectionResource(), 'stm3', 'SELECT 1 AS clm1');
		Tester\Assert::true(\is_resource($resource3));

		$result1 = \pg_execute($this->getConnectionResource(), 'stm3', []);
		Tester\Assert::same(['clm1' => '1'], \pg_fetch_assoc($result1));

		$result2 = \pg_execute($this->getConnectionResource(), 'stm3', []);
		Tester\Assert::same(['clm1' => '1'], \pg_fetch_assoc($result2));

		\pg_prepare($this->getConnectionResource(), 'stm4', 'SELECT 1 AS clm1 WHERE 1 = $1');

		$result3 = \pg_execute($this->getConnectionResource(), 'stm4', [1]);
		Tester\Assert::same(['clm1' => '1'], \pg_fetch_assoc($result3));

		$result4 = \pg_execute($this->getConnectionResource(), 'stm4', [0]);
		Tester\Assert::false(\pg_fetch_assoc($result4));

		$result5 = @\pg_execute($this->getConnectionResource(), 'stm4', [0, 1]); // intentionally @ - E_WARNING: pg_execute(): Query failed: ERROR:  bind message supplies 2 parameters, but prepared statement "stm4" requires 1
		Tester\Assert::false($result5);
		Tester\Assert::contains('ERROR:  bind message supplies 2 parameters, but prepared statement "stm4" requires 1', \pg_last_error($this->getConnectionResource()));

		$result6 = @\pg_execute($this->getConnectionResource(), 'stm5', []); // intentionally @ - E_WARNING: pg_execute(): Query failed: ERROR:  prepared statement "stm5" does not exist
		Tester\Assert::false($result6);
		Tester\Assert::contains('ERROR:  prepared statement "stm5" does not exist', \pg_last_error($this->getConnectionResource()));

		$resource4 = @\pg_prepare($this->getConnectionResource(), 'stm4', 'SELECT 1 AS clm1'); // intentionally @ - E_WARNING: pg_prepare(): Query failed: ERROR:  prepared statement "stm4" already exists
		Tester\Assert::false($resource4);
		Tester\Assert::contains('ERROR:  prepared statement "stm4" already exists', \pg_last_error($this->getConnectionResource()));

		// async

		$success1 = \pg_send_prepare($this->getConnectionResource(), 'stm6', 'SELECTx 1 AS clm1');
		Tester\Assert::true($success1);
		Tester\Assert::same('', \pg_last_error($this->getConnectionResource())); // no error until pg_get_result()

		$result7 = \pg_get_result($this->getConnectionResource());
		Tester\Assert::true(\is_resource($result7));
		Tester\Assert::same(\PGSQL_FATAL_ERROR, \pg_result_status($result7));
		Tester\Assert::contains('ERROR:  syntax error at or near "SELECTx"', \pg_last_error($this->getConnectionResource()));
		Tester\Assert::contains('ERROR:  syntax error at or near "SELECTx"', self::pgResultError($result7));

		\pg_send_prepare($this->getConnectionResource(), 'stm7', 'SELECT 1 AS clm1; SELECT 2 AS clm2');

		$result8 = \pg_get_result($this->getConnectionResource());
		Tester\Assert::true(\is_resource($result8));
		Tester\Assert::same(\PGSQL_FATAL_ERROR, \pg_result_status($result8));
		Tester\Assert::contains('ERROR:  cannot insert multiple commands into a prepared statement', \pg_last_error($this->getConnectionResource()));
		Tester\Assert::contains('ERROR:  cannot insert multiple commands into a prepared statement', self::pgResultError($result8));

		\pg_send_prepare($this->getConnectionResource(), 'stm8', 'SELECT 1 AS clm1 WHERE 1 = $1');

		$result9 = \pg_get_result($this->getConnectionResource());
		Tester\Assert::true(\is_resource($result9)); // we must call this after pg_send_result()

		$success2 = \pg_send_execute($this->getConnectionResource(), 'stm8', [1]);
		Tester\Assert::true($success2);

		$result10 = \pg_get_result($this->getConnectionResource());
		Tester\Assert::same(\PGSQL_TUPLES_OK, \pg_result_status($result10));
		Tester\Assert::same(['clm1' => '1'], \pg_fetch_assoc($result10));

		$success3 = \pg_send_execute($this->getConnectionResource(), 'stm8', [0]);
		Tester\Assert::true($success3);

		$result11 = \pg_get_result($this->getConnectionResource());
		Tester\Assert::false(\pg_fetch_assoc($result11));

		$success4 = \pg_send_execute($this->getConnectionResource(), 'stm8', [0, 1]);
		Tester\Assert::true($success4);

		$result12 = \pg_get_result($this->getConnectionResource());
		Tester\Assert::same(\PGSQL_FATAL_ERROR, \pg_result_status($result12));
		Tester\Assert::contains('ERROR:  bind message supplies 2 parameters, but prepared statement "stm8" requires 1', \pg_last_error($this->getConnectionResource()));
		Tester\Assert::contains('ERROR:  bind message supplies 2 parameters, but prepared statement "stm8" requires 1', self::pgResultError($result12));
	}


	/**
	 * @return resource
	 */
	private function getConnectionResource()
	{
		return $this->connection->getResource();
	}


	/**
	 * @param resource|FALSE $result
	 */
	private static function pgResultError($result): string
	{
		$error = \pg_result_error($result);
		if ($error === FALSE) {
			throw new \RuntimeException('pg_result_error contains no error');
		}
		return $error;
	}

}

(new PgFunctionsTest())->run();
