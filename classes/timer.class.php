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
		$this->start[$name][$this->count[$name]] = $this->microtime();
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
				if( !array_key_exists( $k, (array)$this->stop[$name] ) )
					$this->stop[$name][$k] = $this->microtime();
			}
		}
		else
		{
			$this->stop[$name][$this->count[$name]] = $this->microtime();
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

	public function showTimes( $format = false, $timers = '' )
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
					if( array_key_exists( $k, $this->stop[$name] ) )
					{
						$start =& $this->start[$name][$k];
						$stop =& $this->stop[$name][$k];
						$time = ( $stop - $start );
						echo "$name{$k}\t\t", sprintf( "%0.3f\t", $start ), sprintf( "%0.3f\t", $stop ), sprintf( "%0.3f\n", $time );
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
					if( array_key_exists( $k, $this->stop[$name] ) )
					{
						$start =& $this->start[$name][$k];
						$stop =& $this->stop[$name][$k];
						$time = ( $stop - $start );
					?>
						<tr>
							<td><?php echo $name;?></td>
							<td align="right"><?php echo sprintf( '%0.3f', $start );?></td>
							<td align="right"><?php echo sprintf( '%0.3f', $stop );?></td>
							<td align="right"><?php echo sprintf( '%0.3f', $time );?></td>
						</tr>
					<?php
					}
				}
			}
			?></table><?php
		}
	} // end function showTimes


	/**
	 * Returns millisecond time
	 */

	private function microtime()
	{
		$exp = explode(' ', microtime());
		return ( (float)$exp[1] + (float)$exp[0] );
	}

} // end class timer

?>