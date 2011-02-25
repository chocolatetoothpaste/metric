<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Domain;
class Meta extends Model
{
	//protected static $table, $keys;
	//public $id, $fk_id;
	//protected $keys = array( 'primary' => array( 'id', 'fk_id' ) );

	public function __construct( $params = '' )
	{
		if( $params )
		{
			$db = \mysql::instance( DB_NAME_MAIN );
			$keys = $this->getKeys();
			$fields = $this->getFields();
			$pk = $keys['primary'];
			$q = new \query();

			if( !is_array( $params ) )
			{
				$this->fk_id = $params;
				if( is_array( $pk ) )
					$pk = $pk[1];

				$params = array( $pk => $params );
			}
			elseif( is_array( $pk ) && array_intersect_key( $pk, $params ) )
			{
				$params = array_combine( $pk, $params );
				foreach( $params as $k => $v )
					$this->$k = $v;
				/**
				 * Not ready for this yet
				 *
				 * $key = array_search( '*', $q->params );
				 * unset( $q->params[$key]);
				 */
			}
			else
			{
				$params = $params;
				foreach( $params as $k => $v )
					$this->$k = $v;
			}

			$q->select( $fields, $this->getTable(), $params );
			$result = $db->execute( $q->query, $q->params );

			if( $result )
				foreach( $db->result->fetchAll(PDO::FETCH_ASSOC) as $value )
					$this->{$value['meta_key']} = $value['meta_value'];

		}
	}

	public function setKeys( $keys )
	{
		static::$keys = $keys;
	}

	public function setTable( $table )
	{
		static::$table = $table;
	}

	public function save()
	{
		$db = \mysql::instance( DB_NAME_MAIN );
		$query = new \query();
		$columns = array();
		$update = false;
		$fields = $this->getFields();
		$table = $this->getTable();
		$keys = $this->keys['primary'];

		foreach( $keys as $k )
		{
			if( property_exists( $this, $k ) )
			{
				unset( $fields[$k] );
			}
		}

		$update = false;

		foreach( $fields as $k => $v )
		{
			$columns[] = array(
				'meta_key' => $k,
				'meta_value' => $v,
				'fk_id' => $this->fk_id
			);
		}

		$sql = array();

		foreach( $columns as $c )
		{
			$query->insert( $table, $c );

			$result = $db->execute( $query->query, $query->params );

			if( $result->errorCode() == '23000' )
			{
				$query->update( $table, $c, array( 'fk_id' => '', 'meta_key' => '' ) );
				$db->execute( $query->query, $query->params );
			}
			$sql[] = $query;
		}

		return $sql;
	}

}

?>