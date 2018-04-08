<?php declare(strict_types=1);

namespace Forrest79\PhPgSql\Db;

class Query
{
	/** @var string */
	private $origSql;

	/** @var array */
	private $origParams;

	/** @var string */
	private $sql;

	/** @var array */
	private $params;

	/** @var int */
	private $startParamsIndex = 0;


	public function __construct(string $sql, array $params)
	{
		$this->origSql = $sql;
		$this->origParams = $params;
	}


	public function getSql(): string
	{
		if ($this->sql === NULL) {
			$this->parseQuery();
		}

		return $this->sql;
	}


	public function getParams(): array
	{
		if ($this->params === NULL) {
			$this->parseQuery();
		}

		return $this->params;
	}


	/**
	 * @internal
	 */
	public function setStartParamsIndex(int $index): self
	{
		$this->startParamsIndex = $index;
		return $this;
	}


	private function parseQuery(): void
	{
		$sql = $this->origSql;

		$origParamIndex = 0;
		$paramIndex = $this->startParamsIndex;

		$this->params = [];
		$this->sql = preg_replace_callback(
			'/([\\\\]?)\?/',
			function ($matches) use (& $origParamIndex, & $paramIndex) {
				if ($matches[1] === '\\') {
					return '?';
				}

				if (!isset($this->origParams[$origParamIndex])) {
					throw Exceptions\QueryException::noParam($origParamIndex);
				}

				$param = $this->origParams[$origParamIndex];
				unset($this->origParams[$origParamIndex]);
				$origParamIndex++;

				if (is_array($param)) {
					$keys = '';
					array_walk($param, function() use (& $keys, & $paramIndex){
						$keys .= '$' . ++$paramIndex . ', ';
					});
					$this->params = array_merge($this->params, $param);
					return substr($keys, 0, -2);
				} else if (is_bool($param)) {
					return $param === TRUE ? 'TRUE' : 'FALSE';
				} else if ($param instanceof Literal) {
					return (string) $param;
				} else if ($param instanceof self) {
					$param->setStartParamsIndex($paramIndex);
					$params = $param->getParams();
					$paramIndex += count($params);
					$this->params = array_merge($this->params, $params);
					return $param->getSql();
				}

				$this->params[] = $param;

				return '$' . ++$paramIndex;
			},
			$sql
		);

		if (!$this->params) {
			$this->params = $this->origParams;
		}
	}

}
