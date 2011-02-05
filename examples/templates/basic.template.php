<?php
//$js = array_merge( array( '/adapter/ext/ext-base' ), $page->js );
$js = $page->js;
$css = array_merge( array( 'reset', 'base', 'main' ), $page->css );
$js = implode( ',', $js );
$css = implode( ',', $css );
$page->title = iif( DEV, 'DEV: ' ) . 'Framework'
	. iif( $page->title, " | {$page->title}" );
$params = iif( DEV, '&cache=no' )	. iif( $page->uid, "&uid={$page->uid}" );
$page->content_type = 'text/html; charset=utf-8';
//$params = iif( $page->uid, "&uid={$page->uid}" );

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
	<head>
		<?php /* if( $this->description ): ?>
			<meta name="description" content="<?php echo $this->description; ?>" />
		<?php endif;
			if( $this->keywords ): ?>
				<meta name="keywords" content="<?php echo $this->keywords; ?>" />
		<?php endif; */ ?>
		<title><?php echo $page->title; ?></title>
		<?php if( stripos( $js, 'ext' ) ): ?>
		<link
			rel="stylesheet"
			type="text/css"
			href="/js/resources/css/ext-all.css" />
		<?php endif; ?>

		<?php if( $css ): ?>
		<link
			rel="stylesheet"
			type="text/css"
			href="/resource.php?css=<?php echo $css, $params; ?>" />
		<?php endif; ?>

		<?php if( $js ): ?>
		<script	src="/resource.php?js=<?php echo $js, $params; ?>" type="text/javascript"></script>
		<?php endif; ?>

	</head>

	<body id="body">
		<div id="container">
			<?php if( keyAndValue( $_SESSION, 'user' ) instanceof User && $_SESSION['user']->authenticate() ): ?>
				<a href="/admin/index">Home</a><br />
				<a href="/admin/users">Users</a><br />
				<a href="/admin/accounts">Accounts</a><br />
				<a href="/logout">Log out</a><br />
			<?php
				endif;

			// this outputs the page after it has been compiled by the
			// page controller.  see page::render() for more
			echo $page->body;
			?>
			<br class="clear" />
		</div>
		<?php //$msg->showAll(); ?>
	</body>
</html>