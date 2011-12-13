<?php

/**
* @author ross paskett - rpaskett@gmail.com
* @copyright 2007, 2009 ross paskett
* @package PMail v1.0
* revised:	2009-09-21
*
*/


class pmail
{

	/**
	 * HTML version of the message
	 * @var	string
	 */
	public $html_msg = '';

	/**
	 * Text version of the message
	 * @var	string
	 */
	public $text_msg = '';

	/**
	 * array of message headers
	 * @var	array
	 */
	private $headers;

	/**
	 * the files stringified
	 * @var array
	 */
	private $attachments;

	/**
	 * initial email boundary
	 * @var string
	 */
	private $__boundary;

	/**
	 * prepended email boundary
	 * @var string
	 */
	private $boundary;

	/**
	 * initial email alt boundary
	 * @var string
	 */
	private $__alt_boundary;

	/**
	 * prepended email alt boundary
	 * @var string
	 */
	private $alt_boundary;

	/**
	 * address(es) to send message to
	 * @var array
	 */
	public $to;

	/**
	 * message sender
	 * @var string
	 */
	public $from;

	/**
	 * message subject
	 * @var string
	 */
	public $subject;

	/**
	 * the message, compiled
	 * @var string
	 */
	public $message;

	/**
	 * content type of the main message
	 * @var string
	 */
	public $content_type;



	/**
	 * An excellent class for creating multi-part MIME emails
	 */

	public function __construct()
	{
		$this->__boundary = '<boundary>' . md5( 'PMAIL_BOUNDARY' . time() ) . '</boundary>';
		$this->__alt_boundary = '<boundary>' . md5( 'PMAIL_ALT_BOUNDARY' . time() ) . '</boundary>';

		$this->boundary = '--' . $this->__boundary;
		$this->alt_boundary = '--' . $this->__alt_boundary;
	}	//	end constructor pmail


	/**
	 * builds the html part of the message
	 * @return string $html_msg;
	 */

	private function buildHTMLPart()
	{
		$header = '';

		if( $this->text_msg && $this->attachments ):
				$header = "{$this->alt_boundary}\r\n";
				$this->html_msg .= "\r\n{$this->alt_boundary}--\r\n";
		elseif( $this->text_msg || $this->attachments ):
			$header = "$this->boundary\r\n";
		else:
			$this->content_type = 'text/html';
			return $this->html_msg . "\r\n";
		endif;

		$this->html_msg = $header
			. 'Content-Type: text/html; charset="utf-8"' . "\r\n"
			. 'Content-Transfer-Encoding: quoted-printable' . "\r\n"
			. 'Content-Disposition: inline' . "\r\n\r\n" . $this->html_msg;

		return $this->html_msg;
	}	//	end function buildHTMLPart


	/**
	 * builds the text part of the message
	 * @return string
	 */

	private function buildTextPart()
	{
		$header = '';

		if( $this->html_msg && $this->attachments ):
				$header = "{$this->boundary}\r\n"
					. 'Content-Type: multipart/alternative;' . "\r\n"
					. "	boundary=\"{$this->__alt_boundary}\"\r\n\r\n"
					. "{$this->alt_boundary}\r\n";
		elseif( $this->html_msg || $this->attachments ):
			$header = "{$this->boundary}\r\n";
		else:
			$this->content_type = 'text/plain';
			return $this->text_msg . "\r\n";
		endif;

		$this->text_msg = $header
			. 'Content-Type: text/html; charset="utf-8"' . "\r\n"
			. 'Content-Transfer-Encoding: quoted-printable' . "\r\n"
			. 'Content-Disposition: inline' . "\r\n\r\n" . $this->text_msg . "\r\n";

		return $this->text_msg;
	}


	/**
	 * puts the entire message together
	 */

	private function buildMessage()
	{

		if( $this->text_msg )
			$this->message .= $this->buildTextPart();

		if( $this->html_msg )
			$this->message .= $this->buildHTMLPart();

		if( $this->attachments )
		{
			$this->content_type = 'multipart/mixed';
			$this->message .= $this->attachments
				. "{$this->boundary}--\r\n";
		}
		elseif( $this->html_msg && $this->text_msg )
		{
			$this->content_type = 'multipart/alternative';
		}
		
		$headers = array
		(
			'Message-Id'		=>	"<{$this->to}:" . time() . '>',
			'Date'					=>	gmdate('r'),
			'X-Mailer'			=>	'PMail v1',
			'X-Priority'		=>	'3',
			'MIME-Version'	=>	'1.0',
			'Content-Type'	=>	$this->content_type . ";\r\n\tboundary=\"{$this->__boundary}\"\r\n"
		);

		foreach( $headers as $k => $v )
			$this->headers .= "$k: $v\r\n";

		$this->message = $this->headers . $this->message;

		echo "<pre>$this->message</pre>";
	}


	/**
	 * Sets up the headers for the message
	 */

