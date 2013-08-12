<?php
class pmail
{

	/**
	 * HTML version of the message
	 * @var	string
	 */
	public $html = '';

	/**
	 * Text version of the message
	 * @var	string
	 */
	public $text = '';

	/**
	 * array of message headers
	 * @var	array
	 */
	public $headers = array();

	/**
	 * attachments stringified
	 * @var array
	 */
	private $attachment;

	/**
	 * email boundary
	 * @var string
	 */
	private $boundary;

	/**
	 * email alt boundary
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
	private $content_type;


	/**
	 * An excellent class for creating multi-part MIME emails
	 */

	public function __construct()
	{
		$this->boundary = '<boundary>' . sha1( 'PMAIL_BOUNDARY' . time() ) . '</boundary>';
		$this->alt_boundary = '<boundary>' . sha1( 'PMAIL_ALT_BOUNDARY' . time() ) . '</boundary>';

	}	//	end constructor pmail


	/**
	 * builds the html part of the message
	 * @return string $html_msg;
	 */

	private function buildHTMLPart()
	{
		$header = '';

		if( $this->text && $this->attachment ):
			$header = "--{$this->alt_boundary}\r\n";
			$this->html .= "\r\n--{$this->alt_boundary}--\r\n";
		elseif( $this->text || $this->attachment ):
			$header = "--$this->boundary\r\n";
		else:
			$this->content_type = 'text/html';
			return $this->html . "\r\n";
		endif;

		$this->html = $header
			. 'Content-Type: text/html; charset="utf-8"' . "\r\n"
			. 'Content-Transfer-Encoding: quoted-printable' . "\r\n"
			. 'Content-Disposition: inline' . "\r\n\r\n" . $this->html;

		return $this->html;
	}	//	end function buildHTMLPart


	/**
	 * builds the text part of the message
	 * @return string
	 */

	private function buildTextPart()
	{
		$header = '';

		if( $this->html && $this->attachment ):
				$header = 'Content-Type: multipart/alternative;' . "\r\n"
					. "	boundary=\"{$this->alt_boundary}\"\r\n\r\n"
					. "--{$this->alt_boundary}\r\n";
		elseif( $this->html || $this->attachment ):
			$header = "";
		else:
			$this->content_type = 'text/plain';
			return $this->text . "\r\n";
		endif;

		$this->text = $header
			. "Content-Type: text/html; charset=\"utf-8\"\r\n"
			. "Content-Transfer-Encoding: quoted-printable\r\n"
			. "Content-Disposition: inline;\r\n\r\n{$this->text}\r\n\r\n";

		return $this->text;
	}


	/**
	 * puts the entire message together
	 */

	private function buildMessage()
	{

		if( $this->text )
			$this->message .= $this->buildTextPart();

		if( $this->html )
			$this->message .= $this->buildHTMLPart();

		if( $this->attachment )
		{
			$this->content_type = 'multipart/mixed';
			$this->message .= $this->attachment;
		}
		elseif( $this->html && $this->text )
		{
			$this->content_type = 'multipart/alternative';
		}

		return $this->message;
	}


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

				$this->attachment .= "--{$this->boundary}\r\n";
				$this->attachment .= "Content-Type: {$mime};"
					. "	name=\"{$base}\"\r\n";
				$this->attachment .= "Content-Transfer-Encoding: base64\r\n";
				$this->attachment .= 'Content-Disposition: attachment;'
					. "	filename=\"{$base}\"\r\n\r\n";
				$this->attachment .= "{$file_encoded}";
				unset( $file_encoded, $base, $mime );
			}
		}
	}	//	end method attach


	/**
	 * Assembles and sends the message
	 */

	public function send()
	{
		if( empty( $this->from ) )
		{
			ini_set('sendmail_from', $this->from );
		}

		$this->buildMessage();

		$this->headers = array_merge(
			array(
				'Message-Id'	=> "<{$this->to}:" . time() . '>',
				'Date'			=>	 gmdate( DATE_RFC2822 ),
				'From'			=>	$this->from,
				'X-Mailer'		=>	'ExpoMail 2.0',
				'X-Priority'	=>	'3',
				'MIME-Version'	=>	'1.0',
				'Content-Type'	=>	$this->content_type
					. "\r\n\tboundary=\"{$this->boundary}\""
		), $this->headers );

		$headers = '';

		foreach( $this->headers as $header => $value )
		{
			$headers .= "{$header}: {$value}\r\n";
		}

		$headers .= "This is a multi-part message in MIME format.\r\n\r\n";
		$headers .= "--$this->boundary\r\n";

		$this->message .= "\r\n\r\n--{$this->boundary}--";

		return mail( $this->to, $this->subject, $this->message, $headers );
	}	//	end method send


	/**
	 * Verifies the email address is formatted correctly (i.e., not bogus)
	 * @param string $address
	 * @return boolean
	 */

	private function verifyEmail( $address )
	{
		//	checks to see if email address matches acceptable pattern
		return preg_match( '#^[\w-]+(\.[\w-]+)*@([a-z0-9-]+(\.[a-z0-9-]+)*?\.[a-z]{2,6}|(\d{1,3}\.){3}\d{1,3})(:\d{4})?$#', $address );
	}

}	//	end class PMail
?>
