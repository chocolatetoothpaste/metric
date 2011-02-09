<?php

/**
 * Model
 *
 * @author rosspaskett
 */

namespace Domain;

abstract class Model
//abstract class DomainModel
{
	protected static $keys, $table;
	protected $meta_table, $meta_fields = array(), $meta_obj = array();

	
	/**
	 * If params are passed, the constructor will attempt to update the object
	 * with a row from the database. For retrieving multiple rows, see collection()
	 * @param mixed $params
	 */

	function __construct( $params = '' )
	{
		if( $params )
		{
			$db = \mysql::instance( DB_MAIN );
				if( !$db )
					throw new Exception( 'Unable to connect to the database' );

			$keys = $this->getKeys();
			$fields = $this->getFields();
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

			$q->select( $fields, $this->getTable(), $params );
			$db->fetchIntoObject( $this, $q->query, $q->params );
		}

	}


	/**
	 * Captures passed data and merges it with object properties. for fast and
	 * dirty object updates!
	 * @param array $array
	 */

	public function capture( &$array, $scrub = array() )
	{
		array_merge_object( $this, $array, $scrub );
	}


	/**
	 * Builds a query from an object and saves it to the db
	 * @param object $obj
	 */

	public function save()
	{
		var_dump(get_object_vars());
		$db = \mysql::instance( DB_MAIN );
		if( !$db )
			throw new Exception( 'No connection to database' );

		$query = new \query;
		$update = false;
		$params = $columns = $this->getFields(true);
		$criteria = array();
		$keys = (array)$this->keys['primary'];

		foreach( $keys as $k ):
			// if a primary key is found and set in the property list, assume
			// updating an existing row
			if( $this->$k ):
				$update = true;
				$criteria[$k] = $this->$k;
			else:
				unset( $columns[$k] );
			endif;
			unset( $columns[$k] );
		endforeach;

		$sql = ( $update
			? $query->update( $this->table, $columns, $criteria )
			: $query->insert( $this->table, $columns ) );

		// @todo test using the fetchIntoObject method to see if PDO will
		// correctly update a new/existing object
		$db->execute( $sql, $query->params );

		if( $db->result->errorCode() === '00000' )
		{
			if( !$update )
				$this->{$keys[0]} = $db->lastInsertId();

			return true;
		}
		else
		{
			return false;
		}
	}


	public function delete()
	{
		$db = \mysql::instance( DB_MAIN );
		$time = $db->dateTime();
		$params = array(
			'table_name' => $this->table,
			'primary_key' => $this->keys['primary'],
			'key_value' => $this->id,
			'added' => $time
		);
		$query = new query;

		$query->insert( 'dumpster', $params );
		$db->execute( $query->query, $query->params );

		if( $db->result->errorCode() === '00000' )
		{
			return true;
		}
		else
		{
			return false;
		}
	}


	/**
	 * gets the keys for the table (domain object), utilizes late static binding
	 * @return array
	 */

	final public function getKeys()
	{
		return static::$keys;
	}


	/**
	 * returns the relative name of the db table for a domain object
	 * @return string
	 */

	final public function getTable()
	{
		return static::$table;
	}


	/**
	 * returns the name of the db table containing meta data relating to a domain object
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
	 * returns all public properties of an object
	 * @return array array of public properties for $this
	 */

	public static function getFields( $values = false)
	{
		$class = get_called_class();
		$f = function( $obj, $values ){ return ( $values ? get_object_vars( $obj ) : array_keys( get_object_vars( $obj ) ) ); };
		return $f( new $class, $values );
	}


	/**
	 * Returns a Meta domain object
	 * @param	booolean	$refresh	passing true will re-fetch meta fields
	 * from the database and replace the Meta object [optional, default false]
	 * @return	object		$this->meta_obj	a meta object related to the main object
	 */

	public function getMeta( $refresh = false )
	{
		if( $refresh || !( $this->meta_obj instanceof \Domain\Meta ) )
		{
			$db = \mysql::instance( DB_MAIN );
			$this->meta_obj = new Meta;
			foreach( $this->meta_fields as $prop )
			{
				$this->meta_obj->{$prop} = null;
			}

			$this->meta_obj->setKeys( $this->getMetaKeys() );
			$this->meta_obj->setTable( $this->getMetaTable() );
			$query = 'SELECT meta_key, meta_value FROM '
				. $this->meta_obj->table . ' WHERE fk_id = ?';
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
	 * @return	mixed			Returns the property or returns false if property does not exist
	 */

	public function meta( $key )
	{
		if( !( $this->meta_obj instanceof Meta ) )
		{
			$this->meta_obj = $this->getMeta();
		}
		return ( property_exists( $this->meta_obj, $key ) ? $this->meta_obj->$key : false );
	} // end method meta


	/**
	 * Accepts an array or object of type Meta and will either assign values or will replace meta object completely
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
	} // end method setMeta


	/**
	 * Get a group of domain objects based on criteria. Basically a db fetchAll,
	 * but returns objects of child domain
	 * @param	array	$params	params for the db query [optional, default array()]
	 * @return	array	an array of objects (of child domain model)
	 */

	// function needs expansion using query object
	// public static function collection( query $query = null )
	public static function collection( $params = array() )
	{
		$q = new \query;
		$q->select( static::getFields(), static::$table, $params );
		$db = \mysql::instance( DB_MAIN );
		$db->execute( $q->query, $q->params );

		if( $db->result )
			return $db->result->fetchAll( \PDO::FETCH_CLASS, get_called_class() );
	}

}
?>