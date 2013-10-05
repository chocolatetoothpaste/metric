<?php
namespace Metric\Util;

class Timer
{
	private $start = array();
	private $stop = array();
	private $count = array();


	/**
	 * Start a timer
	 * @param	string	$name	a unique name for the timer [optional, default 'timer']
	 */

	public function start( $name = 'timer' )
	{
		if( ! empty( $this->start[$name] ) )
			throw new Exception( "Timer '$name' has already been started" );

		$this->start[$name] = microtime( true );
	}


	/**
	 * Stops a timer, or recursively stops a set
	 * @param	string	$name		a unique name for the time [optional, default 'timer']
	 * @param	boolean	$recursive	if true, stops all sub timers under the same name [optional, default false]
	 */

	public function stop( $name = 'timer' )
	{
		$this->stop[$name] = microtime( true );
	}


	/**
	 * Stop all timers
	 */

	public function stopAll()
	{
		foreach( $this->start as $name => $void )
			$this->stop[$name] = microtime( true );
	}


	/**
	 * Output a table of times
	 * @param	boolean	$strip	strip tags from output? [optional, default false]
	 * @param	string	$timers	limit to a specific timer name [optional, default '']
	 */

	public function show( $timer = '', $strip = false )
	{
		$timer = ( ! empty( $timer ) ? $this->start[$timer] : $this->start );
		if( $strip )
		{
			echo "Name\t\tStart\t\tStop\t\tTime\n";
			foreach( $timer as $name => $void )
			{
				$start =& $this->start[$name];
				$stop =& $this->stop[$name];
				$time = ( $stop - $start );
				echo "$name\t\t",
					sprintf( "%0.4f\t", $start ),
					sprintf( "%0.4f\t", $stop ),
					sprintf( "%0.4f\n", $time );
			}
		}
		else
		{
		?>
		<table border="1" cellspacing="0" cellpadding="3">
			<tr>
				<td><strong>Name</strong></td>
				<td><strong>Start</strong></td>
				<td><strong>Stop</strong></td>
				<td><strong>Time</strong></td>
			</tr>
			<?php
			foreach( $timer as $name => $void )
			{
				$start =& $this->start[$name];
				$stop =& $this->stop[$name];
				$time = ( $stop - $start );
				echo '<tr><td>',
					$name,
					'</td><td align="right">',
					sprintf( '%0.4f', $start ),
					'</td><td align="right">',
					sprintf( '%0.4f', $stop ),
					'</td><td align="right">',
					sprintf( '%0.4f', $time ),
					'</td></tr>';
			}
			?></table><?php
		}
	} // end function showTimes

} // end class timer

?>