<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class query
{
	public $params = array(), $criteria = array(),
		$query, $table, $where, $order, $columns;

	/**
	 * Creates a select statement using properties defined before calling method
	 * @return string	the generated query string
	 */

	public function select( $columns, $table )
	{
		$this->table = $table;

		$this->columns = ( is_array( $columns ) ? implode( ', ', $columns ) : $columns );

		$this->query = "SELECT {$this->columns} FROM {$this->table}";

		return $this;
	}

	public function where( $where, $separator = 'AND' )
	{
		if( is_array( $where ) )
		{
			$criteria = array();
			foreach( $where as $k => $v )
			{
				// PDO doesn't like special chars as bound param names, so scrubbing
				// them should reliably maintain unique param names
				$clean = preg_replace('#[^a-zA-Z0-9]#', '', $k);
				$this->params[$clean] = $v;
				$criteria[] = "$k = :$clean";
			}
			// stringify the where statements
			$where = implode( " $separator ", $criteria );
		}

		// check if a previous where statement has been set and glue it all together
		$this->where = ( $this->where ? $this->where . " $separator $where" : $where );

		return $this;
	}

	public function like( array $like, $separator = ' AND ' )
	{
		foreach( $like as $k => &$v )
		{
			$l = "__like_$k";
			$this->params[$l] = "%$v%";
			$like[$k] = "$k LIKE :$l";
		}

		unset($v);
		$like = implode( ' OR ', $like );

		return $this->where( $like, $separator );
	}

	public function order( $order, $dir = 'ASC' )
	{
		if( is_array( $order ) )
			$order = implode( ', ', $order );

		$this->order .= " $order $dir ";

		return $this;
	}

	public function query()
	{
		if( ! empty( $this->where ) )
			$this->query .= " WHERE {$this->where}";
		if( ! empty( $this->order ) )
			$this->query .= " ORDER BY {$this->order}";
		if( ! empty( $this->limit ) )
			$this->query .= " LIMIT {$this->limit}";
		return $this->query;
	}

	public function limit( $limit )
	{
		$this->limit = $limit;
		//$this->params['limit'] = (int)$limit;
		return $this;
	}


	/**
	 * Return a generated insert statement from one or more sets of values
	 * passed as an associative array. Inserting a batch of rows rather than
	 * multiple consecutive inserts will be WAY faster.
	 * @param string $table
	 * @param array $values
	 * @return string
	 */

	public function insert( $table, array $params )
	{
		$columns = array();
		if( is_multi( $params ) )
		{
			$arr = array();
			foreach( $params as $k => $void )
			{
				// sort the keys so that each group of values are inserted in
				// the same order
				ksort( $void );
				if( !$columns )
				{
					$columns = array_keys( $params[0] );
					sort( $columns );
				}

				foreach( $void as $pkey => $pvalue )
				{
					$this->params[$pkey . $k] = $pvalue;//sprintf( ':%s', $pvalue );
					$arr[] = ":{$pkey}{$k}";
				}

				$params[$k] = implode( ', ', $arr );
				$params[$k] = sprintf( '(%s)', $params[$k] );
				$arr = array();
			}

			$params = implode( ', ', $params );

		}
		else
		{
			ksort( $params );
			$this->params = $params;
			$columns = array_keys( $this->params );
			$params = asprintf( ':%1$s', $columns );
			$params = implode( ', ', $params );
			$params = sprintf( '(%s)', $params );
		}

		$columns = sprintf( '(%s)', implode( ', ', $columns ) );
		$this->query = "INSERT INTO $table $columns VALUES $params";

		return $this->query;
	}


	/**
	 * Generates an update statement
	 * @param string $table
	 * @param array $params
	 * @param array $where
	 * @return string
	 */

	public function update( $table, array $params, array $where = array() )
	{
		$this->params = $params + $where;
		$columns = asprintf( '%1$s = :%1$s', array_keys( $params ) );
		$columns = implode( ', ', $columns );
		$where = asprintf( '%1$s = :%1$s', array_keys( $where ) );
		$where = implode( ' AND ', $where );
		$this->query = "UPDATE $table SET $columns WHERE $where";

		return $this->query;
	}

}

?>