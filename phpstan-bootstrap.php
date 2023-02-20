<?php declare(strict_types = 1);

// Forward compatibility for PHP 8.1 Enums
if (\PHP_VERSION_ID < 80000) {

	class BackedEnum
	{
		/** @var int|string */
		public $value;

	}

}
