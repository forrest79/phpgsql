<?php

namespace Forrest79\PhPgSql\Db;

/**
 * @implements \ArrayAccess<string, mixed>
 * @implements \IteratorAggregate<string, mixed>
 */
interface Rowable extends \ArrayAccess, \IteratorAggregate, \Countable, \JsonSerializable
{

	public function hasKey(string $key): bool;


	public function toArray(): array;

}
