PhPgSql
=======

[![Latest Stable Version](https://poser.pugx.org/forrest79/phpgsql/v)](//packagist.org/packages/forrest79/phpgsql)
[![Monthly Downloads](https://poser.pugx.org/forrest79/phpgsql/d/monthly)](//packagist.org/packages/forrest79/phpgsql)
[![License](https://poser.pugx.org/forrest79/phpgsql/license)](//packagist.org/packages/forrest79/phpgsql)
[![Build](https://github.com/forrest79/phpgsql/actions/workflows/build.yml/badge.svg?branch=master)](https://github.com/forrest79/phpgsql/actions/workflows/build.yml)
[![codecov](https://codecov.io/gh/forrest79/phpgsql/graph/badge.svg?token=QBWAKKUSN3)](https://codecov.io/gh/forrest79/phpgsql)

Simple and fast PHP database library for PostgreSQL with auto converting DB types to PHP and a powerful fluent interface that can be used to simply create the most of SQL queries.

- lightweight
- no magic
- no database structure reading
- automatically convert PG data types to PHP data types
- support async connect to DB and async query
- simple creating queries with parameters - char `?` for variable is automatically replaced with `$1`, `$2` and so...
  - as a variable you can pass scalar, bool, array, literal or other query

DB and fluent part can be used separately.

> Examples of how to use this library in application, how to extend it with some useful methods and a simple repository system can be found here [https://github.com/forrest79/phpgsql-extension-repository-example](https://github.com/forrest79/phpgsql-extension-repository-example).


## Installation

The recommended way to install PhPgSql is through Composer:

```sh
composer require forrest79/phpgsql
```

PhPgSql requires PHP 7.1.0 and pgsql binary extension. It doesn't work with the PDO!

If you're using [PHPStan](https://phpstan.org/) you can install [settings](https://github.com/forrest79/phpgsql-phpstan) for this great tool. 

```sh
composer require --dev forrest79/phpgsql-phpstan
```

And if you're using [Nette framework](https://nette.org/), there is existing integration with the [Tracy](https://tracy.nette.org/) panel.


## Documentation

[Complete documentation](docs/index.md) is in the `docs` directory.

> All examples are self-tested - you can be sure, it's working.
