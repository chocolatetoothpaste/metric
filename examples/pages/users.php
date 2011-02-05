<?php
/**
 * An interface to manage users
 * @author ross paskett <rpaskett@gmail.com>
 */

//require_once( getenv( 'DOCUMENT_ROOT' ) . '/bootstrap.php' );
$page->authenticate( PERM_ACTION_READ, 'users' );
$page->css = array( 'users' );
/*$query = new query;
$query->select( array('id', 'username'), 'users' );
//$query->select( array('u.id', 'u.username', 'm.meta_key', 'm.meta_value'), 'users u INNER JOIN user_meta m ON u.id = m.fk_id' );
//$query->select( array('id', 'code as username'), 'permissions');
$page_num = get( 'page', 1 );

$db = mysql::instance( DB_NAME_MAIN );
$results = $db->paginate( $query, $page_num );

*/
///
//
$response = json_decode( $page->request( URL_API_REST . '/users' ) );
/*/
$response = json_decode( $page->request( URL_API_REST . '/users', 'GET', array( 'page' => 2, 'limit' => 1 ) ) );
//*/

$users =& $response->data;
//var_dump($response);
?>