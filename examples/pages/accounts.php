<?php
/**
 * An interface to manage users
 * @author ross paskett <rpaskett@gmail.com>
 */

//require_once( getenv( 'DOCUMENT_ROOT' ) . '/bootstrap.php' );
$page->authenticate( PERM_ACTION_READ, 'accounts' );
$page->css = array( 'users' );
$query = new query;
$query->select( array('*'), 'accounts' );//, array( 'm.meta_key' => 'friendly_id' ) );
//$query->select( array('u.id', 'u.username', 'm.meta_key', 'm.meta_value'), 'users u INNER JOIN user_meta m ON u.id = m.fk_id' );
//$query->select( array('id', 'code as username'), 'permissions');
$page_num = get( 'page', 1 );

$db = new mysql( DB_NAME_MAIN );
$accounts = $db->paginate( $query, $page_num );

?>