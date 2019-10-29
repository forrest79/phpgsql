<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Tests\Integration;

use Forrest79\PhPgSql\Db;
use Tester;

require_once __DIR__ . '/TestCase.php';

/**
 * @testCase
 */
class TransactionsTest extends TestCase
{
	/** @var Db\Transactions */
	protected $transactions;


	protected function setUp(): void
	{
		parent::setUp();
		$this->transactions = $this->connection->transactions();
	}


	public function testTransactions(): void
	{
		$this->transactions->begin();

		Tester\Assert::true($this->connection->isInTransaction());
		Tester\Assert::same(1, $this->connection->query('SELECT 1')->fetchSingle());

		$this->transactions->commit();

		Tester\Assert::false($this->transactions->isInTransaction());

		$this->transactions->begin();

		Tester\Assert::true($this->transactions->isInTransaction());
		Tester\Assert::same(1, $this->connection->query('SELECT 1')->fetchSingle());

		$this->transactions->rollback();
	}


	public function testTransactionsWithMode(): void
	{
		$this->transactions->begin('ISOLATION LEVEL REPEATABLE READ');

		Tester\Assert::true($this->transactions->isInTransaction());
		Tester\Assert::same(1, $this->connection->query('SELECT 1')->fetchSingle());

		$this->transactions->commit();
	}


	public function testSavepoints(): void
	{
		$this->transactions->begin();

		$this->transactions->savepoint('test');

		Tester\Assert::true($this->transactions->isInTransaction());
		Tester\Assert::same(1, $this->connection->query('SELECT 1')->fetchSingle());

		$this->transactions->releaseSavepoint('test');

		$this->transactions->commit();

		Tester\Assert::false($this->transactions->isInTransaction());

		$this->transactions->begin();

		$this->transactions->savepoint('test');

		Tester\Assert::true($this->transactions->isInTransaction());
		Tester\Assert::same(1, $this->connection->query('SELECT 1')->fetchSingle());

		$this->transactions->rollbackToSavepoint('test');

		$this->transactions->rollback();
	}

}

(new TransactionsTest())->run();
