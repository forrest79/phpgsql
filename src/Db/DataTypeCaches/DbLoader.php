<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db\DataTypeCaches;

use Forrest79\PhPgSql\Db;

abstract class DbLoader implements Db\DataTypeCache
{
	private const LOAD_QUERY = 'SELECT oid, typname FROM pg_catalog.pg_type';

	/** @var Db\Connection */
	private $connection;


	public function __construct(Db\Connection $connection)
	{
		$this->connection = $connection;
	}


	protected function loadFromDb(): array
	{
		$connection = $this->connection->getResource();
		$resource = @\pg_query($connection, self::LOAD_QUERY); // intentionally @
		if ($resource === FALSE) {
			throw Db\Exceptions\DataTypeCacheException::cantLoadTypes();
		}

		$types = [];
		while (TRUE) {
			$data = \pg_fetch_assoc($resource);
			if ($data === FALSE) {
				break;
			}
			$types[(int) $data['oid']] = $data['typname'];
		};

		return $types;
	}

}
