<?php

/**
 * Description of DomainModel
 *
 * @author rosspaskett
 */

//namespace Domain;

//	class Model
abstract class DomainModel
{
	protected $keys, $table, $db,
		$meta_table, $meta_fields = array(), $meta_obj = array();

	/**
	 * If params are passed, the constructor will attempt to update the object
	 * with the corresponding row from the database. Presently multiple rows
	 * are not possible since the object can't be cast to array upon creation
	 * @global object $db
	 * @param mixed $params
	 */

	function __construct( $params = '' )
	{
		if( $params )
		{
			$db = mysql::instance( DB_NAME_MAIN );
				if( !$db )
					throw new Exception( 'Unable to connect to the database' );

			$keys = $this->getKeys();
			$fields = array_keys( $this->getFields() );
			$pk = $keys['primary'];
			$q = new query();

			if( !is_array( $params ) ):
				if( is_array( $pk ) )
					$pk = $pk[0];

				$params = array( $pk => $params );

			//	commenting this out for now. it's a pretty abstract concept, needs
			//	some real thought for reliable execution
			//elseif( is_array( $pk ) && array_intersect_key( $pk, $params ) ):
				//$params = array_combine( $pk, $params );

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
		$db = mysql::instance( DB_NAME_MAIN );
		if( !$db )
			throw new Exception( 'No connection to database' );

		$query = new query;
		$update = false;
		$params = $columns = $this->getFields();
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
		$db = mysql::instance( DB_NAME_MAIN );
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
	 * gets the keys for the table (domain object)
	 * @return array
	 */

	final public function getKeys()
	{
		return $this->keys;
	}


	/**
	 * gets the canonical name of the table for a domain object
	 * @return string
	 */

	final public function getTable()
	{
		return $this->table;
	}

	final public function getMetaTable()
	{
		return $this->meta_table;
	}

	final public function getMetaFields()
	{
		return $this->meta_fields;
	}

	final public function getMetaKeys()
	{
		return $this->meta_keys;
	}


	/**
	 * returns all public properties of an object
	 * @return array array of public properties for $this
	 */

	public function getFields()
	{
		$f = function( $obj ){ return get_object_vars( $obj ); };
		return $f( $this );
	}


	/**
	 * Returns a Meta object
	 * @param	booolean	$refresh	passing true will re-fetch meta fields
	 * from the database and replace the Meta object [optional, default false]
	 * @return	object		$this->meta	a meta object related to the main object
	 */

	public function getMeta( $refresh = false )
	{

		if( $refresh || !( $this->meta_obj instanceof Meta ) )
		{
			$db = mysql::instance( DB_NAME_MAIN );
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
				$data = $result->fetchAll(PDO::FETCH_OBJ);
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

}
?>