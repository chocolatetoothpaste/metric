<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class query
{
	public $params = array(), $query, $table, $where, $order = array(), $columns;

	/**
	 * Creates a select statement using properties defined before calling method
	 * @return string	the generated query string
	 */

	public function select( $columns, $table )
	{
		$this->table = $table;

		$this->columns = ( is_array( $columns )
			? implode( ', ', $columns )
			: $columns );

		$this->query = "SELECT {$this->columns} FROM {$this->table}";

		return $this;
	}

	public function where( $where, $separator = 'AND' )
	{
		if( is_array( $where ) && count( $where ) > 0 )
		{
			$criteria = array();
			foreach( $where as $k => $v )
			{
				// PDO doesn't like special chars as bound param names
				// scrubbing them should reliably maintain unique param names
				$clean = preg_replace('#[^a-zA-Z0-9_]#', '', $k);
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

	public function in( array $ins )
	{
		$in = array();
		foreach( $ins as $v )
		{
			$k = "__in_$v";
			$this->params[$k] = $v;
			$in[] = ":$k";
		}
		$in = implode( ',', $in );
		$this->where( "IN ($in)", '' );

		return $this;
	}

	public function like( array $like, $separator = 'AND' )
	{
		foreach( $like as $k => &$v )
		{
			$l = "__like_$k";
			$this->params[$l] = "%$v%";
			$like[$k] = "$k LIKE :$l";
		}

		unset($v);

		$like = implode( ' OR ', $like );
		$like = "($like)";

		return $this->where( $like, $separator );
	}

	public function between( $one, $two )
	{
		// a unique id of some kind is required to dsitinguish fields, so a md5
		// hash of the current where clause should give one each time
		$column = md5( $ths->where );
		$k = "__between_{$column}_";

		// replace non-alpha-numeric (plus underscore) characters in the values
		// and use those as part of the unique field name (otherwise there the
		// values would collide)
		$k1 = preg_replace('#[^a-zA-Z0-9_]#', '', "{$k}{$one}");
		$k2 = preg_replace('#[^a-zA-Z0-9_]#', '',"{$k}{$two}");

		$this->params[$k1] = $one;
		$this->params[$k2] = $two;
		$this->where( " BETWEEN :$k1 AND :$k2 ", '' );

		return $this;
	}

	public function group( $group, $direction = 'ASC' )
	{
		if( is_array( $group ) )
			$group = implode( ', ', $group );

		$this->group[] = "$group $direction";

		return $this;
	}

	public function order( $order, $direction = 'ASC' )
	{
		if( is_array( $order ) )
			$order = implode( ', ', $order );

		$this->order[] = "$order $direction";

		return $this;
	}

	public function limit( $limit, $offset = 0 )
	{
		$this->limit = "$offset, $limit";
		return $this;
	}


	/**
	 * Compile each part together and generate a valid SQL statement
	 */

	public function query()
	{
		if( ! empty( $this->where ) )
			$this->query .= " WHERE {$this->where}";
		if( ! empty( $this->group ) )
			$this->query .= ' GROUP BY ' . implode( ', ', $this->group );
		if( ! empty( $this->order ) )
			$this->query .= ' ORDER BY ' . implode( ', ', $this->order );
		if( ! empty( $this->limit ) )
			$this->query .= " LIMIT {$this->limit}";
		return $this->query;
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
				if( ! $columns )
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

		$columns = sprintf( '(%s)', '`' . implode( '`, `', $columns ) . '`' );
		$this->query = "INSERT INTO $table $columns VALUES $params";

		return $this;
	}


	/**
	 * Generates an update statement
	 * @param string $table
	 * @param array $params
	 * @param array $where
	 * @return string
	 */

	public function update( $table, $params )
	{
		// if( is_array( $params ) )
		// {
			$this->params = $params;
			$columns = asprintf( '`%1$s` = :%1$s', array_keys( $params ) );
			$columns = implode( ', ', $columns );
		// }
		// else
		// {
		// 	$columns = $params;
		// }
		$this->query = "UPDATE $table SET $columns";

		return $this;
	}


	/**
	 * Creates a select statement using properties defined before calling method
	 * @return string	the generated query string
	 */

	public function delete( $table )
	{
		$this->table = $table;
		$this->query = "DELETE FROM {$this->table}";

		return $this;
	}

}

?>