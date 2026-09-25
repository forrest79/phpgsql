<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Tests\Integration;

use Forrest79\PhPgSql\Db;
use Forrest79\PhPgSql\Fluent;
use Tester;

require_once __DIR__ . '/TestCase.php';

/**
 * @testCase
 */
final class FetchObjectTest extends TestCase
{

	public function testFetchObject(): void
	{
		$this->createTestTable();

		$result = $this->connection->query('SELECT id, name, created_at AS "createdAt" FROM test ORDER BY id');

		$user = $result->fetchObject(FetchObjectUser::class);
		if ($user === null) {
			throw new \RuntimeException('No data from database were returned');
		}

		Tester\Assert::type(FetchObjectUser::class, $user);
		Tester\Assert::same(1, $user->id);
		Tester\Assert::same('name3', $user->name);
		Tester\Assert::same('2026-01-01', $user->createdAt->format('Y-m-d'));

		$user = $result->fetchObject(FetchObjectUser::class);
		Tester\Assert::same(2, $user?->id);
		Tester\Assert::null($user?->name);

		$result->fetchObject(FetchObjectUser::class);
		Tester\Assert::null($result->fetchObject(FetchObjectUser::class));

		$result->free();
	}


	public function testFetchAllObjects(): void
	{
		$this->createTestTable();

		$result = $this->connection->query('SELECT id, name, created_at AS "createdAt" FROM test ORDER BY id');

		$users = $result->fetchAllObjects(FetchObjectUser::class);
		Tester\Assert::same([1, 2, 3], \array_map(static fn (FetchObjectUser $user): int => $user->id, $users));

		$users = $result->fetchAllObjects(FetchObjectUser::class, 1);
		Tester\Assert::same([2, 3], \array_map(static fn (FetchObjectUser $user): int => $user->id, $users));

		$users = $result->fetchAllObjects(FetchObjectUser::class, 1, 1);
		Tester\Assert::same([2], \array_map(static fn (FetchObjectUser $user): int => $user->id, $users));

		$result->free();

		$emptyResult = $this->connection->query('SELECT id, name, created_at AS "createdAt" FROM test WHERE id < 0');
		Tester\Assert::same([], $emptyResult->fetchAllObjects(FetchObjectUser::class));
		$emptyResult->free();
	}


	public function testFetchObjectIterator(): void
	{
		$this->createTestTable();

		$result = $this->connection->query('SELECT id, name, created_at AS "createdAt" FROM test ORDER BY id');

		for ($i = 0; $i < 2; $i++) { // iterator can be rewound
			$ids = [];
			foreach ($result->fetchObjectIterator(FetchObjectUser::class) as $key => $user) {
				$ids[$key] = $user->id;
			}
			Tester\Assert::same([0 => 1, 1 => 2, 2 => 3], $ids);
		}

		$result->free();
	}


	public function testFetchObjectWithRowFetchMutator(): void
	{
		$this->createTestTable();

		$result = $this->connection
			->query('SELECT name FROM test ORDER BY id')
			->addRowFetchMutator(static function (Db\Row $row): void {
				$row->name = \is_string($row->name) ? \strtoupper($row->name) : 'NONE';
			});

		$names = $result->fetchAllObjects(FetchObjectName::class);
		Tester\Assert::same(['NAME3', 'NONE', 'NAME1'], \array_map(static fn (FetchObjectName $name): string => $name->name, $names));

		$result->free();
	}


	public function testFetchObjectAsRowSubclass(): void
	{
		$this->createTestTable();

		$result = $this->connection->query('SELECT id, name, created_at FROM test ORDER BY id');

		$row = $result->fetchObject(FetchObjectRow::class);
		if ($row === null) {
			throw new \RuntimeException('No data from database were returned');
		}

		Tester\Assert::type(FetchObjectRow::class, $row);
		Tester\Assert::same(['id' => false, 'name' => false, 'created_at' => false], $result->getParsedColumns()); // lazy parsing as standard row

		Tester\Assert::same(1, $row->id);
		Tester\Assert::same(['id' => true, 'name' => false, 'created_at' => false], $result->getParsedColumns());

		Tester\Assert::same([1, 2, 3], \array_map(static fn (FetchObjectRow $row): int => $row->id, $result->fetchAllObjects(FetchObjectRow::class)));

		$result->free();
	}


	public function testFetchObjectBadColumns(): void
	{
		$this->createTestTable();

		$result = $this->connection->query('SELECT id, name, created_at AS "createdAt", 1 AS extra FROM test ORDER BY id');
		Tester\Assert::exception(static function () use ($result): void {
			$result->fetchObject(FetchObjectUser::class);
		}, Db\Exceptions\ResultException::class, code: Db\Exceptions\ResultException::CANNOT_HYDRATE_OBJECT);
		$result->free();

		$result = $this->connection->query('SELECT id, name FROM test ORDER BY id');
		Tester\Assert::exception(static function () use ($result): void {
			$result->fetchObject(FetchObjectUser::class);
		}, Db\Exceptions\ResultException::class, code: Db\Exceptions\ResultException::CANNOT_HYDRATE_OBJECT);
		$result->free();

		$result = $this->connection->query('SELECT id::text AS id, name, created_at AS "createdAt" FROM test ORDER BY id');
		Tester\Assert::exception(static function () use ($result): void {
			$result->fetchObject(FetchObjectUser::class);
		}, Db\Exceptions\ResultException::class, code: Db\Exceptions\ResultException::CANNOT_HYDRATE_OBJECT);
		$result->free();
	}


	public function testFluentFetchObject(): void
	{
		$this->createTestTable();

		$fluentConnection = new Fluent\Connection($this->getTestConnectionConfig());

		$query = $fluentConnection
			->createQuery()
			->select(['id', 'name', 'createdAt' => 'created_at'])
			->from('test')
			->orderBy('id');

		Tester\Assert::same(1, $query->fetchObject(FetchObjectUser::class)?->id);
		Tester\Assert::same([1, 2, 3], \array_map(static fn (FetchObjectUser $user): int => $user->id, $query->fetchAllObjects(FetchObjectUser::class)));

		$ids = [];
		foreach ($query->fetchObjectIterator(FetchObjectUser::class) as $user) {
			$ids[] = $user->id;
		}
		Tester\Assert::same([1, 2, 3], $ids);

		$query->free();

		$fluentConnection->close();
	}


	private function createTestTable(): void
	{
		$this->connection->query('
			CREATE TABLE test(
				id serial,
				name text,
				created_at date NOT NULL DEFAULT \'2026-01-01\'
			);
		');

		$this->connection->query('INSERT INTO test(name) VALUES(?), (?), (?)', 'name3', null, 'name1');
	}

}

(new FetchObjectTest())->run();
