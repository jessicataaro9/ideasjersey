<?php

namespace security\wordpress\DynamicTables;

use Exception;

class rsssl_dataTable {

	/**
	 * @var mixed
	 */
	public $post;
	/**
	 * @var array|int[]
	 */
	private $paging;
	private $queryBuilder;

	/**
	 * @var array
	 */
	private $validateRaw;

	public function __construct( $POST, QueryBuilder $queryBuilder ) {
		$this->post         = $POST;
		$this->validateRaw  = [];
		$this->queryBuilder = $queryBuilder;
	}


	/**
	 * This class validates all sorting parameters
	 * it validates existence of the parameters and columns
	 *
	 * @throws Exception
	 */
	public function validate_sorting(): rsssl_dataTable {
		// First, we check if the sortColumn and sortDirection are set.
		if ( isset( $this->post['sortColumn']['column'] ) && isset( $this->post['sortDirection'] ) ) {
			// Sanitize the sort column and direction.
			$sort_column    = sanitize_key( $this->post['sortColumn']['column'] );
			$sort_direction = sanitize_key( $this->post['sortDirection'] );

			// Then, we check if the sortColumn is a valid column.
			if (
				! in_array( $sort_column, $this->queryBuilder->get_columns() )
				&& ! in_array( $sort_column, $this->validateRaw )
			) {
				throw new Exception( 'Invalid sort column' );
			}

			// Then, we check if the sortDirection is a valid direction using strict comparison.
			if ( ! in_array( $sort_direction, array( 'asc', 'desc' ), true ) ) {
				throw new Exception( 'Invalid sort direction' );
			}

			$this->queryBuilder->order_by( $sort_column, $sort_direction );
		}

		return $this;
	}

	/**
	 * Fetches the columns from the queryBuilder
	 * @return array
	 * @throws Exception
	 */
	private function get_columns(): array {
		return $this->queryBuilder->get_columns();
	}


	/**
	 * Sets the columns for selection in the query
	 *
	 * @param  array  $array  // the columns to select
	 *
	 * @return $this // returns the class instance
	 * @throws Exception // throws an exception if the column is invalid
	 */
	public function set_select_columns( array $array ): rsssl_dataTable {
		//we loop through the array and check if the column is valid
		// and if the column starts with raw: we exclude it from the check
		$rawColumns = [];
		foreach ( $array as $key => $column ) {
			$column = sanitize_text_field( $column );
			if ( false === strpos( $column, 'raw:' ) ) {
				if ( ! in_array( $column, $this->get_columns() ) ) {
					throw new Exception( 'Invalid column ' . $column . ' in select for table ' . $this->queryBuilder->getTable() . '.' );
				}
			} else {
				//we remove the column from the array and add it to the rawColumns array
				unset( $array[ $key ] );
				$rawColumns[] = str_replace( 'raw:', '', $column );
				//also we tell the query builder that this is a raw column
				$this->queryBuilder->add_raw_column( $column );
			}
		}

		//we get the first array element and add it to the query
		if ( isset( $array[0] ) ) {
			$this->queryBuilder->select_columns( $array[0] );
		}

		//we loop through the rest of the array and add it to the query
		for ( $i = 1; $i < count( $array ); $i ++ ) {
			if ( isset( $array[ $i ] ) ) { // Added check to ensure key exists
				$this->queryBuilder->add_select( $array[ $i ] );
			}
		}

		//we add the raw columns to the query
		foreach ( $rawColumns as $rawColumn ) {
			// Remove curly braces from the raw column, if any
			$rawColumn = str_replace( [ '{', '}' ], '', $rawColumn );
			$this->queryBuilder->add_select( $rawColumn );

			$exploded = explode( ' as ', $rawColumn );
			if (isset($exploded[1])) {
				$columnName = $exploded[1];
				$this->validateRaw[] = $columnName;
			} else {
				throw new Exception("Unexpected rawColumn format: " . $rawColumn);
			}

		}

		return $this;
	}

	/**
	 * Return the results from the query with pagination
	 *
	 * @return array
	 * @throws Exception
	 */
	public function get_results(): array {
		return $this->queryBuilder->paginate( ...$this->paging );
	}

