<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Tests\Integration;

use Forrest79\PhPgSql\Db;

/**
 * @property-read int $id
 * @property-read string|null $name
 * @property-read \DateTimeImmutable $created_at
 */
final class FetchObjectRow extends Db\Row
{

}
