<?php

/**
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

//namespace Domain;

//	class User extends Model
class User extends DomainModel
{
	public	$id;
	public	$username;
	public	$email;
	public	$type		=	'2';
	public	$enabled	=	'1';

	protected $keys			= array( 'primary' => 'id', 'unique' => 'username' );
	protected $table		= 'users';
	protected $meta_table	= 'user_meta';
	protected $meta_fields	= array( 'first_name', 'last_name', 'middle_name' );
	protected $meta_keys	= array( 'primary' => array( 'fk_id', 'meta_key' ) );
	protected $meta_obj;

	private		$permissions;
	private		$addresses = array();
	private		$emails = array();
	private		$phones = array();


	/**
	 * Does all required actions to log a user in
	 */

	public function login()
	{
		$this->last_login = time();
		$this->save();
		$this->fetchPermissions();
		// php FREAKS OUT if you don't unset $this->db and try to make it a session var
		unset( $this->last_login );
	} // end method login


	/**
	 * Fetches a users permissions from the DB and assigns them as stdClass properties
	 * @global object $db
	 */

	public function fetchPermissions()
	{
		$db = mysql::instance( DB_NAME_MAIN );
		$this->permissions = new stdClass;

		$query = '
			SELECT
				p.id, p.code, r.value
			FROM
				permissions p
			INNER JOIN
				user_permission_relation r
			ON
				r.permission_id = p.id
			WHERE r.user_id = ?';

		$results = $db->execute( $query, array( $this->id ) )->fetchAll( PDO::FETCH_OBJ );

		foreach( $results as $v )
			$this->permissions->{$v->code} = $v->value;
	}


	/**
	 * check if a user has sufficent permission to access a component.
	 * If only a bit is passed, checks if user is a certain type.
	 * If bit and permission is passed, checks if user has sufficent privileges
	 * @param	int	$bit	access level to check user against
	 */

	public function authenticate( $bit = 0, $permission = '' )
	{
		if( $this->type ^ 0 && $this->id && $this->username && $bit >= 0 )
		{
			if( $permission )
				return ( setAndValue( $this->permissions->$permission ) & $bit );
			elseif( $bit !== 0 )
				return  $bit & $this->type;
			else
				return true;
		}
		return false;
	}


	/**
	 * Saves the object to the database
	 */

	public function save()
	{
		// shortcut so last_modified doesn't have to be set everytime the object is saved
		$this->last_modified = time();
		return parent::save();
	}

}

?>