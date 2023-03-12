<?php declare(strict_types = 1);

return PHP_MAJOR_VERSION < 8
	? [
		'parameters' => [
			'ignoreErrors' => [
				[
					'message' => '#PHPDoc tag @var with type array<int, string>\|false is not subtype of native type string\.#',
					'path' => __DIR__ . '/src/Db/Connection.php',
					'count' => 1,
				],
			],
		],
	]
	: [
		'parameters' => [
			'ignoreErrors' => [
				[
					'message' => '#Call to function assert\(\) with true will always evaluate to true\.#',
					'path' => __DIR__ . '/src/Db/Result.php',
					'count' => 1,
				],
				[
					'message' => '#Call to function is_string\(\) with string will always evaluate to true\.#',
					'path' => __DIR__ . '/src/Db/Result.php',
					'count' => 1,
				],
			],
		],
	];
