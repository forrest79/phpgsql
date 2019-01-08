<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Tests\Integration;

use Forrest79\PhPgSql\Db;
use Tester;

require_once __DIR__ . '/TestCase.php';

/**
 * @testCase
 */
class DataTypesCacheTest extends TestCase
{
	/** @var Db\Connection */
	private $connection;

	/** @var string */
	private $cacheFile;

	/** @var Db\DataTypesCache\FileDbDataTypesCache */
	private $dataTypeCache;


	protected function setUp(): void
	{
		parent::setUp();
		$this->connection = new Db\Connection(sprintf('%s dbname=%s', $this->getConfig(), $this->getDbName()));
		$this->cacheFile = sprintf('%s/plpgsql-data-types-cache-%s.php', sys_get_temp_dir(), uniqid());
		$this->dataTypeCache = new Db\DataTypesCache\FileDbDataTypesCache($this->connection, $this->cacheFile);
	}


	public function testCache(): void
	{
		$this->connection->setDataTypesCache($this->dataTypeCache);

		Tester\Assert::false(file_exists($this->cacheFile));

		$cacheDb = $this->dataTypeCache->load(); // load from DB
		Tester\Assert::true(count($cacheDb) > 0); // there must be some types

		Tester\Assert::true($this->connection->query('SELECT TRUE')->fetchSingle());

		$cacheFile = $this->dataTypeCache->load(); // load from file
		Tester\Assert::same($cacheDb, $cacheFile);
	}


	public function testLoadCacheFromFile(): void
	{
		$type = 'test-type';

		$cacheDb = $this->dataTypeCache->load(); // load from DB
		Tester\Assert::true(count($cacheDb) > 0); // there must be some types
		Tester\Assert::false(array_search($type, $cacheDb, TRUE)); // but no $type

		\file_put_contents(
			$this->cacheFile,
			'<?php declare(strict_types=1);' . PHP_EOL . \sprintf('return [1=>\'%s\'];', $type)
		);

		$cacheFile = $this->dataTypeCache->load(); // load from file
		Tester\Assert::same([1 => $type], $cacheFile);
	}


	protected function tearDown(): void
	{
		$this->connection->close();
		$this->dataTypeCache->clean();
		parent::tearDown();
	}

}

(new DataTypesCacheTest())->run();
