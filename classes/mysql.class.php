<?php
/**
 * a wicked awesome extension to PDO.
 * @author ross paskett - rpaskett@gmail.com
 * @copyright 2010 ross paskett
 * @package framework
 */

class mysql extends database
{

	/**
	 * $results_low - the lower result number returned by $this->paginate()
	 * @var int
	 */
	public $low_result;

	/**
	 * $results_low - the upper result number returned by $this->paginate()
	 * @var int
	 */
	public $high_result;

	/**
	 * $found_rows - total rows found when paginating
	 * @var int
	 */
	public $found_rows;

	/**
	 * $prev_page - the previous page in the result set
	 * @var int
	 */
	public $prev_page;

	/**
	 * $next_page - the next page in the result set
	 * @var int
	 */
	public $next_page;

	/**
	 * $total_pages - total pages found when calculating pagination
	 * @var int
	 */
	public $total_pages;

	public $result, $fetch_mode, $option;


	/**
	 * Takes a string as a key and auto-connects to a pre-defined connection
	 * @param string $name
	 */

	public function __construct( $name )
	{
		global $__db_connections;
		extract($__db_connections[$name]);
		$dsn = "$driver:host=$host;dbname=$dbname;";
		parent::__construct( $dsn, $username, $password );

	}	//	end function __construct


	/**
	 * Fetches a row from the DB and updates an existing object
	 * @param object $obj
	 * @param object query $q
	 */

	public function fetchIntoObject( &$obj, $query, array $params = array() )
	{
		$this->result = $this->execute( $query, $params );

		if( $this->result )
		{
			$this->result->setFetchMode( PDO::FETCH_INTO, $obj );
			$this->result->fetch( PDO::FETCH_INTO );
		}

		return $this->result;
	}


	/**
	 * Gets a row from DB and creates an ORM object from it
	 * @param object $obj
	 * @param object query $q
	 * @return object
	 */

	public function fetchClass( $class, $query, array $params = array() )
	{
		$this->result = $this->execute( $query, $params );

		if( $this->result )
		{
			//$this->result->setFetchMode( PDO::FETCH_CLASS, $class );
			//$return = $this->result->fetch();
			$this->option = $class;
			$this->fetch_mode = PDO::FETCH_CLASS;
			$return = $this->result->fetchObject($class);
		}

		return $return;
	}


	/**
	 * Fetches the next row from a PDOStatement object stored in $this->result
	 * @return mixed
	 */

	public function next()
	{
		$this->result->setFetchMode( $this->fetch_mode, $this->option );
		return $this->result->fetch( $this->fetch_mode, PDO::FETCH_ORI_NEXT );
	}


	/**
	 * Executes an SQL statement using passed params and returns a statement obj
	 */

	public function execute( $query, array $params = array() )
	{
		$this->result = parent::prepare( $query );
		if( $this->result )
			$this->result->execute( $params );
		return $this->result;
	}

}	//	end class db

?>