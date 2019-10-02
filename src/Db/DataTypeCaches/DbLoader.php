<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db\DataTypeCaches;

use Forrest79\PhPgSql\Db;

abstract class DbLoader implements Db\DataTypeCache
{
	private const LOAD_QUERY = 'SELECT oid, typname FROM pg_catalog.pg_type';


	protected function loadFromDb(Db\Connection $connection): array
	{
		$resource = $connection->getResource();
		$query = @\pg_query($resource, self::LOAD_QUERY); // intentionally @
		if ($query === FALSE) {
			throw Db\Exceptions\DataTypeCacheException::cantLoadTypes($connection->getLastError());
		}

		$types = [];
		while (TRUE) {
			$data = \pg_fetch_assoc($query);
			if ($data === FALSE) {
				break;
			}
			$types[$data['oid']] = $data['typname'];
		}

		return $types;
	}

}