	private function __set_message_headers()
	{
		$content_type = 'multipart/'
			. ( $this->attachments
				? 'mixed'
				: 'alternative' );

		//	setting headers; tasty!
		$this->headers .= "Message-Id: <{$this->to}:" . time() . ">\r\n";
		//	$this->headers .= "To: " . $this->to . "\r\n";
		$this->headers .= 'Date: ' . gmdate('r') . "\r\n";

		if( !empty( $this->from ) )
		{
			ini_set('sendmail_from', $this->from );
			$this->headers .= "From: {$this->from}\r\n";
		}

		if( $this->cc )
			$this->headers .= "Cc: {$this->cc}\r\n";

		if( $this->bcc )
			$this->headers .= "Bcc: {$this->bcc}\r\n";

		//	$this->headers .= "Subject: " . $this->subject . "\r\n";
		if( !empty( $this->in_reply_to ) )
			$this->headers .= "In-Reply-To: {$this->in_reply_to}\r\n";

		if ( !empty( $this->return_address ) )
		{
			$this->headers .= "Return-Path: {$this->return_address}\r\n";
			$this->headers .= "Return-Receipt-To: {$this->return_address}\r\n";
		}

		$this->headers .= 'X-Mailer: PMail v1' . "\r\n";
		$this->headers .= 'X-Priority: 3' . "\r\n";
		$this->headers .= 'MIME-Version: 1.0' . "\r\n";
		$this->headers .= 'Content-Type: ' . "{$content_type};\r\n";
		$this->headers .= "	boundary=\"{$this->__boundary}\"\r\n\r\n";
		//	$this->headers .= "This is a multi-part message in MIME format.\r\n";

		return true;

	}	//	end method __set_message_headers


	/**
	 * Puts the message together with the correct headers/boundaries
	 * @return boolean
	 */

	private function __set_message_body()
	{
		if( empty( $this->alt_body ) )
			$this->alt_msg = strip_tags( br2nl( $this->msg ) );

		$this->alt_msg = wordwrap( $this->alt_msg, 70 );

		$this->msg = "{$this->boundary}\r\n";
		$this->msg .= 'Content-Type: multipart/alternative;' . "\r\n";
		$this->msg .= "	boundary=\"{$this->__alt_boundary}\"\r\n\r\n";

		$this->msg .= "{$this->alt_boundary}\r\n";
		$this->msg .= 'Content-Type: text/plain; charset="utf-8"' . "\r\n";
		$this->msg .= 'Content-Transfer-Encoding: quoted-printable' . "\r\n";
		$this->msg .= 'Content-Disposition: inline' . "\r\n\r\n";
		$this->msg .= "{$this->alt_msg}\r\n\r\n";

		if( $this->html ):
			$this->msg .= "{$this->alt_boundary}\r\n";
			$this->msg .= 'Content-Type: text/html; charset="utf-8"' . "\r\n";
			$this->msg .= 'Content-Transfer-Encoding: quoted-printable' . "\r\n";
			$this->msg .= 'Content-Disposition: inline' . "\r\n\r\n";
			$this->msg .= "{$this->message}\r\n\r\n\r\n";
		endif;

		$this->msg .= "{$this->alt_boundary}--\r\n";

		return true;

	}	//	end method __set_message_body


	/**
	 * Encode and attach files to an email, add appropriate headers
	 * @param mixed $attachments - a file/array of files to attach
	 */

	public function attach( $attachments = array() )
	{
		foreach( $attachments as $file )
	  {
  		if( $f = file_get_contents( $file ) )
  		{
				//	encode the file and create the proper headers
  			$file_encoded = chunk_split( base64_encode( $f ) );
				$mime = mime_content_type( $file );
				$base = basename($file);

  			$this->attachments .= "{$this->boundary}\r\n";
  			$this->attachments .= "Content-Type: {$mime};"
					. " name=\"{$base}\"\r\n";
  			$this->attachments .= 'Content-Transfer-Encoding: base64' . "\r\n";
  			$this->attachments .= 'Content-Disposition: attachment;'
					. " filename=\"{$base}\"\r\n\r\n";
  			$this->attachments .= "{$file_encoded}\r\n\r\n";

    		unset( $file_encoded, $base, $mime );
  	  }
    }
	}	//	end method attach


	/**
	 * Assembles and sends the message
	 */

	public function send()
	{
		$this->buildMessage();

		/*
		$emails = array();

		if( !is_array( $this->to ) )
		{
			$this->to = (array)$this->to;
		}

	  $this->to = implode( ', ', $this->to );

		if( $this->__set_message_headers() && $this->__set_message_body() )
		{
			if( $this->attachments )
			{
				$this->msg .= $this->attachments;
			}

			$this->msg .= "{$this->boundary}--\r\n";
			$return = mail( $this->to, $this->subject, $this->msg, $this->headers );
			ini_restore( 'sendmail_from' );
			return $return;
		}*/
	}	//	end method send


	/**
	 * Verifies the email address is formatted correctly (i.e., not bogus)
	 * @param string $address
	 * @return boolean
	 */

	private function __verify_email( $address )
	{
		//	checks to see if email address matches acceptable pattern
		return preg_match( '#^[\w-]+(\.[\w-]+)*@([a-z0-9-]+(\.[a-z0-9-]+)*?\.[a-z]{2,6}|(\d{1,3}\.){3}\d{1,3})(:\d{4})?$#', $address );
	}	//	end method __check_email_syntax


}	//	end class PMail

?>
