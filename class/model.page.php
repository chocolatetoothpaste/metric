<?php
namespace Metric\Page;

abstract class Model
{
	// require page classes to define this function
	abstract public function init();


	public $file, $view, $body, $hash, $request;
	public $template = false;
	public $title = '';
	public $js = array(), $css = array();
	public $params = array(), $data = array();
	public $content_type = 'text/html; charset=utf-8';
	public $visibility = 'public';

	private $cache = false;


	/**
	 * Parses $request and tries to find a matching controller. Looks in the
	 * controller directory as well as at an array of aliases the resolve to
	 * physical files on disk
	 * @param string $request
	 */

	final public static function load( $request )
	{
		global $config;

		// check if the page is in the public dir, protected pages dir, or if
		// a "view" exists. finally, check if request is defined in config file
		// as a service or alias to a file
		if( file_exists( $config->PATH_CONTROLLER . $request . '.php' ) )
			$file = $config->PATH_CONTROLLER . $request . '.php';
		elseif( file_exists( $config->PATH_VIEW . $request . '.php' ) )
			$file = $config->PATH_VIEW . $request . '.php';
		elseif( ! empty( $config->alias[$request] )
		&& file_exists( $config->alias[$request] ) )
			$file = $config->alias[$request];
		else
			$file = $config->PAGE_404;

		// load the page controller...
		require_once( $file );

		$view = pathinfo( $file, PATHINFO_FILENAME );
		$view = "{$config->PATH_VIEW}/{$this->view}.phtml";

		if( file_exists( $view ) )
			$this->view = $view;
	}	// end method load


	/**
	 * Set the template file to be loaded
	 */

	public function template( $t )
	{
		global $config;
		$this->template = $t;
	}


	/**
	 * Compile an array of JS/CSS files to load in a somewhat logical manner
	 * This function is called from static::js() and static::css() and should
	 * really only matter to these functions
	 */

	private function assets( $prev, $args )
	{
		$prepend = end( $args );

		if( $args ) {
			if( is_bool( $prepend ) === true && $prepend === true ) {
				array_pop( $args );
				$prev = array_merge( $args, $prev );
			}

			else
				$prev = array_merge( $prev, $args );
		}

		return $prev;
	}


	/**
	 * Load page specific scripts passed as function args
	 */

	public function js()
	{
		$args = func_get_args();

		if( $args )
			$this->js = $this->assets( $this->js, $args );

		return $this->js;
	}


	/**
	 * Load page specific styles passed as function args
	 */

	public function css()
	{
		$args = func_get_args();

		if( $args )
			$this->css = $this->assets( $this->css, $args );

		return $this->css;
	}


	/**
	 * Sets up the page and displays it
	 * @param string $contents
	 * @global object $auth
	 */

	public function render()
	{
		global $config;
		// in the past, there have been a couple (minor) issues with the
		// browser trying to download the page rather than render it,
		// this header should fix this.
		header( "Content-Type: {$this->content_type}" );

		ob_start();

		// load the view, if there is one. late check allows the controller to
		// specify a view file and produce an error if path is somehow incorrect
		if( file_exists( $this->view ) )
			require_once( $this->view );
		else
			// since this framework does not require a view file, and in the
			// event one does not exist, set view to null to allow apps to
			// check the state of the view file
			$this->view = null;

		$this->body = ob_get_clean();

		ob_start();

		if( ! $this->template )
			echo $this->body;
		else
			require( $this->template );


		// cache the page if the stars are aligned (no errors),
		// because caching an errored page would be stupid
		if( $this->cache && strlen( $this->body ) && ! error_get_last() )
		{
			$date = strtotime( '+1 month' );
			$date = gmdate( DATE_RFC1123, $date );
			header( "Expires: {$date}" );

			// check if the cache directory is writable
			// and attempt to cache page output
			if( is_writable( dirname( $this->cache ) ) )
				file_put_contents( $this->cache,
					ob_get_contents(), LOCK_EX );
		}

		// display page, even if it doesn't get cached
		ob_end_flush();

	}	// end method render


	/**
	 * Caches the output of a page
	 */

	public function cache( $request = null, $mtime = 0 )
	{
		global $config;

		// allow request to be passed as arg to accommodate customized pages
		// to be cached, otherwise cache the default request
		if( ! $request )
			$request = $this->request;

		// grab the most recent mtime of a file/files, create a hash
		if( ! $mtime )
			$mtime = max( filemtime( $this->file ), filemtime( $this->view ) );

		$this->hash = md5( $request ) . "-{$mtime}";
		$this->cache = $config->PATH_CACHE . "/{$this->hash}";

		header( "Cache-Control: {$this->visibility}", false );
		header( "Etag: {$this->hash}" );
		header( 'Pragma: cache' );

		// check if user has a local cached file
		// else check for a server cached file
		// else generate a new file and if possible cache it
		if( ! empty( $_SERVER['HTTP_IF_NONE_MATCH'] )
			&& $_SERVER['HTTP_IF_NONE_MATCH'] === "$this->hash" )
		{
			header( $config->http_status[$config->HTTP_NOT_MODIFIED] );
			die;
		}
		else if( file_exists( $this->cache ) && filesize( $this->cache ) > 0 )
		{
			header( "X-Cache-Loaded: {$this->hash}" );
			echo file_get_contents( $this->cache );
			die;
		}
		else
		{
			ob_start();
		}
	}


	/**
	 * Redirects to another page, safe fall back if headers are sent
	 * @access	public
	 * @param	string	$url
	 */

	public static function redirect( $url )
	{
		global $config;

		// write session to the disk
		session_write_close();

		// option to die before redirecting the page, useful when debugging
		// @see config.inc.php
		if( $config->DIE_BEFORE_REDIRECT )
			die( 'Dying before redirect, <a href="' . $url
				. '">click here</a> to continue.' );

		if( ! headers_sent() )
			header( "Location: $url" );
		else
		{
			echo '<script type="text/javascript">
					document.location.replace("', addslashes( $url ), '");
				</script>';
		}

	}	// end function redirect


	/**
	 * Detect if the page is using HTTPS protocol and return bool or redirect
	 */

	public static function https( $redirect = true )
	{
		global $config;
		$https = ( ! empty( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] === 'on' );

		// if redirect is true and https is not on, redirect to https site
		if( $redirect && ! $https )
		{
			static::redirect( 'https://' . $_SERVER['HTTP_HOST']
				. $_SERVER['REQUEST_URI'] );
			die;
		}
		else
			return $https;
	}


	/**
	 * Loads a HTML fragment
	 * @param	string	$frag
	 * @param	boolean	$return
	 */

	public function frag( $frag )
	{
		global $config;
		$frag = $config->PATH_FRAG . "/{$frag}.phtml";

		// send data to frags and access it through $this->data
		if( file_exists( $frag ) )
			include( $frag );
		else
			throw new Exception( "Unable to load fragment $frag" );
	}


	/**
	 * Safely echo a value onto the page
	 * @param	string	$var
	 */

	public function write( &$var )
	{
		return ( ! empty( $var ) ? $var : '' );
	}

}	// end class page
