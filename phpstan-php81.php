<?php declare(strict_types = 1);

return PHP_VERSION_ID < 80100
	? [
		'parameters' => [
			'excludePaths' => [
				'analyseAndScan' => [
					__DIR__ . '/tests/TestEnum.php',
				],
			],
			'ignoreErrors' => [
				[
					'message' => '#^Parameter \#1 \$connection of function pg_connect_poll expects resource, resource\|null given\.$#',
					'path' => __DIR__ . '/src/Db/Connection.php',
					'count' => 1,
				],
				[ // === We know, that this can't happen ===
					'message' => '#^Parameter \#1 \$connection of function pg_set_error_verbosity expects resource, resource\|null given\.$#',
					'path' => __DIR__ . '/src/Db/Connection.php',
					'count' => 1,
				],
				[
					'message' => '#^Parameter \#1 \$result of function pg_.+ expects resource, resource\|false given\.$#',
					'path' => __DIR__ . '/tests/Integration/PgFunctionsTest.php',
					'count' => 50,
				],
				[ // === Compatibility with Enums (PHP 8.1) ===
					'message' => '#Access to constant One on an unknown class Forrest79\\\\PhPgSql\\\\Tests\\\\TestEnum\.#',
					'path' => __DIR__ . '/tests/Unit/FluentQueryTest.php',
					'count' => 2,
				],
				[ // === Compatibility with Enums (PHP 8.1) ===
					'message' => '#Access to constant (One|Two) on an unknown class Forrest79\\\\PhPgSql\\\\Tests\\\\TestEnum\.#',
					'path' => __DIR__ . '/tests/Unit/QueryTest.php',
					'count' => 3,
				],
			],
		],
	]
	: [
		'parameters' => [
			'ignoreErrors' => [
				'#Parameter \#\d+ \$.+ of function pg_.+ expects PgSql\\\\Connection\|string, resource given\.#',
				'#Parameter \#\d+ \$.+ of method Forrest79\\\\PhPgSql\\\\.+::.+\(\) expects resource, PgSql\\\\Result given\.#',
				'#Parameter \#\d+ \$.+ of function pg_.+ expects PgSql\\\\Connection(\|null)?, resource(\|null)? given\.#',
				'#Parameter \#\d+ \$.+ of function pg_.+ expects PgSql\\\\Result, PgSql\\\\Result\|false given\.#',
				'#Parameter \#\d+ \$.+ of function pg_.+ expects PgSql\\\\Result, resource(\|false)? given\.#',
				'#^Parameter \#1 \$connection of function pg_set_error_verbosity expects int\|PgSql\\\\Connection, resource(\|null)? given\.$#',
				'#Parameter \#\d+ \$result of static method Forrest79\\\\PhPgSql\\\\Tests\\\\Integration\\\\PgFunctionsTest\:\:pgResultError\(\) expects resource\|false, PgSql\\\\Result\|false given\.#',
				'#Property Forrest79\\\\PhPgSql\\\\.+::(\$resource|\$queryResource) \(resource(\|null)?\) does not accept PgSql\\\\(Connection|Result)\.#',
				'#Parameter \#1 \$result of static method Forrest79\\\\PhPgSql\\\\.+::.+\(\) expects resource, PgSql\\\\Result given\.#',
				[
					'message' => '#Parameter \#2 \$rawValues of method Forrest79\\\\PhPgSql\\\\Db\\\\RowFactory::createRow\(\) expects array\<string, string\|null\>, array\<int\|string, string\|null\> given\.#',
					'path' => __DIR__ . '/src/Db/Result.php',
					'count' => 1,
				],
				[
					'message' => '#Parameter \#1 \$connection of function pg_query expects PgSql\\\\Connection\|string, string\|null given\.#',
					'path' => __DIR__ . '/tests/clean-dbs.php',
					'count' => 1,
				],
			],
		],
	];
