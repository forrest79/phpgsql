<?php declare(strict_types=1);

namespace Tests\Integration\Forrest79\PhPgSql\Db;

use Forrest79\PhPgSql\Db;
use Tester;

require_once __DIR__ . '/../bootstrap.php';

abstract class TestCase extends Tester\TestCase
{
	/** @var string */
	private $dbname;

	/** @var string */
	private $config;

	/** @var Db\Connection */
	private $adminConnection;


	/**
	 * @throws Db\Exceptions\ConnectionException
	 * @throws Db\Exceptions\QueryException
	 */
	protected function setUp(): void
	{
		parent::setUp();
		$this->dbname = sprintf('phpgsql_%s_%s', getmypid(), uniqid());
		$this->config = PHPGSQL_CONNECTION_CONFIG;
		$this->adminConnection = new Db\Connection($this->config);

		$this->adminConnection->query('CREATE DATABASE ?', $this->adminConnection::literal($this->getDbName()));
	}


	/**
	 * @throws Db\Exceptions\ConnectionException
	 * @throws Db\Exceptions\QueryException
	 */
	protected function tearDown(): void
	{
		$this->adminConnection->query('DROP DATABASE ?', $this->adminConnection::literal($this->dbname));
	}


	protected function getConfig(): string
	{
		return $this->config;
	}


	protected function getDbName(): string
	{
		return $this->dbname;
	}

}