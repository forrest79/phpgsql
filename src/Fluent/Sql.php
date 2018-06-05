<?php

namespace Forrest79\PhPgSql\Fluent;

interface Sql
{

	function table($from, ?string $alias = NULL): Fluent;


	function select(array $columns): Fluent;


	function distinct(): Fluent;


	function from($from, ?string $alias = NULL): Fluent;


	function join($join, ?string $alias = NULL, array $onConditions = []): Fluent;


	function innerJoin($join, ?string $alias = NULL, array $onConditions = []): Fluent;


	function leftJoin($join, ?string $alias = NULL, array $onConditions = []): Fluent;


	function leftOuterJoin($join, ?string $alias = NULL, array $onConditions = []): Fluent;


	function rightJoin($join, ?string $alias = NULL, array $onConditions = []): Fluent;


	function rightOuterJoin($join, ?string $alias = NULL, array $onConditions = []): Fluent;


	function fullJoin($join, ?string $alias = NULL, array $onConditions = []): Fluent;


	function fullOuterJoin($join, ?string $alias = NULL, array $onConditions = []): Fluent;


	function crossJoin($join, ?string $alias = NULL): Fluent;


	function on(string $alias, array $conditions): Fluent;


	function where(string $condition, ...$params): Fluent;


	function whereAnd(array $conditions = []): Complex;


	function whereOr(array $conditions = []): Complex;


	function groupBy(array $columns): Fluent;


	function having(string $condition, ...$params): Fluent;


	function havingAnd(array $conditions = []): Complex;


	function havingOr(array $conditions = []): Complex;


	function orderBy(array $colums): Fluent;


	function limit(int $limit): Fluent;


	function offset(int $offset): Fluent;


	function union($query): Fluent;


	function unionAll($query): Fluent;


	function intersect($query): Fluent;


	function except($query): Fluent;


	function insert(?string $into = NULL, ?array $columns = []): Fluent;


	function values(array $data): Fluent;


	function rows(array $rows): Fluent;


	function update(?string $table = NULL, ?string $alias = NULL): Fluent;


	function set(array $data): Fluent;


	function delete(?string $from = NULL, ?string $alias = NULL): Fluent;


	function returning(array $returning): Fluent;


	function truncate(?string $table = NULL): Fluent;

}
