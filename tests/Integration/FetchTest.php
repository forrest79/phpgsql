<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Tests\Integration;

use Forrest79\PhPgSql\Db;
use Tester;

require_once __DIR__ . '/TestCase.php';

/**
 * @testCase
 */
class FetchTest extends TestCase
{

	public function testFetch(): void
	{
		$this->connection->query('
			CREATE TABLE test(
				id serial,
  				name text
			);
		');
		$this->connection->query('INSERT INTO test(name) VALUES(?)', 'phpgsql');

		$result = $this->connection->query('SELECT id, name FROM test');

		$row = $this->fetch($result);

		Tester\Assert::same(1, $row->id);
		Tester\Assert::same('phpgsql', $row->name);

		$result->free();
	}


	public function testFetchSingle(): void
	{
		$this->connection->query('
			CREATE TABLE test(
				id integer
			);
		');
		$this->connection->query('INSERT INTO test(id) VALUES(?)', 999);

		$result = $this->connection->query('SELECT id FROM test');

		$id = $result->fetchSingle();

		Tester\Assert::same(999, $id);

		$result->free();
	}


	public function testFetchAll(): void
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


	public function testFetchAssocSimple(): void
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


	public function testFetchAssocArray(): void
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


	public function testFetchAssocPipe(): void
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


	public function testFetchAssocValue(): void
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


	public function testFetchAssocNoColumn(): void
	{
		$this->connection->query('
			CREATE TABLE test(
				id serial,
				type integer
			);
		');
		$this->connection->query('INSERT INTO test(type) SELECT generate_series FROM generate_series(3, 1, -1)');

		$result = $this->connection->query('SELECT id, type FROM test ORDER BY id');

		Tester\Assert::exception(static function () use ($result): void {
			$result->fetchAssoc('id=types');
		}, Db\Exceptions\ResultException::class, NULL, Db\Exceptions\ResultException::NO_COLUMN);

		$result->free();
	}


	public function testFetchAssocBlank(): void
	{
		$this->connection->query('
			CREATE TABLE test(
				id serial,
				type integer,
				name character varying
			);
		');
		$this->connection->query('INSERT INTO test(type, name) SELECT generate_series, \'test\' || generate_series FROM generate_series(2, 1, -1)');

		$result = $this->connection->query('SELECT id, type, name FROM test ORDER BY id');

		$rows = $result->fetchAssoc('');

		Tester\Assert::same(2, \count($rows));

		Tester\Assert::same(2, $rows[0]->type);
		Tester\Assert::same('test2', $rows[0]->name);
		Tester\Assert::same(1, $rows[1]->type);
		Tester\Assert::same('test1', $rows[1]->name);

		$result->free();
	}


	public function testFetchPairs(): void
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


	public function testFetchPairsOnlyOneColumn(): void
	{
		$this->connection->query('
			CREATE TABLE test(
				id serial,
				name character varying
			);
		');
		$this->connection->query('INSERT INTO test(name) SELECT \'name\' || generate_series FROM generate_series(3, 1, -1)');

		$result = $this->connection->query('SELECT id, name FROM test ORDER BY id');

		Tester\Assert::exception(static function () use ($result): void {
			$result->fetchPairs('name');
		}, Db\Exceptions\ResultException::class, NULL, Db\Exceptions\ResultException::FETCH_PAIRS_FAILED);

		$result->free();
	}


	public function testFetchPairsIndexedArray(): void
	{
		$this->connection->query('
			CREATE TABLE test(
				id serial,
				type integer
			);
		');
		$this->connection->query('INSERT INTO test(type) SELECT generate_series FROM generate_series(3, 1, -1)');

		$result = $this->connection->query('SELECT type FROM test ORDER BY id');

		$rows = $result->fetchPairs();

		Tester\Assert::same([3, 2, 1], $rows);

		$rows = $result->fetchPairs(NULL, 'type');

		Tester\Assert::same([3, 2, 1], $rows);

		$result->free();
	}


	public function testFetchRowAsJson(): void
	{
		$result = $this->connection->query('SELECT 1 AS number_column, \'test\'::text AS text_column, TRUE AS boolean_column, NULL AS null_column');

		$row = $result->fetch();

		Tester\Assert::same(
			'{"number_column":1,"text_column":"test","boolean_column":true,"null_column":null}',
			\json_encode($row)
		);

		$result->free();
	}


	public function testFetchBadOffset(): void
	{
		$result = $this->connection->query('SELECT 1');

		$row = $result->fetch();
		if ($row === NULL) {
			throw new \RuntimeException('No data from database were returned');
		}

		Tester\Assert::exception(static function () use ($row): void {
			$row[1];
		}, Db\Exceptions\RowException::class, NULL, Db\Exceptions\RowException::NOT_STRING_KEY);

		Tester\Assert::exception(static function () use ($row): void {
			$row[1] = 'value';
		}, Db\Exceptions\RowException::class, NULL, Db\Exceptions\RowException::NOT_STRING_KEY);

		Tester\Assert::exception(static function () use ($row): void {
			isset($row[1]);
		}, Db\Exceptions\RowException::class, NULL, Db\Exceptions\RowException::NOT_STRING_KEY);

		Tester\Assert::exception(static function () use ($row): void {
			unset($row[1]);
		}, Db\Exceptions\RowException::class, NULL, Db\Exceptions\RowException::NOT_STRING_KEY);

		$result->free();
	}


	public function testFetchPairsBadKeyOrValue(): void
	{
		$this->connection->query('
			CREATE TABLE test(
				id serial,
				name character varying
			);
		');
		$this->connection->query('INSERT INTO test(name) SELECT \'name\' || generate_series FROM generate_series(3, 1, -1)');

		$result = $this->connection->query('SELECT id, name FROM test ORDER BY id');

		// Bad key
		Tester\Assert::exception(static function () use ($result): void {
			$result->fetchPairs('type', 'name');
		}, Db\Exceptions\ResultException::class, NULL, Db\Exceptions\ResultException::NO_COLUMN);

		// Bad value
		Tester\Assert::exception(static function () use ($result): void {
			$result->fetchPairs('id', 'type');
		}, Db\Exceptions\ResultException::class, NULL, Db\Exceptions\ResultException::NO_COLUMN);

		$result->free();
	}


	public function testFetchNoColumn(): void
	{
		$this->connection->query('
			CREATE TABLE test(
				id serial,
  				name text
			);
		');
		$this->connection->query('INSERT INTO test(name) VALUES(?)', 'phpgsql');

		$result = $this->connection->query('SELECT id, name FROM test');

		$row = $this->fetch($result);

		Tester\Assert::exception(static function () use ($row): void {
			$row->cnt;
		}, Db\Exceptions\RowException::class, NULL, Db\Exceptions\RowException::NO_COLUMN);

		$result->free();
	}


	public function testResultIterator(): void
	{
		$this->connection->query('
			CREATE TABLE test(
				id serial,
				name character varying
			);
		');
		$this->connection->query('INSERT INTO test(name) SELECT \'name\' || generate_series FROM generate_series(3, 1, -1)');

		$result = $this->connection->query('SELECT id, name FROM test ORDER BY id');

		Tester\Assert::same(3, \count($result));

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


	public function testAffectedRows(): void
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


	public function testGetColumns(): void
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


	public function testResultColumnType(): void
	{
		$this->connection->query('
			CREATE TABLE test(
				id serial,
  				name text
			);
		');
		$this->connection->query('INSERT INTO test(name) VALUES(?)', 'phpgsql');

		$result = $this->connection->query('SELECT id, name FROM test');

		Tester\Assert::same('text', $result->getColumnType('name'));

		$result->free();
	}


	public function testResultNoColumnForType(): void
	{
		$this->connection->query('
			CREATE TABLE test(
				id serial,
  				name text
			);
		');
		$this->connection->query('INSERT INTO test(name) VALUES(?)', 'phpgsql');

		$result = $this->connection->query('SELECT id, name FROM test');

		Tester\Assert::exception(static function () use ($result): void {
			$result->getColumnType('count');
		}, Db\Exceptions\ResultException::class, NULL, Db\Exceptions\ResultException::NO_COLUMN);

		$result->free();
	}


	public function testResultColumnIsAlreadyInUse(): void
	{
		$result = $this->connection->query('SELECT 1 AS column, 2 AS column');

		Tester\Assert::exception(static function () use ($result): void {
			/** @var Db\Row $row */
			$row = $result->fetch();
			$row->column;
		}, Db\Exceptions\ResultException::class, NULL, Db\Exceptions\ResultException::COLUMN_NAME_IS_ALREADY_IN_USE);

		$result->free();
	}


	public function testCustomRowFactoryOnConnection(): void
	{
		$this->connection->setDefaultRowFactory($this->createCustomRowFactory());

		$this->connection->query('
			CREATE TABLE test(
				id serial,
  				name text
			);
		');
		$this->connection->query('INSERT INTO test(name) VALUES(?)', 'phpgsql');

		$result = $this->connection->query('SELECT id, name FROM test');

		$row = $this->fetch($result);

		Tester\Assert::same('custom', $row->name);

		$result->free();
	}


	public function testCustomRowFactoryOnResult(): void
	{
		$this->connection->query('
			CREATE TABLE test(
				id serial,
  				name text
			);
		');
		$this->connection->query('INSERT INTO test(name) VALUES(?)', 'phpgsql');

		$result = $this->connection->query('SELECT id, name FROM test');
		$result->setRowFactory($this->createCustomRowFactory());

		$row = $this->fetch($result);

		Tester\Assert::same('custom', $row->name);

		$result->free();
	}


	public function testNoResults(): void
	{
		Tester\Assert::null($this->connection->query('SELECT 1 WHERE FALSE')->fetchSingle());
		Tester\Assert::same([], $this->connection->query('SELECT 1 WHERE FALSE')->fetchAll());
		Tester\Assert::same([], $this->connection->query('SELECT 1 WHERE FALSE')->fetchAssoc('column'));
		Tester\Assert::same([], $this->connection->query('SELECT 1 WHERE FALSE')->fetchPairs());
	}


	public function testRowValues(): void
	{
		$this->connection->query('
			CREATE TABLE test(
				id serial,
  				name text
			);
		');
		$this->connection->query('INSERT INTO test(name) VALUES(?)', 'phpgsql');

		$result = $this->connection->query('SELECT id, name FROM test');

		$row = $this->fetch($result);

		Tester\Assert::same($result, $row->getResult());

		Tester\Assert::same('phpgsql', $row->name);

		Tester\Assert::false(isset($row->type));

		Tester\Assert::false(isset($row['another_type']));

		Tester\Assert::exception(static function () use ($row): void {
			$row->type;
		}, Db\Exceptions\RowException::class, NULL, Db\Exceptions\RowException::NO_COLUMN);

		Tester\Assert::exception(static function () use ($row): void {
			$row['another_type'];
		}, Db\Exceptions\RowException::class, NULL, Db\Exceptions\RowException::NO_COLUMN);

		$row->type = 'test';

		Tester\Assert::true(isset($row->type));

		Tester\Assert::same('test', $row->type);

		$row['another_type'] = 'another_test';

		Tester\Assert::true(isset($row['another_type']));

		Tester\Assert::same('another_test', $row['another_type']);

		unset($row->type);

		Tester\Assert::false(isset($row->type));

		Tester\Assert::exception(static function () use ($row): void {
			$row->type;
		}, Db\Exceptions\RowException::class, NULL, Db\Exceptions\RowException::NO_COLUMN);

		unset($row['another_type']);

		Tester\Assert::false(isset($row['another_type']));

		Tester\Assert::exception(static function () use ($row): void {
			$row['another_type'];
		}, Db\Exceptions\RowException::class, NULL, Db\Exceptions\RowException::NO_COLUMN);

		unset($row->name);

		foreach ($row as $key => $value) {
			Tester\Assert::same('id', $key);
			Tester\Assert::same(1, $value);
		}

		$result->free();
	}


	private function fetch(Db\Result $result): Db\Row
	{
		$row = $result->fetch();
		if ($row === NULL) {
			throw new \RuntimeException('No data from database were returned');
		}
		return $row;
	}


	private function createCustomRowFactory(): Db\RowFactory
	{
		return new class implements Db\RowFactory {

			/**
			 * @param array<string, mixed> $values
			 */
			public function createRow(Db\Result $result, array $values): Db\Row
			{
				return new Db\Row($result, ['name' => 'custom']);
			}

		};
	}

}

\run(FetchTest::class);
