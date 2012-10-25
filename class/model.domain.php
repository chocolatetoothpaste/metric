<?php

/**
 * Model
 *
 * @author rosspaskett
 */

namespace Domain;

abstract class Model
{
	protected static $keys, $table, $search, $connection = 'default', $db;
	protected $meta_table, $meta_fields = array(), $meta_obj = array();


	/**
	 * If params are passed, the constructor will attempt to update the object
	 * with a row from the database. For retrieving multiple rows, see collection()
	 * @param mixed $params
	 */

	function __construct( $params = null )
	{
		global $config;

		if( $params )
		{
			$db = \mysql::instance( $config->db[static::$connection] );
			//static::$db = $db;

			$fields = static::getFields();
			$q = new \query;

			if( ! is_array( $params ) ):
				// 99.9% of the time, the first part of the
				// primary key will be the auto_increment field
				$pk = (array) $this->getKeys('primary');
				$pk = $pk[0];
				$params = array( $pk => $params );
			endif;

			$q->select( $fields, $this->getTable() )->where( $params )->query();
			$db->fetchIntoObject( $this, $q );
			$info = $db->stmt->errorInfo();

			if( $info[0] !== '00000' || ! is_null( $info[1] ) )
			{
				throw new \Exception( $info[2], $info[1] );
			}
		}

	}


	/**
	 * Captures passed data and merges it with object properties. for fast and
	 * dirty object updates!
	 * @param array $array
	 */

	public function capture( array &$array, $scrub = array() )
	{
		\array_merge_object( $this, $array, $scrub );
	}

	public function update( array &$array )
	{
		$keys = static::getKeys('primary');
		$keys = array_flip( $keys );

		foreach( $array as $k => $v )
			if( property_exists( $this, $k ) && ! isset( $keys[$k] ) )
				$this->{$k} = $v;
	}


	/**
	 * Builds a query from an object and saves it to the db
	 * @param object $obj
	 */

	public function save()
	{
		global $config;
		$db = \mysql::instance( $config->db[static::$connection] );

		$query = new \query;
		$update = true;
		$criteria = array();
		$table = $this->getTable();
		$keys = $this->getKeys();
		$columns = $this->getFields( true, $this );

		$keys = ( ! empty( $keys['unique'] )
			? array_merge( (array)$keys['primary'], (array)$keys['unique'] )
			: (array)$keys['primary'] );

		$intersect = array_intersect_key( $columns, array_flip( $keys ) );

		array_walk( $intersect, function( $v ) use ( &$update ) {
			if( empty( $v ) )
				$update = false;
		});

		if( $update )
		{
			$columns = array_diff( $columns, $intersect );
			$query->update( $table, $columns )->where( $intersect )->query();
		}
		else
			$query->insert( $table, $columns )->query();

		$db->execute( $query );

		// 00000 means no errors
		if( $db->stmt->errorCode() === '00000' )
		{
			if( ! $update )
				$this->{$keys[0]} = $db->lastInsertId();

			return true;
		}
		else
		{
			$info = $db->stmt->errorInfo();
			throw new \Exception( $info[2], $info[1] );
		}
	}


	public function delete()
	{
		global $config;
		$db = \mysql::instance( $config->db[static::$connection] );
		$table = $this->getTable();
		$keys = $this->getKeys();
		$keys = $keys['primary'];
		$val = array();

		foreach( $keys as $v )
			$val[$v] = $this->$v;

		$q = new \query();

		// when using this delete function in this context, only one row will
		// ever be deleted, so limit statement to 1 row to avoid malicious code
		$q->delete($table)->where($val)->limit(1)->query();

		$db->execute( $q, $val );

		// 00000 means no errors
		if( $db->stmt->errorCode() !== '00000' )
		{
			$info = $db->stmt->errorInfo();
			throw new \Exception( $info[2], $info[1] );
		}
	}


	/**
	 * gets the keys for the table (domain object) via late static binding
	 * @return array
	 */

	final public static function getKeys( $key = false )
	{
		return ( $key ? static::$keys[$key] : static::$keys );
	}


	/**
	 * returns the relative name of the db table for a domain object
	 * @return string
	 */

	final public static function getTable()
	{
		return static::$table;
	}


