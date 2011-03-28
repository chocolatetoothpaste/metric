<?php
/**
 *
 * @author ross paskett
 */

abstract class database extends PDO
{
	abstract public function fetchIntoObject( &$obj, $query, array $params = array() );
	abstract public function fetchClass( $class, $query, array $params = array() );
	abstract public function next();
	abstract public function execute( $query, array $params = array() );
	private static $instance = array();


	/**
	 * Returns an instance of the database, separates database driver and db connection name as defined in config.inc.php
	 * @param string $db_name
	 * @return object
	 */

	public static function instance( $db_info )
	{
		$name = md5( implode( ';', $db_info ) );
		$class = get_called_class();
		if( !array_key_exists( $class, self::$instance ) || !array_key_exists( $name, self::$instance[$class] ) )
		{
			self::$instance[$class][$name] = new $class( $db_info );
		}

		return self::$instance[$class][$name];
    }


	/**
	 * Returns a date/time string in sql-friendly format
	 * @param int $time
	 * @return string
	 */

	public function dateTime( $time = -1 )
	{
		return date('Y-m-d H:i:s', ( $time === -1 ? time() : $time ) );
	}


	/**
	 * takes an array of fields and prepares them for use in a query statement
	 * @param array $fields
	 * @return array
	 */

	public function prepareFields( &$data )
	{
		$return = array();
		foreach( $data as $k => $v )
			$return[] = "{$k} = :{$k}";

		return $return;
	}


	/**
	 * Recursively scrubs an array or string of illegal characters
	 * @param mixed $string
	 */

	public function sanitize( $string, $ignore = array() )
	{
		if ( is_array( $string ) )
		{
			$ignore = array_flip( $ignore );
			foreach ( $string as $key => $value )
			{
				if( !array_key_exists( $key, $ignore ) )
					$string[$key] = $this->sanitize( $value, $ignore );
			}
			return $string;
		}
		return parent::quote( $string );
	}	//	end function sanitize()


	/**
	 * Automagically paginates a query and returns a condensed set of results
	 * @param string $query
	 * @param int $page
	 * @param int $results
	 */

	public function paginate( query $query, $page = 1, $results = 10, $calc_rows = true )
	{
		$offset = ( $page - 1 ) * $results;
		$sql = "$query->query LIMIT $offset, $results";
		$stmt = $this->execute( $sql, $query->params );
		$return = $stmt->fetchAll(PDO::FETCH_ASSOC);

		if( $return )
		{
			$this->low_result = ( $page - 1 ) * $results + 1;
			$num = $page * $results;

			/*
			 * Would be nice to cache the row count so it isn't calculated with each page load
			 *
			 * if( keyAndValue( $_SESSION, array( 'db', 'found_rows' ) ) )
				$this->found_rows = $_SESSION['db']['found_rows'];
			else*/
				$this->found_rows = $this->execute( 'SELECT count(*) FROM ' . $query->table )->fetchColumn();

			$this->total_pages = ceil( $this->found_rows / $results );

			$this->high_result = ( $this->found_rows < $num
				? $this->found_rows : $num );

			$this->prev_page = ( $page <= 1 ? false : $page - 1 );
			$this->next_page = ( $page == $this->total_pages ? false : $page + 1 );
		}

		unset( $results, $page, $min, $sql, $result, $row, $num );
		return $return;
	}	//	end function paginate()

}

?>
