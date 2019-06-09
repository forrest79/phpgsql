<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Tests\Integration;

use Forrest79\PhPgSql\Db;
use Tester;

require_once __DIR__ . '/TestCase.php';

/**
 * @testCase
 */
class DataTypeCacheTest extends TestCase
{
	/** @var Db\Connection */
	private $connection;

	/** @var string */
	private $cacheFile;


	protected function setUp(): void
	{
		parent::setUp();
		$this->connection = new Db\Connection(\sprintf('%s dbname=%s', $this->getConfig(), $this->getDbName()));
		$this->cacheFile = \sprintf('%s/plpgsql-data-types-cache-%s.php', \sys_get_temp_dir(), \uniqid());
	}


	public function testCache(): void
	{
		$dataTypeCache = $this->createDataTypeCache();

		$this->connection->setDataTypeCache($dataTypeCache);

		Tester\Assert::false(\file_exists($this->cacheFile));

		$cacheDb = $dataTypeCache->load($this->connection); // load from DB
		Tester\Assert::true(\count($cacheDb) > 0); // there must be some types

		Tester\Assert::true($this->connection->query('SELECT TRUE')->fetchSingle()); // test valid boolean parsing

		$cacheCache = $dataTypeCache->load($this->connection); // load from cache
		Tester\Assert::same($cacheDb, $cacheCache);

		$dataTypeCacheNew = $this->createDataTypeCache();

		$cacheFile = $dataTypeCacheNew->load($this->connection); // load from file
		Tester\Assert::same($cacheDb, $cacheFile);

		$cacheCacheNew = $dataTypeCacheNew->load($this->connection); // load from cache
		Tester\Assert::same($cacheDb, $cacheCacheNew);

		$dataTypeCache->clean();
		$dataTypeCacheNew->clean();
	}


	public function testLoadCacheFromFile(): void
	{
		$type = 'phppgsql-test-type';

		$dataTypeCache = $this->createDataTypeCache();

		$cacheDb = $dataTypeCache->load($this->connection); // load from DB
		Tester\Assert::true(\count($cacheDb) > 0); // there must be some types
		Tester\Assert::false(\array_search($type, $cacheDb, TRUE)); // but no $type

		\file_put_contents(
			$this->cacheFile,
			'<?php declare(strict_types=1);' . PHP_EOL . \sprintf('return [1=>\'%s\'];', $type)
		);

		$dataTypeCacheNew = $this->createDataTypeCache();
		$cacheFile = $dataTypeCacheNew->load($this->connection); // load from file
		Tester\Assert::same([1 => $type], $cacheFile);
	}


	private function createDataTypeCache(): Db\DataTypeCaches\PhpFile
	{
		return new Db\DataTypeCaches\PhpFile($this->cacheFile);
	}


	protected function tearDown(): void
	{
		$this->connection->close();
		parent::tearDown();
	}

}

(new DataTypeCacheTest())->run();
