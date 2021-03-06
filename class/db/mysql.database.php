<?php
namespace Metric\DB;

class Mysql extends Model
{
	public $err_code, $stmt, $fetch_mode, $option;

	public static $err_info = array(
		1054	=>	array( 'code' => 500, 'message' => 'Schema does not match data model' ),
		1048	=>	array( 'code' => 417, 'message' => 'Possible null fields' ),
		1062	=>	array( 'code' => 409, 'message' => 'Conflict detected on unique index' ),
		1146	=>	array( 'code' => 409, 'message' => 'Schema does not exist for data model' )
	);



	/**
	 * Takes a string as a key and auto-connects to a pre-defined connection
	 * @param string $name
	 */

	public function __construct( $info )
	{
		global $config;

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

			$info = 'mysql:' . implode(';', $info);
			parent::__construct( $info, $user, $pass );//, array( \PDO::ATTR_PERSISTENT => true ) );
		}
		catch( \PDOException $e )
		{
			if( $config->DEV )
				die( 'Error connecting to the database: ' . $e->getMessage() . print_r("<br> Connection string: $info", true) );
			else
				error_log('DB CONNECTION ERROR: ' . $e->getMessage() );
		}
	}	//	end function __construct


	/**
	 * Fetches a row from the DB and updates an existing object
	 * @param	object	$obj
	 * @param	object	$query	a query object
	 */

	public function fetchIntoObject( &$obj, \Metric\DB\Query $query )
	{
		$this->stmt = $this->execute( $query );

		if( $this->stmt )
		{
			$this->stmt->setFetchMode( \PDO::FETCH_INTO, $obj );
			$this->stmt->fetch();
		}

		return $this->stmt;
	}


	/**
	 * Gets a row from DB and creates an ORM object from it
	 * @param object $obj
	 * @param object query $q
	 * @return object
	 */

	public function fetchClass( $class, \Metric\DB\Query $query )
	{
		$this->stmt = $this->execute( $query );

		if( $this->stmt )
		{
			//$this->stmt->setFetchMode( \PDO::FETCH_CLASS, $class );
			//$return = $this->stmt->fetch();
			$this->option = $class;
			$this->fetch_mode = \PDO::FETCH_CLASS;
			$return = $this->stmt->fetchObject($class);
		}

		return $return;
	}


	/**
	 * Fetches the next row from a \PDOStatement object stored in $this->stmt
	 * @return mixed
	 */

	public function next()
	{
		return $this->stmt->fetch( \PDO::FETCH_ORI_NEXT );
	}


	/**
	 * Overriding parent function to create a stmt object internally
	 */

	public function prepare( $statement, $driver_options = array() )
	{
		$this->stmt = parent::prepare( $statement, $driver_options );
		return $this->stmt;
	}


	/**
	 * Executes a query using passed params and returns a statement obj
	 */

	public function execute( \Metric\DB\Query $query )
	{
		// only
		// if( empty( $this->stmt ) )
			$this->stmt = parent::prepare( $query->query );
		/*if( ! empty( $params['limit'] ) )
		{
			$limit = (int) $params['limit'];
			$this->stmt->bindParam( ':limit', $limit, \\PDO::PARAM_INT );
			//unset( $params['limit'] );
		}*/

		$this->stmt->execute( $query->params );
		//$this->stmt->execute();
		return $this->stmt;
	}

}	//	end class db
?>