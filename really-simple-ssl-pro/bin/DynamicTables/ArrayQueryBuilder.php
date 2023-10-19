<?php

namespace security\wordpress\DynamicTables;

use Exception;

class ArrayQueryBuilder extends QueryBuilder {
	private $dataArray = [];
	protected $where = [];
	protected $orderBy = '';

	public function __construct($dataArray) {
		parent::__construct('');

		$this->dataArray = $dataArray;
	}

	public function get(): array {
		$results = $this->dataArray;

		// Applying WHERE conditions including where_in and where_not_in
		if (!empty($this->where)) {
			$results = array_filter($results, function($item) {
				foreach ($this->where as $whereClause) {
					$column = $whereClause['column'];
					$operator = $whereClause['operator'];
					$value = $whereClause['value'];

					if (!$this->compare($item[$column], $operator, $value)) {
						return false;
					}
				}
				return true;
			});
		}

		// Applying ORDER BY
		if (!empty($this->orderBy)) {
			list($orderStr, $column, $direction) = explode(' ', $this->orderBy);
			usort($results, function($a, $b) use ($column, $direction) {
				if ($a[$column] == $b[$column]) return 0;
				if ($direction === 'ASC') {
					return ($a[$column] < $b[$column]) ? -1 : 1;
				} else {
					return ($a[$column] > $b[$column]) ? -1 : 1;
				}
			});
		}

		// Applying LIMIT and OFFSET
		if (!empty($this->limit)) {
			$results = array_slice($results, $this->offset, $this->limit);
		}

		return $results;
	}

	public function count(): ?string {
		return count($this->get());
	}

	public function to_sql(): ?string {
		return "Not available for ArrayQueryBuilder.";
	}

	private function compare($itemValue, $operator, $value): bool {
		switch ($operator) {
			case '=':
				return $itemValue == $value;
			case '!=':
				return $itemValue != $value;
			case '>':
				return $itemValue > $value;
			case '<':
				return $itemValue < $value;
			case '>=':
				return $itemValue >= $value;
			case '<=':
				return $itemValue <= $value;
			case 'LIKE':
				return stripos($itemValue, trim($value, '%')) !== false;
			case 'NOT LIKE':
				return stripos($itemValue, trim($value, '%')) === false;
			case 'IN':
				return in_array($itemValue, $value, true);
			case 'NOT IN':
				return !in_array($itemValue, $value, true);
			default:
				return false;
		}
	}

	public function get_query(bool $skipLimit = false): ?string {
		return "Not applicable for ArrayQueryBuilder.";
	}

	public function get_columns(): array {
		if (!empty($this->dataArray)) {
			return array_keys($this->dataArray[0]);
		}
		return [];
	}

	public function where_in($column, $values): ArrayQueryBuilder {
		$this->where[] = ['column' => $column, 'operator' => 'IN', 'value' => $values];
		return $this;
	}

	public function where_not_in($column, $values): ArrayQueryBuilder {
		$this->where[] = ['column' => $column, 'operator' => 'NOT IN', 'value' => $values];
		return $this;
	}

//	public function paginate($rows = 0, $page = 1): array {
//		$this->limit($rows, ($page - 1) * $rows);
//		return $this->get();
//	}


}
