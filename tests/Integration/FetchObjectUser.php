<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Tests\Integration;

final class FetchObjectUser
{

	public function __construct(
		public readonly int $id,
		public readonly string|null $name,
		public readonly \DateTimeImmutable $createdAt,
	)
	{
	}

}
