<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
class Account extends DomainModel
{
	public	$id;
	public	$uid;
	public	$status;
	public	$created_on;

	protected $keys		=	array( 'primary' => 'id' );
	protected $table	=	'accounts';
	protected $meta_obj;
	protected $meta_table = 'account_meta';
	protected $meta_fields = array( 'max_users', 'friendly_id' );
	protected $meta_keys = array( 'primary' => array( 'fk_id', 'meta_key' ) );
}
?>
