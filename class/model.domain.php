<?php

/**
 * Model
 *
 * @author rosspaskett
 */

namespace Domain;

abstract class Model
{
	protected static $keys, $table, $search;
	protected $meta_table, $meta_fields = array(), $meta_obj = array();


	/**
	 * If params are passed, the constructor will attempt to update the object
	 * with a row from the database. For retrieving multiple rows, see collection()
	 * @param mixed $params
	 */

	function __construct( $params = '' )
	{
		global $config;
		if( $params )
		{
			$db = \mysql::instance( $config->db[$config->DB_MAIN] );

			$keys = $this->getKeys();
			$fields = static::getFields();
			$pk = $keys['primary'];
			$q = new \query();

			if( !is_array( $params ) ):
				if( is_array( $pk ) )
					$pk = $pk[0];

				$params = array( $pk => $params );

			elseif( is_array( $pk ) && array_intersect_key( $pk, $params ) ):
				$params = array_combine( $pk, $params );


				/**
				 * Not ready for this yet
				 *
				 * $key = array_search( '*', $q->params );
				 * unset( $q->params[$key]);
				 */

			endif;

			$q->select( $fields, $this->getTable() )->where( $params );
			$db->fetchIntoObject( $this, $q->query(), $q->params );
		}

	}


	/**
	 * Captures passed data and merges it with object properties. for fast and
	 * dirty object updates!
	 * @param array $array
	 */

	public function capture( &$array, $scrub = array() )
	{
		\array_merge_object( $this, $array, $scrub );
	}


	/**
	 * Builds a query from an object and saves it to the db
	 * @param object $obj
	 */

	public function save()
	{
		global $config;
		$db = \mysql::instance( $config->db[$config->DB_MAIN] );

		$query = new \query;
		$update = false;
		$criteria = array();
		$table = $this->getTable();
		$keys = $this->getKeys();
		$columns = $this->getFields( true, $this );

		$keys = ( !empty( $keys['unique'] )
			? array_merge((array)$keys['primary'], (array)$keys['unique'])
			: (array)$keys['primary'] );

		foreach( $keys as $type => $k ):
			// if a primary key has a value in the
			// property list, update an existing row
			if( !empty( $this->$k ) )
			{
				if( $type == 'primary' )
				{
					$update = true;
					$criteria[$k] = $this->$k;
					unset( $columns[$k] );
				}
				elseif( $update == true )
				{
					unset( $columns[$k] );
				}
			}
			else
			{
				unset( $columns[$k] );
			}
		endforeach;

		$sql = ( $update
			? $query->update( $table, $columns, $criteria )
			: $query->insert( $table, $columns ) );

		$db->execute( $sql, $query->params );
		//error_log($db->result->errorCode());

		// 00000 means no errors
		if( $db->result->errorCode() === '00000' )
		{
			if( !$update )
				$this->{$keys[0]} = $db->lastInsertId();

			return true;
		}
		else
		{
			error_log( 'Domain error::' . get_class($this)
					   . ' - ' . $db->result->errorCode() . ' :: ' . print_r($db->result->errorInfo(), true ) );
			$info = $db->result->errorInfo();

			if( $db->result->errorCode() == '42S22' )
			{
				$message = 'Schema does not match data model ' . \get_called_class();
				$code = $config->HTTP_INTERNAL_SERVER_ERROR;
			}
			else if( $config->DEV )
			{
				$message = json_encode( $info );
				$code = $config->HTTP_INTERNAL_SERVER_ERROR;
			}
			else
			{
				$message = 'Unable to create resource';
				$code = $config->HTTP_NOT_ACCEPTABLE;
			}

			throw new \Exception( $message, $code );
		}
	}


	public function delete()
	{
		global $config;
		$db = \mysql::instance( $config->db[$config->DB_MAIN] );
		$table = $this->getTable();
		$keys = $this->getKeys();
		$key = ( is_array( $keys['primary'] )
			? $keys['primary'][0]
			: $keys['primary'] );
		$q = "DELETE FROM $table WHERE $key = ? LIMIT 1";
		$db->execute($q, array($this->{$key}));

		return ( $db->result->errorCode() === '00000' ? true : false );
	}


	/**
	 * gets the keys for the table (domain object) via late static binding
	 * @return array
	 */

	final public static function getKeys()
	{
		return static::$keys;
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
			$db = \mysql::instance( $config->db[$config->DB_MAIN] );
			$this->meta_obj = new Meta;
			foreach( $this->meta_fields as $prop )
			{
				$this->meta_obj->{$prop} = null;
			}

			$this->meta_obj->setKeys( $this->getMetaKeys() );
			$this->meta_obj->setTable( $this->getMetaTable() );
			$query = 'SELECT meta_key, meta_value FROM '
				. $this->meta_obj->getTable() . ' WHERE fk_id = ?';
			$result = $db->execute( $query, array( $this->id ) );

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
		$q->select( static::getFields(), static::$table, $params );
		$db = \mysql::instance( $config->db[$config->DB_MAIN] );
		$db->execute( $q->query, $q->params );

		if( $db->result )
			return $db->result->fetchAll( \PDO::FETCH_CLASS, get_called_class() );
	}

}
?>