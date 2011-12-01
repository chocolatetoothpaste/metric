<?php
/**
 * Timer class
 * @author Ross Paskett <rpaskett@gmail.com>
 */


class timer
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
		if ( !array_key_exists( $name, $this->count ) )
			$this->count[$name] = 0;
		else
			$this->count[$name] = end( array_keys( $this->start[$name] ) );

		$this->count[$name]++;
		$this->start[$name][$this->count[$name]] = microtime(true);
	}


	/**
	 * Stops a timer, or recursively stops a set
	 * @param	string	$name		a unique name for the time [optional, default 'timer']
	 * @param	boolean	$recursive	if true, stops all sub timers under the same name [optional, default false]
	 */

	public function stop( $name = 'timer', $recursive = false )
	{
		if( $recursive )
		{
			krsort( $this->start[$name] );
			foreach( $this->start[$name] as $k => $v )
			{
				/*if( !array_key_exists( $k, (array)$this->stop[$name] ) )
					$this->stop[$name][$k] = microtime(true);*/
				if( empty( $this->stop[$name][$k] ) )
					$this->stop[$name][$k] = microtime(true);
			}
		}
		else
		{
			$this->stop[$name][$this->count[$name]] = microtime(true);
			$this->count[$name]--;
		}
	}


	/**
	 * Stop all timers
	 */

	public function stopAll()
	{
		foreach( $this->start as $name => $void )
		{
			$this->stop( $name, true );
		}
	}


	/**
	 * Output a table of times
	 * @param	boolean	$strip	strip tags from output? [optional, default false]
	 * @param	string	$timers	limit to a specific timer name [optional, default '']
	 */

	public function show( $format = false, $timers = '', $strip = false )
	{
		$timers = ( $timers ? $this->start[$only] : $this->start );
		if( $strip )
		{
			echo "Name\t\tStart\t\tStop\t\tTime\n";
			foreach( $timers as $name => $times )
			{
				ksort($times);
				foreach( $times as $k => $v )
				{
					//if( array_key_exists( $k, $this->stop[$name] ) )
					if( !empty( $this->stop[$name][$k] ) )
					{
						$start =& $this->start[$name][$k];
						$stop =& $this->stop[$name][$k];
						$time = ( $stop - $start );
						echo "$name{$k}\t\t",
							sprintf( "%0.4f\t", $start ),
							sprintf( "%0.4f\t", $stop ),
							sprintf( "%0.4f\n", $time );
					}
				}
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
			foreach( $timers as $name => $times )
			{
				ksort($times);
				foreach( $times as $k => $v )
				{
					//if( array_key_exists( $k, $this->stop[$name] ) )
					if( !empty( $this->stop[$name][$k] ) )
					{
						$start =& $this->start[$name][$k];
						$stop =& $this->stop[$name][$k];
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
				}
			}
			?></table><?php
		}
	} // end function showTimes

} // end class timer

?>