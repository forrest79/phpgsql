<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Tests\Integration;

use Forrest79\PhPgSql\Db;
use Forrest79\PhPgSql\Tests;

require_once __DIR__ . '/../TestCase.php';

abstract class TestCase extends Tests\TestCase
{
	/** @var Db\Connection */
	protected $connection;

	/** @var string */
	private $dbname;

	/** @var string */
	private $config;

	/** @var Db\Connection */
	private $adminConnection;


	protected function setUp(): void
	{
		parent::setUp();
		$this->dbname = \sprintf('phpgsql_%s_%s', \getmypid(), \uniqid());
		$this->config = \PHPGSQL_CONNECTION_CONFIG;
		$this->adminConnection = new Db\Connection($this->config);

		$this->adminConnection->query('CREATE DATABASE ?', Db\Sql\Literal::create($this->getDbName()));

		$this->connection = $this->createConnection();
	}


	protected function tearDown(): void
	{
		$this->connection->close();
		$this->adminConnection->query('DROP DATABASE ?', Db\Sql\Literal::create($this->dbname));
	}


	protected function getConfig(): string
	{
		return $this->config;
	}


	protected function getDbName(): string
	{
		return $this->dbname;
	}


	protected function getTestConnectionConfig(): string
	{
		return \sprintf('%s dbname=%s', $this->getConfig(), $this->getDbName());
	}


	protected function createConnection(): Db\Connection
	{
		return new Db\Connection($this->getTestConnectionConfig());
	}

}
