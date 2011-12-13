<?php
/**
 * a wicked awesome extension to PDO.
 * @author ross paskett - rpaskett@gmail.com
 * @package framework
 */

class mysql extends database
{
	public $err_code, $err_info, $result, $fetch_mode, $option;


	/**
	 * Takes a string as a key and auto-connects to a pre-defined connection
	 * @param string $name
	 */

	public function __construct( &$info )
	{
		// build the connection string and try to establish a connection,
		// otherwise die with honor. this should probably just go to global handler
		try
		{
			$user = $info['username'];
			$pass = $info['password'];
			unset( $info['username'], $info['password'] );

			array_walk( $info, function(&$v, $k) {
				$v = "$k=$v";
			});

			$info = "mysql:{$host}dbname={$info['dbname']};";
			parent::__construct( $info, $user, $pass, array( PDO::ATTR_PERSISTENT => true ) );
		}
		catch( PDOException $e )
		{
			die( 'Error connecting to the database: ', $e->getMessage() );
		}
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
	 * Executes a query using passed params and returns a statement obj
	 */

	public function execute( $query, array $params = array() )
	{
		$this->result = parent::prepare( $query );
		$this->result->execute( $params );

		return $this->result;
	}

}	//	end class db
?>