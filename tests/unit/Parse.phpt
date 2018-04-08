<?php declare(strict_types=1);

namespace Tests\Unit\Forrest79\PhPgSql\Db;

use Forrest79\PhPgSql\Db;
use Tester;

require_once __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
class Parse extends Tester\TestCase
{
	/** @var Db\Connection */
	private $connection;


	/**
	 * @throws Db\Exceptions\ConnectionException
	 */
	protected function setUp()
	{
		parent::setUp();
		$this->connection = new Db\Connection;
	}


	public function testPrepareQuery()
	{
		$query = $this->connection->prepareQuery('SELECT * FROM table');
		Tester\Assert::same('SELECT * FROM table', $query->getSql());
		Tester\Assert::same([], $query->getParams());
	}


	public function testPrepareQueryWithParams()
	{
		$query = $this->connection->prepareQuery('SELECT * FROM table WHERE column = $1', 1);
		Tester\Assert::same('SELECT * FROM table WHERE column = $1', $query->getSql());
		Tester\Assert::same([1], $query->getParams());

		$query = $this->connection->prepareQueryArray('SELECT * FROM table WHERE column = $1', [1]);
		Tester\Assert::same('SELECT * FROM table WHERE column = $1', $query->getSql());
		Tester\Assert::same([1], $query->getParams());

		$query = $this->connection->prepareQuery('SELECT * FROM table WHERE column = ?', 1);
		Tester\Assert::same('SELECT * FROM table WHERE column = $1', $query->getSql());
		Tester\Assert::same([1], $query->getParams());

		$query = $this->connection->prepareQueryArray('SELECT * FROM table WHERE column = ?', [1]);
		Tester\Assert::same('SELECT * FROM table WHERE column = $1', $query->getSql());
		Tester\Assert::same([1], $query->getParams());
	}


	public function testPrepareQueryWithBadParams()
	{
		Tester\Assert::exception(function() {
			$query = $this->connection->prepareQuery('SELECT * FROM table WHERE column = ? AND column2 = ?', 1);
			$query->getSql();
		}, Db\Exceptions\QueryException::class);
	}


	public function testPrepareQueryWithLiteral()
	{
		$query = $this->connection->prepareQuery('SELECT * FROM ? WHERE column = ?', $this->connection::literal('table'), 1);
		Tester\Assert::same('SELECT * FROM table WHERE column = $1', $query->getSql());
		Tester\Assert::same([1], $query->getParams());
	}


	public function testPrepareQueryWithArray()
	{
		$query = $this->connection->prepareQuery('SELECT * FROM table WHERE column IN (?)', [1, 2]);
		Tester\Assert::same('SELECT * FROM table WHERE column IN ($1, $2)', $query->getSql());
		Tester\Assert::same([1, 2], $query->getParams());
	}


	public function testPrepareQueryWithQuery()
	{
		$subquery = $this->connection->prepareQuery('SELECT id FROM subtable WHERE column = ?', 1);
		$query = $this->connection->prepareQuery('SELECT * FROM table WHERE id IN (?)', $subquery);
		Tester\Assert::same('SELECT * FROM table WHERE id IN (SELECT id FROM subtable WHERE column = $1)', $query->getSql());
		Tester\Assert::same([1], $query->getParams());
	}


	public function testPrepareQueryEscapeQuestionmark()
	{
		$query = $this->connection->prepareQuery('SELECT * FROM table WHERE column = ? AND text ILIKE \'What\?\'', 1);
		Tester\Assert::same('SELECT * FROM table WHERE column = $1 AND text ILIKE \'What?\'', $query->getSql());
		Tester\Assert::same([1], $query->getParams());
	}


	public function testPrepareQueryComplex()
	{
		$subquery = $this->connection->prepareQuery(
			'SELECT id FROM subtable WHERE when = ? AND text ILIKE \'When\?\' AND year > ?',
			$this->connection::literal('now()'), 2005
		);
		$query = $this->connection->prepareQuery(
			'SELECT * FROM table WHERE column = ? OR id IN (?) OR type IN (?)',
			'yes', $subquery, [3, 2, 1]
		);
		Tester\Assert::same('SELECT * FROM table WHERE column = $1 OR id IN (SELECT id FROM subtable WHERE when = now() AND text ILIKE \'When?\' AND year > $2) OR type IN ($3, $4, $5)', $query->getSql());
		Tester\Assert::same(['yes', 2005, 3, 2, 1], $query->getParams());
	}

}

(new Parse)->run();
