<?php

namespace Lab;

use Closure;
use Exception;
use Composer\Autoload\ClassLoader;

/**
 *
 */
class Application
{
	const REGEX_PARSE_ERROR = '#PHP Parse error\:\s+(.*) in (.*) on line (\d+)#i';

	/**
	 *
	 */
	protected $args = array();


	/**
	 *
	 */
	public function __construct($lab, $root, ClassLoader $loader, array $args = array())
	{
		$this->lab    = $lab;
		$this->root   = $root;
		$this->loader = $loader;
		$this->args   = $args;
		$this->timer  = new Utilities\Timer();

		$this->autoloader = function($class) {
			throw new Exception(sprintf(
				'Cannot autoload class %s, autoloading disabled, try $this->needs() or using a mock',
				$class
			));
		};
	}

	/**
	 * A simple assertion wrapper
	 *
	 * @param mixed $value The value to perform assertions on
	 * @param boolean $raw The option to treat the value as non-parseable, default FALSE
	 * @return Assertion An assertion object
	 */
	public function accept($value, $raw = FALSE)
	{
		return new Assertion($value, $raw);
	}


	/**
	 * A simple rejection wrapper
	 *
	 * @param mixed $value The value to perform rejection on
	 * @param boolean $raw The option to treat the value as non-parseable, default FALSE
	 * @return InvertedAssertion A rejection object
	 */
	public function reject($value, $raw = FALSE)
	{
		return new InvertedAssertion($value, $raw);
	}


	/**
	 *
	 */
	public function run()
	{
		if (!isset($this->args[1])) {
			$this->printBanner();
		}

		try {
			foreach ([$this->root . '/lab.config', realpath(__DIR__ . '/../lab.config'), NULL] as $config_file) {
				if (file_exists($config_file)) {
					$this->check($config_file);
					break;
				}
			}

			if ($config_file) {
				$config = include $config_file;
			} else {
				throw new Exception('unable to find a functional config');
			}

		} catch (Exception $e) {
			echo $this->print('Broken config: ', 'red') . $e->getMessage();
			echo PHP_EOL;
			return -1;
		}

		if (!isset($this->args[1])) {
			$result = $this->runTests($config['tests_directory'], $config);
		} else {
			$result = $this->runTest($this->args[1], $config);
		}

		echo PHP_EOL;

		return $result;
	}


	/**
	 *
	 */
	public function runTest($test_file, $config)
	{
		try {
			$this->check($test_file);

			$unit = include $test_file;
			$name = pathinfo($test_file, PATHINFO_FILENAME);

		} catch (Exception $e) {
			echo $this->print('Broken test: ', 'red') . $e->getMessage();
			echo PHP_EOL;
			return -1;
		}

		echo PHP_EOL;
		echo $this->print(sprintf('Running %s', $name), 'blue');
		echo PHP_EOL;

		if (!empty($config['disable_autoloading'])) {
			spl_autoload_register($this->autoloader, TRUE, TRUE);
		}

		$this->timer->start();

		try {
			if (isset($config['setup']) && $config['setup'] instanceof Closure) {
				call_user_func($config['setup']->bindTo($this, $this), NULL, NULL);
			}

			if (isset($unit['setup']) && $unit['setup'] instanceof Closure) {
				call_user_func($unit['setup']->bindTo($this, $this), NULL, NULL);
			}

		} catch (Exception $e) {
			echo $this->print('Setup Failed: ', 'red') . $e->getMessage();
			echo PHP_EOL;
			return -1;
		}

		if (isset($unit['tests']) && is_array($unit['tests'])) {
			foreach ($unit['tests'] as $message => $test) {
				if (!$test instanceof Closure) {
					continue;
				}

				try {
					$this->exception = NULL;
					call_user_func($test->bindTo($this, $this), NULL, NULL);
					echo "\t" . '[ ' . $this->print('    PASS    ', 'green') . ' ] - ' . $message . PHP_EOL;

				} catch (InvalidTestException $e) {
					$this->exception = $e;
					echo "\t" . '[ ' . $this->print('INVALID TEST', 'yellow') . ' ] - ' . $message . PHP_EOL;

				} catch (FailedTestException $e) {
					$this->exception = $e;
					echo "\t" . '[ ' . $this->print('    FAIL    ', 'red') . ' ] - ' . $message . PHP_EOL;

				} catch (Exception $e) {
					$this->exception = $e;
					echo "\t" . '[ ' . $this->print('    FAIL    ', 'red') . ' ] - ' . $message . PHP_EOL;
					echo "\t" . PHP_EOL;
					echo "\t" . 'Unexpected Exception [try `throws()`]:' . PHP_EOL;
				}

				if ($this->exception) {
					echo PHP_EOL;
					echo implode(PHP_EOL . "\n", explode(PHP_EOL, $e->getMessage())) . PHP_EOL;
					echo PHP_EOL;

					foreach ($this->makeDetails($e) as $type => $value) {
						echo "\t" . $this->print(str_pad($type . ':', 12, ' '), 'cyan') . $value . PHP_EOL;
					}

					break;
				}
			}
		}

		try {
			if (isset($config['cleanup']) && $config['cleanup'] instanceof Closure) {
				call_user_func($config['cleanup']->bindTo($this, $this), NULL, NULL);
			}

			if (isset($unit['cleanup']) && $unit['cleanup'] instanceof Closure) {
				call_user_func($unit['cleanup']->bindTo($this, $this), NULL, NULL);
			}

		} catch (Exception $e) {
			echo $this->print('Cleanup Failed: ', 'red') . $e->getMessage();
			echo PHP_EOL;
			return -1;
		}

		$this->timer->stop();

		if (!empty($config['disable_autoloading'])) {
			spl_autoload_unregister($this->autoloader);
		}

		$time = $this->timer->getTime();
		$mem  = memory_get_usage() / 1024 / 1024;

		echo PHP_EOL . "\t" . 'Time:   ' . $time;
		echo PHP_EOL . "\t" . 'Memory: ' . number_format($mem, 2) . 'mb';
		echo PHP_EOL;

		return $this->exception
			? -1
			: 0;
	}


