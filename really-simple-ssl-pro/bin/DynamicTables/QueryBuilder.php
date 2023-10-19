<?php

namespace security\wordpress\DynamicTables;

use Exception;

/**
 * Class QueryBuilder
 * Adds an extra layer of abstraction to the WordPress database and supports pagination
 *
 * @package security\wordpress\DynamicTables
 */
class QueryBuilder {
	private $table;
	private $columns;
	private $fetchedColumns;
	protected $orderBy = '';
	protected $limit = '';
	protected $offset = '';

	protected $join;

	private $rawColumns;

	protected $where;
	private $orWhere;

	protected $where_in;
	protected $where_not_in;

	protected $results = array();
	/**
	 * @var string
	 */
	protected $groupBy;

	public function __construct( $table ) {
		$this->table = $table;
		$this->where = array();
		$this->orWhere = array();
		$this->columns = '*';
		$this->fetchedColumns = array();
		$this->rawColumns = array();
		$this->join = array();
	}

	/**
	 * Sets the columns to select
	 *
	 * @param $columns
	 *
	 * @return $this
	 */
	public function select_columns( $columns ): QueryBuilder {
		$this->columns = $columns;

		return $this;
	}

	/**
	 * Adds columns to the select
	 *
	 * @param $columns
	 *
	 * @return $this
	 */
	public function add_select( $columns ): QueryBuilder {
		$this->columns .= ", $columns";

		return $this;
	}

	public function join($table): QueryBuilder {
		$this->join['table'] = $table;
		return $this;
	}

	public function on($column1, $operator, $column2): QueryBuilder {
		$this->join['on'] = [
			'column1' => $column1,
			'operator' => $operator,
			'column2' => $column2
		];
		return $this;
	}

	public function as($alias): QueryBuilder {
		$this->join['alias'] = $alias;
		return $this;
	}


	/**
	 * Sets the order by
	 *
	 * @param $column
	 * @param  string  $direction
	 *
	 * @return $this
	 * @throws Exception
	 */
	public function order_by( $column, string $direction = 'ASC' ): QueryBuilder {
		$column = $this->validate_column( $column );
		$direction = strtoupper(sanitize_text_field($direction));

		// Checking if the $direction is a valid direction using strict comparison.
		if ( ! in_array( $direction, array( 'ASC', 'DESC' ), true ) ) {
			throw new Exception( 'Invalid sort direction' );
		}
		$this->orderBy = "ORDER BY $column $direction";

		return $this;
	}

	/**
	 * Validates the column and returns a sanitized column name or throws an exception
	 *
	 * @param $column
	 *
	 * @return string
	 * @throws Exception
	 */
	private function validate_column( $column ): string {
		$column = sanitize_text_field($column);
		if ( ! in_array( $column, $this->get_columns() ) ) {
			//it could be this is a raw column name
			if ( ! in_array( $column, $this->rawColumns ) ) {
				throw new Exception( 'Invalid column ' . $column . ' in select for table ' . $this->table . '.' );
			}
		}
		return $column;
	}

	/**
	 * Sets the limit and offset
	 *
	 * @param  int  $limit
	 * @param  int  $offset
	 *
	 * @return $this
	 */
	public function limit( int $limit, int $offset = 0 ): QueryBuilder {
		$this->limit  = $limit;
		$this->offset = $offset;

		return $this;
	}

	/**
	 * Builds, sanitizes and returns the query
	 *
	 * @param  bool  $skipLimit
	 *
	 * @return string|null
	 * @throws Exception
	 */
	public function get_query( bool $skipLimit = false ): ?string {
		global $wpdb;

		$query = "SELECT $this->columns FROM $this->table";

		if (!empty($this->join)) {
			$query .= " INNER JOIN {$this->join['table']} AS {$this->join['alias']} ON {$this->join['on']['column1']} {$this->join['on']['operator']} {$this->join['on']['column2']}";
			// Append $joinQuery to your main SQL query string.

		}

//
		// Handling the where clauses
		if (!empty($this->where)) {
			$query .= " WHERE ";
			$this->handleWhereClauses($query, $this->where);
		}

		if (!empty($this->orWhere)) {
			$query .= empty($this->where) ? " WHERE (" : " AND (";
			$this->handleWhereClauses($query, $this->orWhere, 'OR');
			$query .= ")";
		}


		if ( ! empty( $this->orderBy ) ) {
			$query .= " $this->orderBy";
		}


		// Prepare the WHERE clause using $wpdb->prepare
		if ( ! empty( $this->where ) ) {
			$where_values = array_map( function ( $where ) {
				return $where['value'];
			}, $this->where );
		} else {
			$where_values = array();
		}

		if ( ! empty( $this->orWhere ) ) {
			$orWhere_values = array_map( function ( $orWhere ) {
				return  $orWhere['value'] ;
			}, $this->orWhere );
		} else {
			$orWhere_values = array();
		}


		if (!empty($this->groupBy)) {
			$query .= " $this->groupBy";
		}

		if ( ! $skipLimit ) {
			if ( ! empty( $this->limit ) ) {
				$query .= " LIMIT $this->limit";
			}

			if ( ! empty( $this->offset ) ) {
				$query .= " OFFSET $this->offset";
			}
		}


		$where_values = array_merge( $where_values, $orWhere_values );

		//we add the join

		return $wpdb->prepare( $query, $where_values );
	}

	/**
	 * Handles the where clauses
	 *
	 * @param $query
	 * @param $clauses
	 * @param  string  $connector
	 *
	 * @throws Exception
	 */
	private function handleWhereClauses(&$query, $clauses, string $connector = 'AND') {
		foreach ($clauses as $index => $clause) {
			if ($index > 0) {
				$query .= " $connector ";
			}
			$column = $this->validate_column($clause['column']);
			$operator = $this->validate_operator($clause['operator']);
			$query .= "{$column} {$operator} %s";
		}
	}


