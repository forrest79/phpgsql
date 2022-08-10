<?php declare(strict_types = 1);

return PHP_VERSION_ID < 80100
	? [
		'parameters' => [
			'ignoreErrors' => [
				[
					'message' => '#^Parameter \#1 \$connection of function pg_connect_poll expects resource, resource\|null given\.$#',
					'path' => __DIR__ . '/src/Db/Connection.php',
					'count' => 1,
				],
				[
					'message' => '#^Parameter \#1 \$result of function pg_.+ expects resource, resource\|false given\.$#',
					'path' => __DIR__ . '/tests/Integration/PgFunctionsTest.php',
					'count' => 50,
				],
			],
		],
	]
	: [
		'parameters' => [
			'ignoreErrors' => [
				'#Parameter \#\d+ \$.+ of function pg_.+ expects resource, PgSql\\\\Connection given\.#',
				'#Parameter \#\d+ \$.+ of method Forrest79\\\\PhPgSql\\\\.+::.+\(\) expects resource, PgSql\\\\Result given\.#',
				'#Parameter \#\d+ \$.+ of function pg_.+ expects PgSql\\\\Connection(\|null)?, resource(\|null)? given\.#',
				'#Parameter \#\d+ \$.+ of function pg_.+ expects PgSql\\\\Result, PgSql\\\\Result\|false given\.#',
				'#Parameter \#\d+ \$.+ of function pg_.+ expects PgSql\\\\Result, resource(\|false)? given\.#',
				'#Parameter \#\d+ \$result of static method Forrest79\\\\PhPgSql\\\\Tests\\\\Integration\\\\PgFunctionsTest\:\:pgResultError\(\) expects resource\|false, PgSql\\\\Result\|false given\.#',
				'#Property Forrest79\\\\PhPgSql\\\\.+::\$resource \(resource(\|null)?\) does not accept PgSql\\\\Connection\.#',
				'#Parameter \#1 \$result of static method Forrest79\\\\PhPgSql\\\\.+::.+\(\) expects resource, PgSql\\\\Result given\.#',
				[
					'message' => '#Parameter \#2 \$rawValues of method Forrest79\\\\PhPgSql\\\\Db\\\\RowFactory::createRow\(\) expects array\<string, string\|null\>, array\<int\|string, string\|null\> given\.#',
					'path' => __DIR__ . '/src/Db/Result.php',
					'count' => 1,
				],
				[
					'message' => '#Parameter \#1 \$query of function pg_query expects string, string\|null given\.#',
					'path' => __DIR__ . '/tests/clean-dbs.php',
					'count' => 1,
				],
			],
		],
	];
