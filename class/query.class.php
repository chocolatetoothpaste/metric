<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class query
{
	public	$params = array(), $criteria = array(), $query, $table, $where, $order;

	final function __construct()
	{
		/*
		if( $obj )
		{
			$this->object = $obj;
			$this->columns = array_keys( get_object_vars( $obj ) );
			$this->table = $obj->getTable();
		}*/

		/**
		 * This could be a sweet start to doing joins and stuff through php
		 */

		/*foreach( func_get_args() as $obj )
		{
			$class = get_class( $obj );
			$this->columns[$class] = array_keys( get_object_vars( $obj ) );
			$this->table[$class] = $obj->getTable();
		}*/
		//var_dump($this);
	}


	/**
	 * Creates a select statement using properties defined before calling method
	 * @return string	the generated query string
	 */

	public function select( array $columns, $table, array $where = array() )
	{
		$criteria = array();
		$this->table = $table;

		if( !$this->where && $where )
		{
			foreach( $where as $k => $v )
			{
				// PDO doesn't like special chars as bound param names, so scrubbing
				// them should reliably maintain unique param names
				$f = preg_replace('#[^a-zA-Z0-9]#', '', $k);
				$this->params[$f] = $v;
				$criteria[] = "$k = :$f";
			}
			$this->where = 'WHERE ' . implode( ' AND ', $criteria );
		}
		elseif( $this->where )
		{
			$this->where = "WHERE {$this->where}";
		}
		
		if( $this->order )
			$this->order = "ORDER BY {$this->order}";

		$columns = implode( ', ', $columns );
		$this->query = "SELECT $columns FROM $table {$this->where} {$this->order}";

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