	/**
	 *
	 */
	public function runTests($tests_directory, $config)
	{
		$this->timer->start();

		foreach ($this->scan($tests_directory) as $test_file) {
			$command = sprintf(
				'%s %s %s %s',
				PHP_BINARY, $this->lab, escapeshellarg($test_file), file_exists('/dev/null')
					? '2>/dev/null'
					: '2> nul'
			);

			passthru($command, $status);

			if ($status !== 0) {
				return -1;
			}
		}

		$this->timer->stop();

		echo PHP_EOL;
		echo $this->print('ALL TESTS PASSING', 'light_cyan');
		echo $this->print(' (' . $this->timer->getTime() . ')', 'white');
		echo PHP_EOL;

		return 0;
	}


	/**
	 * Check a file for syntax errors
	 */
	protected function check($file)
	{
		exec(sprintf('%s -l %s 2>&1', PHP_BINARY, escapeshellarg($file)), $output);

		if (preg_match_all(static::REGEX_PARSE_ERROR, $output[0], $matches)) {
			throw new Exception(
				$matches[1][0]                          .  // The syntax error
				$this->print(' @ ', 'green')            .  // @
				$this->print($matches[2][0], 'yellow')  .  // File
				'#'                                     .  // #
				$this->print($matches[3][0], 'yellow')     // Line number
			);
		}
	}


	/**
	 * Depend on a particular class
	 *
	 * @param string $class The class to depend on
	 * @return void
	 */
	protected function needs($class)
	{
		if (!$file = $this->loader->findFile($class)) {
			throw new Exception(sprintf(
				'Cannot include class "%s", no file provides this class or file is unreadable',
				$class
			));
		}

		$this->check($file);
		$this->loader->loadClass($class);
	}


	/**
	 *
	 */
	protected function print($text, $color)
	{
		$colors = [
			'black'        => '0;30',
			'dark_gray'    => '1;30',
			'blue'         => '0;34',
			'light_blue'   => '1;34',
			'green'        => '0;32',
			'light_green'  => '1;32',
			'cyan'         => '0;36',
			'light_cyan'   => '1;36',
			'red'          => '0;31',
			'light_red'    => '1;31',
			'purple'       => '0;35',
			'light_purple' => '1;35',
			'brown'        => '0;33',
			'yellow'       => '1;33',
			'light_gray'   => '0;37',
			'white'        => '1;37'
		];

		return sprintf("\033[%sm%s\033[0m", $colors[$color], $text);
	}


	/**
	 *
	 */
	private function printBanner()
	{
		$banner = (
			' _          _    ____    ___  ' . PHP_EOL .
			'| |    __ _| |__|___ \  / _ \ ' . PHP_EOL .
			'| |   / _` |  _ \ __) || | | |' . PHP_EOL .
			'| |__| (_| | |_) / __/ | |_| |' . PHP_EOL .
			'|_____\__,_|_.__/____$this->print(_)___/  By: Dotink'
		);

		echo PHP_EOL;
		echo $this->print($banner, 'dark_gray') . PHP_EOL;
		echo PHP_EOL;
		echo PHP_EOL;
	}


	/**
	 * Gets detail about an exception that was thrown.
	 *
	 * @param Exception $e The exception to get details on
	 * @return array A clean array of information about the exception
	 */
	private function makeDetails(Exception $e)
	{
		$trace  = $e->getTrace();
		$depth  = count($trace) - 5;

		return [
			'Context' => isset($trace[$depth]['class'])
				? $trace[$depth]['class'] . '::' . (
					$trace[$depth]['function'] == '__call'
						? $trace[$depth]['args'][0]
						: $trace[$depth]['function']
				)
				: $trace[$depth]['function'],

			'File' => isset($trace[$depth]['file'])
				? $trace[$depth]['file']
				: $e->getFile(),

			'Line' => isset($trace[$depth]['line'])
				? $trace[$depth]['line']
				: $e->getLine()
		];
	}


	/**
	 * Scan a directory for a number of PHP files.
	 *
	 * @access private
	 * @param string $directory The directory to scan
	 * @return array The list of file, empty if the directory was unreaable
	 */
	private function scan($directory)
	{
		$test_files = array();
		$directory  = !preg_match('#^(/|\\\\|[a-z]:(\\\\|/)|\\\\|//)#i', $directory)
			? realpath($this->root . DIRECTORY_SEPARATOR . $directory)
			: realpath($directory);

		if ($directory) {
			$test_files = array_merge($test_files, glob($directory . DIRECTORY_SEPARATOR . '*.php'));

			foreach (glob($directory . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR) as $sub_directory) {
				$test_files = array_merge($test_files, $this->scan($sub_directory));
			}
		}

		return $test_files;
	}
}
