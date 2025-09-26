<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Tests\Integration;

use Forrest79\PhPgSql\Db;
use Forrest79\PhPgSql\Fluent;
use Tester;

require_once __DIR__ . '/TestCase.php';

/**
 * @testCase
 */
final class FetchMutatorTest extends TestCase
{

	public function testFetchRowMutator(): void
	{
		$this->connection->query('
			CREATE TABLE test(
				id serial,
  				name text
			);
		');

		$this->connection->query('INSERT INTO test(name) VALUES(?)', 'phpgsql');

		$result = $this->connection
			->query('SELECT id, name FROM test')
			->setRowFetchMutator(static function (Db\Row $row): void {
				$row->new_column = $row->id . '-' . $row->name;
			});

		$row = $result->fetch();
		if ($row === null) {
			throw new \RuntimeException('No data from database were returned');
		}

		Tester\Assert::same(1, $row->id);
		Tester\Assert::same('phpgsql', $row->name);
		Tester\Assert::same('1-phpgsql', $row->new_column);

		$result->free();
	}


	public function testFetchAllRowMutator(): void
	{
		$this->connection->query('
			CREATE TABLE test(
				id serial,
				name character varying
			);
		');

		$this->connection->query('INSERT INTO test(name) SELECT \'name\' || generate_series FROM generate_series(3, 1, -1)');

		$result = $this->connection
			->query('SELECT id, name FROM test ORDER BY id')
			->setRowFetchMutator(static function (Db\Row $row): void {
				$row->new_column = $row->id . '-' . $row->name;
				$row->repeat = ($row->repeat ?? 0) + 1;
			});

		$rows = $result->fetchAll();

		Tester\Assert::same(['id' => 1, 'name' => 'name3', 'new_column' => '1-name3', 'repeat' => 1], $rows[0]->toArray());
		Tester\Assert::same(['id' => 2, 'name' => 'name2', 'new_column' => '2-name2', 'repeat' => 1], $rows[1]->toArray());
		Tester\Assert::same(['id' => 3, 'name' => 'name1', 'new_column' => '3-name1', 'repeat' => 1], $rows[2]->toArray());

		$rows = $result->fetchAll(1);

		Tester\Assert::same(['id' => 2, 'name' => 'name2', 'new_column' => '2-name2', 'repeat' => 1], $rows[0]->toArray());
		Tester\Assert::same(['id' => 3, 'name' => 'name1', 'new_column' => '3-name1', 'repeat' => 1], $rows[1]->toArray());

		$rows = $result->fetchAll(1, 1);

		Tester\Assert::same(['id' => 2, 'name' => 'name2', 'new_column' => '2-name2', 'repeat' => 1], $rows[0]->toArray());

		$result->free();
	}


	public function testFetchSingleColumnMutator(): void
	{
		$this->connection->query('
			CREATE TABLE test(
				id serial,
  				name text
			);
		');

		$this->connection->query('INSERT INTO test(name) VALUES(?)', 'phpgsql');

		$result = $this->connection
			->query('SELECT name FROM test')
			->setColumnsFetchMutator([
				'name' => static function (string $name): string {
					return \strtoupper($name);
				},
			]);

		Tester\Assert::same('PHPGSQL', $result->fetchSingle());

		$result->free();
	}


	public function testFetchSingleRowBeforeColumnMutator(): void
	{
		$this->connection->query('
			CREATE TABLE test(
				id serial,
  				name text
			);
		');

		$this->connection->query('INSERT INTO test(name) VALUES(?)', 'phpgsql');

		$result = $this->connection
			->query('SELECT name FROM test')
			->setRowFetchMutator(static function (Db\Row $row): void {
				$row->name .= '-rowMutated';
			})
			->setColumnsFetchMutator([
				'name' => static function (string $name): string {
					return \strtoupper($name);
				},
			]);

		Tester\Assert::same('PHPGSQL-ROWMUTATED', $result->fetchSingle());

		$result->free();
	}


	public function testFetchAssocColumnMutator(): void
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

		$rows = $result
			->setColumnsFetchMutator([
				'type' => static function (int $type): string {
					return 'type' . $type;
				},
			])
			->fetchAssoc('type');

		Tester\Assert::same(['id' => 1, 'type' => 3, 'name' => 'name3'], $rows['type3']->toArray());
		Tester\Assert::same(['id' => 2, 'type' => 2, 'name' => 'name2'], $rows['type2']->toArray());
		Tester\Assert::same(['id' => 3, 'type' => 1, 'name' => 'name1'], $rows['type1']->toArray());

		$result->free();
	}


	public function testFetchAssocColumnMutatorObjectAsKey(): void
	{
		$this->connection->query('
			CREATE TABLE test(
				id serial,
				test_date date
			);
		');

		$this->connection->query('INSERT INTO test(test_date) VALUES(\'2021-04-20\')');

		$result = $this->connection->query('SELECT id, test_date FROM test');

		$rows = $result
			->setColumnsFetchMutator([
				'test_date' => static function (\DateTimeImmutable $date): string {
					return $date->format('Ymd');
				},
			])
			->fetchAssoc('test_date=id');

		Tester\Assert::same(1, $rows['20210420']);

		$result->free();
	}


	public function testFetchAssocColumnMutatorMutatedObjectAsKey(): void
	{
		$this->connection->query('
			CREATE TABLE test(
				id serial,
				test_date date
			);
		');

		$this->connection->query('INSERT INTO test(test_date) VALUES(CURRENT_DATE)');

		$result = $this->connection
			->query('SELECT id, test_date FROM test')
			->setColumnsFetchMutator([
				'test_date' => static function (\DateTimeImmutable $date): \DateTimeImmutable {
					return $date;
				},
			]);

		Tester\Assert::exception(static function () use ($result): void {
			$result->fetchAssoc('test_date=id');
		}, Db\Exceptions\ResultException::class, code: Db\Exceptions\ResultException::FETCH_MUTATOR_BAR_RETURN_TYPE);

		$result->free();
	}


	public function testFetchAssocRowMutator(): void
	{
		$this->connection->query('
			CREATE TABLE test(
				id serial,
				type integer,
				name character varying
			);
		');

		$this->connection->query('INSERT INTO test(type, name) SELECT generate_series, \'name\' || generate_series FROM generate_series(3, 1, -1)');

		$result1 = $this->connection->query('SELECT id, type, name FROM test ORDER BY id');

		$rows1 = $result1
			->setRowFetchMutator(static function (Db\Row $row): void {
				$row->new_column = $row->id . '-' . $row->name;
				$row->repeat = ($row->repeat ?? 0) + 1;
			})
			->fetchAssoc('type');

		Tester\Assert::same(['id' => 1, 'type' => 3, 'name' => 'name3', 'new_column' => '1-name3', 'repeat' => 1], $rows1[3]->toArray());
		Tester\Assert::same(['id' => 2, 'type' => 2, 'name' => 'name2', 'new_column' => '2-name2', 'repeat' => 1], $rows1[2]->toArray());
		Tester\Assert::same(['id' => 3, 'type' => 1, 'name' => 'name1', 'new_column' => '3-name1', 'repeat' => 1], $rows1[1]->toArray());

		$result1->free();

		// ---

		$result2 = $this->connection->query('SELECT id, type, name FROM test ORDER BY id');

		$rows2 = $result2
			->setRowFetchMutator(static function (Db\Row $row): void {
				$row->new_column = $row->id . '-' . $row->name;
				$row->repeat = ($row->repeat ?? 0) + 1;
			})
			->fetchAssoc('type=[]');

		Tester\Assert::same(['id' => 1, 'type' => 3, 'name' => 'name3', 'new_column' => '1-name3', 'repeat' => 1], $rows2[3]);
		Tester\Assert::same(['id' => 2, 'type' => 2, 'name' => 'name2', 'new_column' => '2-name2', 'repeat' => 1], $rows2[2]);
		Tester\Assert::same(['id' => 3, 'type' => 1, 'name' => 'name1', 'new_column' => '3-name1', 'repeat' => 1], $rows2[1]);

		$result2->free();
	}


	public function testFetchAssocRowAndColumnMutator(): void
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

		$rows = $result
			->setRowFetchMutator(static function (Db\Row $row): void {
				$row->new_column = $row->id . '-' . $row->name;
			})
			->setColumnsFetchMutator([
				'type' => static function (int $type): string {
					return 'type' . $type;
				},
			])
			->fetchAssoc('type');

		Tester\Assert::same(['id' => 1, 'type' => 3, 'name' => 'name3', 'new_column' => '1-name3'], $rows['type3']->toArray());
		Tester\Assert::same(['id' => 2, 'type' => 2, 'name' => 'name2', 'new_column' => '2-name2'], $rows['type2']->toArray());
		Tester\Assert::same(['id' => 3, 'type' => 1, 'name' => 'name1', 'new_column' => '3-name1'], $rows['type1']->toArray());

		$result->free();
	}


	public function testFetchAssocRowBeforeColumnMutator(): void
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

		$rows = $result
			->setRowFetchMutator(static function (Db\Row $row): void {
				$row->type = 'type' . $row->id;
				$row->name = 'NAME_' . $row->name;
			})
			->setColumnsFetchMutator([
				'type' => static function (string $type): string {
					return \strtoupper($type);
				},
				'name' => static function (string $name): string {
					return \strtolower($name);
				},
			])
			->fetchAssoc('type=name');

		Tester\Assert::same(['TYPE1' => 'name_name3', 'TYPE2' => 'name_name2', 'TYPE3' => 'name_name1'], $rows);

		$result->free();
	}


	public function testFetchPairsValueMutator(): void
	{
		$this->connection->query('
			CREATE TABLE test(
				id serial,
				name character varying
			);
		');

		$this->connection->query('INSERT INTO test(name) SELECT \'name\' || generate_series FROM generate_series(3, 1, -1)');

		$result = $this->connection
			->query('SELECT id, name FROM test ORDER BY id')
			->setColumnsFetchMutator([
				'name' => static function (string $name): int {
					return (int) \substr($name, -1, 1);
				},
			]);

		$rows1 = $result->fetchPairs();

		Tester\Assert::same([1 => 3, 2 => 2, 3 => 1], $rows1);

		$rows2 = $result->fetchPairs('id', 'name');

		Tester\Assert::same([1 => 3, 2 => 2, 3 => 1], $rows2);

		$result->free();
	}


	public function testFetchPairsKeyMutator(): void
	{
		$this->connection->query('
			CREATE TABLE test(
				id serial,
				name character varying
			);
		');

		$this->connection->query('INSERT INTO test(name) SELECT \'name\' || generate_series FROM generate_series(3, 1, -1)');

		$result = $this->connection
			->query('SELECT id, name FROM test ORDER BY id')
			->setColumnsFetchMutator([
				'id' => static function (int $id): string {
					return 'id' . $id;
				},
			]);

		$rows1 = $result->fetchPairs();

		Tester\Assert::same(['id1' => 'name3', 'id2' => 'name2', 'id3' => 'name1'], $rows1);

		$rows2 = $result->fetchPairs('id', 'name');

		Tester\Assert::same(['id1' => 'name3', 'id2' => 'name2', 'id3' => 'name1'], $rows2);

		$result->free();
	}


	public function testFetchPairsKeyMutatorMutatedObject(): void
	{
		$this->connection->query('
			CREATE TABLE test(
				id serial,
				name character varying
			);
		');

		$this->connection->query('INSERT INTO test(name) SELECT \'name\' || generate_series FROM generate_series(3, 1, -1)');

		$result = $this->connection
			->query('SELECT id, name FROM test ORDER BY id')
			->setColumnsFetchMutator([
				'id' => static function (int $id): \DateTimeImmutable {
					return new \DateTimeImmutable('2020-04-0' . $id);
				},
			]);

		Tester\Assert::exception(static function () use ($result): void {
			$result->fetchPairs();
		}, Db\Exceptions\ResultException::class, code: Db\Exceptions\ResultException::FETCH_MUTATOR_BAR_RETURN_TYPE);

		$result->free();
	}


	public function testFetchPairsKeyValueMutator(): void
	{
		$this->connection->query('
			CREATE TABLE test(
				id serial,
				name character varying
			);
		');

		$this->connection->query('INSERT INTO test(name) SELECT \'name\' || generate_series FROM generate_series(3, 1, -1)');

		$result = $this->connection
			->query('SELECT id, name FROM test ORDER BY id')
			->setColumnsFetchMutator([
				'id' => static function (int $id): string {
					return 'id' . $id;
				},
				'name' => static function (string $name): int {
					return (int) \substr($name, -1, 1);
				},
			]);

		$rows1 = $result->fetchPairs();

		Tester\Assert::same(['id1' => 3, 'id2' => 2, 'id3' => 1], $rows1);

		$rows2 = $result->fetchPairs('id', 'name');

		Tester\Assert::same(['id1' => 3, 'id2' => 2, 'id3' => 1], $rows2);

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

		$result = $this->connection
			->query('SELECT type FROM test ORDER BY id')
			->setColumnsFetchMutator([
				'type' => static function (int $type): string {
					return 'type' . $type;
				},
			]);

		$rows1 = $result->fetchPairs();

		Tester\Assert::same(['type3', 'type2', 'type1'], $rows1);

		$rows2 = $result->fetchPairs(null, 'type');

		Tester\Assert::same(['type3', 'type2', 'type1'], $rows2);

		$result->free();
	}


	public function testFetchPairsRowBeforeColumnMutator(): void
	{
		$this->connection->query('
			CREATE TABLE test(
				id serial,
				name character varying
			);
		');

		$this->connection->query('INSERT INTO test(name) SELECT \'name\' || generate_series FROM generate_series(3, 1, -1)');

		$result = $this->connection
			->query('SELECT id, name FROM test ORDER BY id')
			->setRowFetchMutator(static function (Db\Row $row): void {
				$row->name = 'NAME' . $row->id;
				$row->id = 'id' . $row->id;
			})
			->setColumnsFetchMutator([
				'id' => static function (string $id): string {
					return \strtoupper($id);
				},
				'name' => static function (string $name): string {
					return \strtolower($name);
				},
			]);

		$rows = $result->fetchPairs('id', 'name');

		Tester\Assert::same(['ID1' => 'name1', 'ID2' => 'name2', 'ID3' => 'name3'], $rows);

		$result->free();
	}


	public function testFluentQueryRowFetchMutatorBeforeExecute(): void
	{
		$this->connection->query('
			CREATE TABLE test(
				id serial,
				name character varying
			);
		');

		$this->connection->query('INSERT INTO test(name) SELECT \'name\' || generate_series FROM generate_series(3, 1, -1)');

		$rows = $this->createFluentQuery()
			->table('test')
			->select(['id', 'name'])
			->orderBy('id')
			->setRowFetchMutator(static function (Db\Row $row): void {
				$row->new_column = $row->id . '-' . $row->name;
			})
			->fetchAll();

		Tester\Assert::same(['id' => 1, 'name' => 'name3', 'new_column' => '1-name3'], $rows[0]->toArray());
		Tester\Assert::same(['id' => 2, 'name' => 'name2', 'new_column' => '2-name2'], $rows[1]->toArray());
		Tester\Assert::same(['id' => 3, 'name' => 'name1', 'new_column' => '3-name1'], $rows[2]->toArray());
	}


	public function testFluentQueryRowFetchMutatorAfterExecute(): void
	{
		$this->connection->query('
			CREATE TABLE test(
				id serial,
				name character varying
			);
		');

		$this->connection->query('INSERT INTO test(name) SELECT \'name\' || generate_series FROM generate_series(3, 1, -1)');

		$query = $this->createFluentQuery()
			->table('test')
			->select(['id', 'name'])
			->orderBy('id');

		$query->execute();

		$rows = $query
			->setRowFetchMutator(static function (Db\Row $row): void {
				$row->new_column = $row->id . '-' . $row->name;
			})
			->fetchAll();

		Tester\Assert::same(['id' => 1, 'name' => 'name3', 'new_column' => '1-name3'], $rows[0]->toArray());
		Tester\Assert::same(['id' => 2, 'name' => 'name2', 'new_column' => '2-name2'], $rows[1]->toArray());
		Tester\Assert::same(['id' => 3, 'name' => 'name1', 'new_column' => '3-name1'], $rows[2]->toArray());
	}


	public function testFluentQueryColumnsMutatorBeforeExecute(): void
	{
		$this->connection->query('
			CREATE TABLE test(
				id serial,
				type integer,
				name character varying
			);
		');

		$this->connection->query('INSERT INTO test(type, name) SELECT generate_series, \'name\' || generate_series FROM generate_series(3, 1, -1)');

		$rows = $this->createFluentQuery()
			->table('test')
			->select(['id', 'type', 'name'])
			->orderBy('id')
			->setColumnsFetchMutator([
				'type' => static function (int $type): string {
					return 'type' . $type;
				},
			])
			->fetchAssoc('type');

		Tester\Assert::same(['id' => 1, 'type' => 3, 'name' => 'name3'], $rows['type3']->toArray());
		Tester\Assert::same(['id' => 2, 'type' => 2, 'name' => 'name2'], $rows['type2']->toArray());
		Tester\Assert::same(['id' => 3, 'type' => 1, 'name' => 'name1'], $rows['type1']->toArray());
	}


	public function testFluentQueryColumnsMutatorAfterExecute(): void
	{
		$this->connection->query('
			CREATE TABLE test(
				id serial,
				type integer,
				name character varying
			);
		');

		$this->connection->query('INSERT INTO test(type, name) SELECT generate_series, \'name\' || generate_series FROM generate_series(3, 1, -1)');

		$query = $this->createFluentQuery()
			->table('test')
			->select(['id', 'type', 'name'])
			->orderBy('id');

		$query->execute();

		$rows = $query
			->setColumnsFetchMutator([
				'type' => static function (int $type): string {
					return 'type' . $type;
				},
			])
			->fetchAssoc('type');

		Tester\Assert::same(['id' => 1, 'type' => 3, 'name' => 'name3'], $rows['type3']->toArray());
		Tester\Assert::same(['id' => 2, 'type' => 2, 'name' => 'name2'], $rows['type2']->toArray());
		Tester\Assert::same(['id' => 3, 'type' => 1, 'name' => 'name1'], $rows['type1']->toArray());
	}


	public function testFluentQueryRowAndColumnsMutatorBeforeExecute(): void
	{
		$this->connection->query('
			CREATE TABLE test(
				id serial,
				type integer,
				name character varying
			);
		');

		$this->connection->query('INSERT INTO test(type, name) SELECT generate_series, \'name\' || generate_series FROM generate_series(3, 1, -1)');

		$rows = $this->createFluentQuery()
			->table('test')
			->select(['id', 'type', 'name'])
			->orderBy('id')
			->setRowFetchMutator(static function (Db\Row $row): void {
				$row->new_column = $row->id . '-' . $row->name;
			})
			->setColumnsFetchMutator([
				'type' => static function (int $type): string {
					return 'type' . $type;
				},
			])
			->fetchAssoc('type');

		Tester\Assert::same(['id' => 1, 'type' => 3, 'name' => 'name3', 'new_column' => '1-name3'], $rows['type3']->toArray());
		Tester\Assert::same(['id' => 2, 'type' => 2, 'name' => 'name2', 'new_column' => '2-name2'], $rows['type2']->toArray());
		Tester\Assert::same(['id' => 3, 'type' => 1, 'name' => 'name1', 'new_column' => '3-name1'], $rows['type1']->toArray());
	}


	public function testFluentQueryRowAndColumnsMutatorAfterExecute(): void
	{
		$this->connection->query('
			CREATE TABLE test(
				id serial,
				type integer,
				name character varying
			);
		');

		$this->connection->query('INSERT INTO test(type, name) SELECT generate_series, \'name\' || generate_series FROM generate_series(3, 1, -1)');

		$query = $this->createFluentQuery()
			->table('test')
			->select(['id', 'type', 'name'])
			->orderBy('id');

		$query->execute();

		$rows = $query
			->setRowFetchMutator(static function (Db\Row $row): void {
				$row->new_column = $row->id . '-' . $row->name;
			})
			->setColumnsFetchMutator([
				'type' => static function (int $type): string {
					return 'type' . $type;
				},
			])
			->fetchAssoc('type');

		Tester\Assert::same(['id' => 1, 'type' => 3, 'name' => 'name3', 'new_column' => '1-name3'], $rows['type3']->toArray());
		Tester\Assert::same(['id' => 2, 'type' => 2, 'name' => 'name2', 'new_column' => '2-name2'], $rows['type2']->toArray());
		Tester\Assert::same(['id' => 3, 'type' => 1, 'name' => 'name1', 'new_column' => '3-name1'], $rows['type1']->toArray());
	}


	public function testFluentQueryAfterReexecute(): void
	{
		$this->connection->query('
			CREATE TABLE test(
				id serial,
				type integer,
				name character varying
			);
		');

		$this->connection->query('INSERT INTO test(type, name) SELECT generate_series, \'name\' || generate_series FROM generate_series(3, 1, -1)');

		$query = $this->createFluentQuery()
			->table('test')
			->select(['id', 'type', 'name'])
			->orderBy('id')
			->setRowFetchMutator(static function (Db\Row $row): void {
				$row->new_column = $row->id . '-' . $row->name;
			})
			->setColumnsFetchMutator([
				'type' => static function (int $type): string {
					return 'type' . $type;
				},
			]);

		$query->execute();

		$rows = $query->reexecute()
			->fetchAssoc('type');

		Tester\Assert::same(['id' => 1, 'type' => 3, 'name' => 'name3', 'new_column' => '1-name3'], $rows['type3']->toArray());
		Tester\Assert::same(['id' => 2, 'type' => 2, 'name' => 'name2', 'new_column' => '2-name2'], $rows['type2']->toArray());
		Tester\Assert::same(['id' => 3, 'type' => 1, 'name' => 'name1', 'new_column' => '3-name1'], $rows['type1']->toArray());
	}


	private function createFluentQuery(): Fluent\QueryExecute
	{
		return new Fluent\QueryExecute(new Fluent\QueryBuilder(), $this->connection);
	}

}

(new FetchMutatorTest())->run();
