<?php declare(strict_types=1);

namespace Tests\Integration\Forrest79\PhPgSql\Db;

use Forrest79\PhPgSql\Db;
use Tester;

require_once __DIR__ . '/TestCase.php';

/**
 * @testCase
 */
class FetchTest extends TestCase
{
	/** @var Db\Connection */
	private $connection;


	/**
	 * @throws Db\Exceptions\ConnectionException
	 * @throws Db\Exceptions\QueryException
	 */
	protected function setUp(): void
	{
		parent::setUp();
		$this->connection = new Db\Connection(sprintf('%s dbname=%s', $this->getConfig(), $this->getTableName()));
		$this->connection->connect();
	}


	public function testFetch()
	{
		$this->connection->query('
			CREATE TABLE test(
				id serial,
  				name text
			);
		');
		$this->connection->query('INSERT INTO test(name) VALUES(?)', 'phpgsql');

		$result = $this->connection->query('SELECT id, name FROM test');

		$row = $result->fetch();

		$result->free();

		Tester\Assert::same(1, $row->id);
		Tester\Assert::same('phpgsql', $row->name);
	}


	public function testFetchSingle()
	{
		$this->connection->query('
			CREATE TABLE test(
				id integer
			);
		');
		$this->connection->query('INSERT INTO test(id) VALUES(?)', 999);

		$result = $this->connection->query('SELECT id FROM test');

		$id = $result->fetchSingle();

		$result->free();

		Tester\Assert::same(999, $id);
	}


	public function testFetchAll()
	{
		$this->connection->query('
			CREATE TABLE test(
				id serial,
				type integer,
				name character varying
			);
		');
		$this->connection->query('INSERT INTO test(type, name) SELECT generate_series, \'name\' || generate_series FROM generate_series(3, 1, -1)');

		$result = $this->connection->query('SELECT id, type, name FROM test ORDER BY id');

		Tester\Assert::same(3, $result->count());

		$rows = $result->fetchAll();

		Tester\Assert::same(['id' => 1, 'type' => 3, 'name' => 'name3'], $rows[0]->toArray());
		Tester\Assert::same(['id' => 2, 'type' => 2, 'name' => 'name2'], $rows[1]->toArray());
		Tester\Assert::same(['id' => 3, 'type' => 1, 'name' => 'name1'], $rows[2]->toArray());

		$rows = $result->fetchAll(1);

		Tester\Assert::same(['id' => 2, 'type' => 2, 'name' => 'name2'], $rows[0]->toArray());
		Tester\Assert::same(['id' => 3, 'type' => 1, 'name' => 'name1'], $rows[1]->toArray());

		$rows = $result->fetchAll(1, 1);

		Tester\Assert::same(['id' => 2, 'type' => 2, 'name' => 'name2'], $rows[0]->toArray());

		$result->free();
	}


	public function testFetchAssocSimple()
	{
		$this->connection->query('
			CREATE TABLE test(
				id serial,
				type integer,
				name character varying
			);
		');
		$this->connection->query('INSERT INTO test(type, name) SELECT generate_series, \'name\' || generate_series FROM generate_series(3, 1, -1)');

		$result = $this->connection->query('SELECT id, type, name FROM test ORDER BY id');

		$rows = $result->fetchAssoc('type');

		Tester\Assert::same(['id' => 1, 'type' => 3, 'name' => 'name3'], $rows[3]->toArray());
		Tester\Assert::same(['id' => 2, 'type' => 2, 'name' => 'name2'], $rows[2]->toArray());
		Tester\Assert::same(['id' => 3, 'type' => 1, 'name' => 'name1'], $rows[1]->toArray());

		$result->free();
	}


	public function testFetchAssocArray()
	{
		$this->connection->query('
			CREATE TABLE test(
				id serial,
				type integer,
				name character varying
			);
		');
		$this->connection->query('INSERT INTO test(type, name) SELECT generate_series, \'test\' FROM generate_series(3, 1, -1)');

		$result = $this->connection->query('SELECT id, type, name FROM test ORDER BY id');

		$rows = $result->fetchAssoc('name[]');

		Tester\Assert::same(['id' => 1, 'type' => 3, 'name' => 'test'], $rows['test'][0]->toArray());
		Tester\Assert::same(['id' => 2, 'type' => 2, 'name' => 'test'], $rows['test'][1]->toArray());
		Tester\Assert::same(['id' => 3, 'type' => 1, 'name' => 'test'], $rows['test'][2]->toArray());

		$result->free();
	}


	public function testFetchAssocPipe()
	{
		$this->connection->query('
			CREATE TABLE test(
				id serial,
				type integer,
				name character varying
			);
		');
		$this->connection->query('INSERT INTO test(type, name) SELECT generate_series, \'test\' FROM generate_series(3, 1, -1)');

		$result = $this->connection->query('SELECT id, type, name FROM test ORDER BY id');

		$rows = $result->fetchAssoc('name|type');

		Tester\Assert::same(['id' => 1, 'type' => 3, 'name' => 'test'], $rows['test'][3]->toArray());
		Tester\Assert::same(['id' => 2, 'type' => 2, 'name' => 'test'], $rows['test'][2]->toArray());
		Tester\Assert::same(['id' => 3, 'type' => 1, 'name' => 'test'], $rows['test'][1]->toArray());

		$result->free();

		// ---

		$result = $this->connection->query('SELECT id, type, name FROM test ORDER BY id');

		$rows = $result->fetchAssoc('name|type[]');

		Tester\Assert::same(['id' => 1, 'type' => 3, 'name' => 'test'], $rows['test'][3][0]->toArray());
		Tester\Assert::same(['id' => 2, 'type' => 2, 'name' => 'test'], $rows['test'][2][0]->toArray());
		Tester\Assert::same(['id' => 3, 'type' => 1, 'name' => 'test'], $rows['test'][1][0]->toArray());

		$result->free();
	}


	public function testFetchAssocValue()
	{
		$this->connection->query('
			CREATE TABLE test(
				id serial,
				type integer,
				name character varying
			);
		');
		$this->connection->query('INSERT INTO test(type, name) SELECT generate_series, \'test\' || generate_series FROM generate_series(3, 1, -1)');

		$result = $this->connection->query('SELECT id, type, name FROM test ORDER BY id');

		$rows = $result->fetchAssoc('type|id=name');

		Tester\Assert::same('test3', $rows[3][1]);
		Tester\Assert::same('test2', $rows[2][2]);
		Tester\Assert::same('test1', $rows[1][3]);

		$result->free();
	}


	public function testFetchPairs()
	{
		$this->connection->query('
			CREATE TABLE test(
				id serial,
				name character varying
			);
		');
		$this->connection->query('INSERT INTO test(name) SELECT \'name\' || generate_series FROM generate_series(3, 1, -1)');

		$result = $this->connection->query('SELECT id, name FROM test ORDER BY id');

		$rows = $result->fetchPairs();

		Tester\Assert::same([1 => 'name3', 2 => 'name2', 3 => 'name1'], $rows);

		$rows = $result->fetchPairs('name', 'id');

		Tester\Assert::same(['name3' => 1, 'name2' => 2, 'name1' => 3], $rows);

		$result->free();
	}


	public function testResultIterator()
	{
		$this->connection->query('
			CREATE TABLE test(
				id serial,
				name character varying
			);
		');
		$this->connection->query('INSERT INTO test(name) SELECT \'name\' || generate_series FROM generate_series(3, 1, -1)');

		$result = $this->connection->query('SELECT id, name FROM test ORDER BY id');

		Tester\Assert::same(3, count($result));

		$expected = [
			['id' => 1, 'name' => 'name3'],
			['id' => 2, 'name' => 'name2'],
			['id' => 3, 'name' => 'name1'],
		];
		foreach ($result as $i => $row) {
			Tester\Assert::same($expected[$i], $row->toArray());
		}

		$result->free();
	}


	public function testGetColumns()
	{
		$this->connection->query('
			CREATE TABLE test(
				id serial,
  				name text
			);
		');
		$result = $this->connection->query('INSERT INTO test(name) SELECT \'name\' || generate_series FROM generate_series(3, 1, -1)');

		Tester\Assert::same(3, $result->getAffectedRows());

		$result->free();
	}


	public function testAffectedRows()
	{
		$this->connection->query('
			CREATE TABLE test(
				id serial,
  				name text
			);
		');
		$this->connection->query('INSERT INTO test(name) VALUES(?)', 'phpgsql');

		$result = $this->connection->query('SELECT id, name FROM test');

		Tester\Assert::same(['id', 'name'], $result->getColumns());

		$result->free();
	}


	protected function tearDown(): void
	{
		$this->connection->close();
		parent::tearDown();
	}

}

(new FetchTest)->run();