	/**
	 * Returns the results
	 *
	 * @return array|object|null
	 * @throws Exception
	 */
	public function get() {
		global $wpdb;
		$this->results = $wpdb->get_results( $this->get_query() );

		return $this->results;
	}

	/**
	 * Returns the query
	 *
	 * @return string|null
	 */
	public function to_sql(): ?string {
		return $this->get_query();
	}

	/**
	 * Returns the count
	 *
	 * @return string|null
	 */
	public function count(): ?string {
		global $wpdb;
		$query      = $this->get_query( true );
		$countQuery = "SELECT COUNT(*) as count FROM ($query) as subquery";

		return $wpdb->get_var( $countQuery );
	}


	/**
	 * Adds a where clause
	 *
	 * @param $column
	 * @param $operator
	 * @param $value
	 *
	 * @return $this
	 * @throws Exception
	 */
	public function where( $column, $operator, $value ): QueryBuilder {
		//sanitizing the values
		$operator = $this->validate_operator( $operator );
		$value = sanitize_text_field( $value );
		if ( $operator === 'LIKE' || $operator === 'NOT LIKE' ) {
			$value = "%$value%";
		}
		$column = $this->validate_column( $column );

		$this->where[] = array( 'column' => $column, 'operator' => $operator, 'value' => $value );
		return $this;
	}

	/**
	 * Adds an or where clause
	 *
	 * @param $column
	 * @param $operator
	 * @param $value
	 *
	 * @return $this
	 * @throws Exception
	 */
	public function or_where( $column, $operator, $value ): QueryBuilder {
		//sanitizing the values
		$operator = $this->validate_operator( $operator );
		$value = sanitize_text_field( $value );
		//if the operator is like we add the % to the value
		if ( $operator === 'LIKE' || $operator === 'NOT LIKE' ) {
			$value = "%$value%";
		}
		$column = $this->validate_column( $column );

		$this->orWhere[] = array( 'column' => $column, 'operator' => $operator, 'value' => $value );
		return $this;
	}

	/**
	 * Adds a where in clause
	 *
	 * @param $column
	 * @param $values
	 *
	 * @return self
	 * @throws Exception
	 */
	public function where_in( $column, $values ) {
		global $wpdb;
		//sanitizing the values
		$column = $this->validate_column( $column );
		$values = array_map( 'sanitize_text_field', $values );

		$this->where_in[] = $this->where_in( $column, $values );

		return $this;
	}

	/**
	 * Adds a where not in clause
	 *
	 * @param $column
	 * @param $values
	 *
	 * @return self
	 * @throws Exception
	 */
	public function where_not_in( $column, $values ): QueryBuilder {
		global $wpdb;
		//sanitizing the values
		$column = $this->validate_column( $column );
		$values = array_map( 'sanitize_text_field', $values );

		$this->where_not_in[] = $this->where_not_in( $column, $values );

		return $this;
	}

	/**
	 * gets a single result
	 *
	 * @return mixed|null
	 */
	public function first() {
		$this->limit( 1 );
		$result = $this->get();

		return $result[0] ?? null;
	}

	/**
	 * Paginates the results
	 *
	 * @param $rows
	 * @param $page
	 *
	 * @return array
	 * @throws Exception
	 */
	public function paginate( $rows = 0, $page = 0 ): array {
		$page = max( 1, intval( $page ) );
		$rows = max( 1, intval( $rows ) );

		$this->limit( $rows, ( $page - 1 ) * $rows );
		$results  = $this->get();
		$total    = $this->count();
		$lastPage = ceil( $total / $rows );

		return [
			'data'       => $results,
			'pagination' => [
				'totalRows'   => $total,
				'perPage'     => $rows,
				'currentPage' => $page,
				'lastPage'    => $lastPage,
			],
			// if the debug option in WordPress is set to true, the query will be returned
			'query'      => $this->to_sql(), // - uncomment this line if you want to see the query
		];
	}

	public function get_columns(): array {
		//we check if the columns are already set
		if ( ! empty( $this->fetchedColumns ) ) {
			return $this->fetchedColumns;
		}

		global $wpdb;
		$query  = "SHOW COLUMNS FROM $this->table";
		$result = $wpdb->get_results( $query );

		return array_column( $result, 'Field' );
	}

	public function getTable() {
		return $this->table;
	}

	/**
	 * Validates the operator
	 *
	 * @param $operator
	 *
	 * @return string
	 * @throws Exception
	 */
	private function validate_operator( $operator ): string {
		$operator = strtoupper(sanitize_text_field($operator));
		if ( ! in_array( $operator, array( '=', '!=', '>', '<', '>=', '<=', 'LIKE', 'NOT LIKE' ), true ) ) {
			throw new Exception( 'Invalid operator' );
		}

		return $operator;
	}

	/**
	 * extract column name form a raw string value
	 * @param $column
	 *
	 * @return string|null
	 */
	public function extract_column_name( $column ): ?string {
		$pattern = '/\s+as\s+(\w+)/';
		if (preg_match($pattern, $column, $matches)) {
			return $matches[1];
		}
		return null;
	}

	public function add_raw_column( string $column ) {
		$this->rawColumns[] = sanitize_text_field($this->extract_column_name( $column ));
	}

	/**
	 * Sets the group by clause
	 *
	 * @param  array  $columns
	 *
	 * @return $this
	 * @throws Exception
	 */
	public function group_by( array $columns ): QueryBuilder {
		$column = implode(', ', array_map(function($column) {
			return $this->validate_column($column);
		}, $columns));
		$this->groupBy = "GROUP BY $column";

		return $this;
	}
}