	/**
	 * Validates and sets the pagination parameters
	 *
	 * @return $this
	 * @throws Exception
	 */
	public function validate_pagination(): rsssl_dataTable {
		$perPage = 10;
		$page    = 1;
		//we check if the paging parameters are set
		if ( isset( $this->post['page'] ) ) {
			//we check if the page is a number
			if ( ! is_numeric( $this->post['page'] ) ) {
				throw new Exception( 'Invalid page number' );
			}
			$page = $this->post['page'];
		}

		if ( isset( $this->post['currentRowsPerPage'] ) ) {
			//we check if the perPage is a number
			if ( ! is_numeric( $this->post['currentRowsPerPage'] ) ) {
				throw new Exception( 'Invalid per page number' );
			}
			$perPage = $this->post['currentRowsPerPage'];
		}
		$this->paging = [ $perPage, $page ];

		return $this;
	}

	/**
	 * Validates and sets the search parameters
	 *
	 * @return $this
	 * @throws Exception // throws an exception if the search column is invalid
	 */
	public function validate_search(): rsssl_dataTable {

		if ( isset( $this->post['search'] ) && count( $this->post['searchColumns'] ) > 0 ) {

			//we check if the searchColumns are valid
			foreach ( $this->post['searchColumns'] as $column ) {
				$column = sanitize_text_field( $column );

				if ( ! in_array( $column, $this->get_columns() ) ) {
					//We check if it is in the raw columns
					if ( ! in_array( $column, $this->validateRaw ) ) {
						throw new Exception( 'Invalid search column ' . $column );
					}
				}
			}
			//we add the search to the query
			foreach ( $this->post['searchColumns'] as $column ) {
				//if the column is a raw column we add it as a raw column
				if ( in_array( $column, $this->validateRaw ) ) {
					$this->queryBuilder->or_where( $column, 'LIKE', $this->post['search'] );
					continue;
				}
				$column = sanitize_text_field( $column );
				$this->queryBuilder->or_where( $column, 'LIKE', $this->post['search'] );
			}
		}

		return $this;
	}

	/**
	 * Validates and sets the filter parameters
	 *
	 * @return $this
	 * @throws Exception // throws an exception if the filter column is invalid
	 */
	public function validate_filter(): rsssl_dataTable {
		if ( isset( $this->post['filterValue'] ) && $this->post['filterColumn'] !== '' ) {
			if ( ! in_array( $this->post['filterColumn'], $this->get_columns() ) ) {
				throw new Exception( 'Invalid filter column ' . $this->post['filterColumn'] );
			}

			$ignoredValues = [ 'all', 'All', 'ALL', 'none', 'None', 'NONE' ];

			if ( in_array( $this->post['filterValue'], $ignoredValues ) ) {
				return $this;
			}

			$this->queryBuilder->where( sanitize_text_field( $this->post['filterColumn'] ), '=',
				sanitize_text_field( $this->post['filterValue'] ) );
			//we add the filter to the query
		}

		return $this;
	}

	public function join($table): rsssl_dataTable {
		$this->queryBuilder->join($table);
		return $this;
	}

	public function on($column1, $operator, $column2): rsssl_dataTable {
		$this->queryBuilder->on($column1, $operator, $column2);
		return $this;
	}

	public function as($alias): rsssl_dataTable {
		$this->queryBuilder->as($alias);
		return $this;
	}

	/**
	 * Sets the where clause for the query
	 *
	 * @param  array  $array
	 *
	 * @return $this
	 * @throws Exception
	 */
	public function set_where( array $array ): rsssl_dataTable {
		$this->queryBuilder->where( $array[0], $array[1], $array[2] );

		return $this;
	}

	/**
	 * Set the Where In clause
	 *
	 * @throws Exception
	 */
	public function set_where_in( $column, $array ): rsssl_dataTable {
		$this->queryBuilder->where_in( $column, $array );
		return $this;
	}

	public function group_by( $columns ): rsssl_dataTable {
		if ( ! is_array( $columns ) ) {
			$columns = [ $columns ];
		}
		$this->queryBuilder->group_by( $columns );
		return $this;
	}

	/**
	 * @throws Exception
	 */
	public function set_where_not_in( string $column, array $values ): rsssl_dataTable {
		$this->queryBuilder->where_not_in( $column, $values );
		return $this;
	}

	/**
	 * @throws Exception
	 */
	public function get_query() {
		return $this->queryBuilder->get_query();
	}
}