	final public static function getSearch()
	{
		return static::$search;
	}


	final public static function getConnection()
	{
		return static::$connection;
	}


	final public static function setConnection( $connection )
	{
		static::$connection = $connection;
	}


	/**
	 * returns the name of the db table containing
	 * meta data relating to a domain object
	 * @return string
	 */

	final public function getMetaTable()
	{
		return $this->meta_table;
	}


	/**
	 * return all the fields of the relating meta table for a domain object
	 * @return string
	 */

	final public function getMetaFields()
	{
		return $this->meta_fields;
	}


	/**
	 *
	 * @return array
	 */

	final public function getMetaKeys()
	{
		return $this->meta_keys;
	}


	/**
	 * returns an array of public vars for the called class
	 * @return	array	the array of vars
	 */

	final public static function getFields( $values = false, $obj = null )
	{
			$class = get_called_class();
			$vars = call_user_func('get_class_vars', $class );
			return ( $values
				? array_intersect_key( get_object_vars($obj), $vars )
				: array_keys( $vars ) );
	}


	/**
	 * Returns a Meta domain object
	 * @param	booolean	$refresh	passing true will re-fetch meta fields
	 * from the database and replace the Meta object [optional, default false]
	 * @return	object					a meta object related to the main object
	 */

	public function getMeta( $refresh = false )
	{
		if( $refresh || !( $this->meta_obj instanceof \Domain\Meta ) )
		{
			global $config;
			$db = \mysql::instance( $config->db[static::$connection] );
			$this->meta_obj = new Meta;
			foreach( $this->meta_fields as $prop )
			{
				$this->meta_obj->{$prop} = null;
			}

			$this->meta_obj->setKeys( $this->getMetaKeys() );
			$this->meta_obj->setTable( $this->getMetaTable() );
			$query = new \query;
			$query->select( 'meta_key, meta_value', $this->meta_obj->getTable() )->where( 'fk_id = ?' );
			$query->params[] = $this->id;
			$result = $db->execute( $query );

			if( $result )
			{
				$this->meta_obj->fk_id = $this->id;
				$data = $result->fetchAll( \PDO::FETCH_OBJ );
				foreach( $data as $v )
				{
					$this->meta_obj->{$v->meta_key} = $v->meta_value;
				}
			}
		}

		return $this->meta_obj;
	} // end method getMeta


	/**
	 * Returns a property from private Meta object
	 * @param	string	$key	The property to get
	 * @return	mixed			Returns the property or returns
	 * false if property does not exist
	 */

	public function meta( $key )
	{
		if( !( $this->meta_obj instanceof Meta ) )
		{
			$this->meta_obj = $this->getMeta();
		}
		return ( isset( $this->meta_obj->$key )
			? $this->meta_obj->$key
			: false );
	} // end method meta


	/**
	 * Accepts an array or object of type Meta and will either
	 * assign values or will replace meta object completely
	 * @param	mixed	$meta	an array/object to update the current Meta object with
	 */

	public function setMeta( $meta, $value = '' )
	{
		if( is_array( $meta ) )
		{
			// do a capture of array into meta vars
			$this->meta_obj->capture( $meta );
		}
		elseif( $meta instanceof Meta )
		{
			$this->meta_obj = $meta;
		}
		else
		{
			$message = '\Domain\Model::setMeta() expects parameter 1 to be '
				. 'array or object, ' . gettype( $meta ) . ' given';
			throw new Exception( $message );
		}
	} // end method setMeta


	/**
	 * Get a group of domain objects based on criteria. Basically a db fetchAll,
	 * but returns objects of child domain
	 * @param	array	$params	params for the db query [optional, default array()]
	 * @return	array			an array of objects (of child domain model)
	 */

	// function needs expansion using query object
	// public static function collection( query $query = null )
	public static function collection( $params = array() )
	{
		global $config;
		$q = new \query;
		$db = \mysql::instance( $config->db[static::$connection] );
		$q->select( static::getFields(), static::$table )->where( $params )->query();
		$db->execute( $q );

		if( $db->stmt )
			return $db->stmt->fetchAll( \PDO::FETCH_CLASS, get_called_class() );
	}

}
?>