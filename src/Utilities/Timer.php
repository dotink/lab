<?php namespace Dotink\Lab\Utilities
{
	class Timer
	{
		private $endTime;
		private $startTime;


	    /**
	     * Starts the timer
	     *
	     * @return void
	     */
	    public function start()
	    {
	        $this->startTime = microtime(true);
	    }


	    /**
	     * Stops the timer
	     *
	     * @return void
	     */
	    public function stop()
	    {
	        $this->endTime = microtime(true);
	    }


	    /**
	     * Returns a human readable elapsed time
	     *
	     * @param  float $microtime
	     * @param  string  $format   The format to display (printf format)
	     * @return string
	     */
	    public function getTime()
	    {
			$time = $this->endTime - $this->startTime;

	        if ($microtime >= 1) {
	            $unit = 's';
	            $time = round(time, 3);
	        } else {
	            $unit = 'ms';
	            $time = round($time * 1000);
	        }

	        return sprintf('%.3f%s', $time, $unit);
	    }
	}
}
