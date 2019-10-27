<?php declare(strict_types=1);

\define('PHPGSQL_CONNECTION_CONFIG', \getenv('PHPGSQL_CONNECTION_CONFIG') ?: 'host=localhost port=5432 user=postgres password=postgres');
