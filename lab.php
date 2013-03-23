<?php namespace Dotink\Lab {

	use Exception;
	use InvalidArgumentException;
	use stdClass;

	//
	// Useful shorthand constants
	//

	const DS  = \DIRECTORY_SEPARATOR;
	const LB  = \PHP_EOL;
	const TAB = "\t";

	//
	// Constants for comparisons operations
	//

	const GT      = '>';
	const LT      = '<';
	const GTE     = '>=';
	const LTE     = '<=';
	const EXACTLY = '===';


	//
	// Constants for conditional operations
	//

	const EVEN    = 1;
	const ODD     = 0;

	//
	// Support Constants for built-in functionality
	//

	const REGEX_ABSOLUTE_PATH = '#^(/|\\\\|[a-z]:(\\\\|/)|\\\\|//)#i';
	const REGEX_PARSE_ERROR   = '#PHP Parse error\:  (.*) in (.*) on line (\d+)#i';

	//
	// We'll do the error printing, k thankz bye!
	//

	ini_set('display_errors', 0);
	ini_set('display_startup_errors', 0);

	//
	// Print our our label if we're the parent
	//

	if (!isset($argv[2])) {
		$banner = (
			' _        _    ____          _   ___  ' . LB .
			'| |      / \  | __ )  __   _/ | / _ \ ' . LB .
			'| |     / _ \ |  _ \  \ \ / / || | | |' . LB .
			'| |___ / ___ \| |_) |  \ V /| || |_| |' . LB .
			'|_____/_/   \_\____/    \_/ |_(_)___/  By: Dotink'
		);

		echo LB;
		echo _($banner, 'dark_gray') . LB;
		echo LB;
		echo LB;
	}

	/**
	 * An array of PHP errors collected during the running of the script.
	 *
	 * @var array
	 */
	$errors = array();

	/**
	 * An array of Lab warnings collected during the running of the script
	 *
	 * @var array
	 */
	$warnings = array();


	/**
	 * Error handler which collects all the errors for display in the event of a failure.  We
	 * don't bother showing these errors unless there is a failure.
	 */
	set_error_handler(function($number, $string, $file, $line, $context) use (&$errors)
	{
		$type = 'Unknown(' . $number . ')';

		switch ($number) {
			case E_WARNING: $type = 'E_WARNING'; break;
			case E_NOTICE:  $type = 'E_NOTICE';  break;
			case E_STRICT:  $type = 'E_STRICT';  break;
		}

		$errors[] = sprintf(
			'[%s](%s:%d): %s' . LB,
			$type, $file, $line, $string
		);
	});


	/**
	 * When the system shuts down we need print another line, but also look to see if we received
	 * a fatal error and print it cleanly.
	 */
	register_shutdown_function(function() {
		echo LB;

		if ($error = error_get_last()) {
			if ($error['type'] == E_ERROR || $error['type'] == E_PARSE) {
				echo LB;
				echo _('Fatal error:', 'red') . ' ' . $error['message'] . _(' @ ', 'green');
				echo _($error['file'] . '#' . $error['line'], 'yellow');
				echo LB;
			}
		}
	});


	/**
	 * Get a message printed in a particular color
	 *
	 * @param string $message The Message to print
	 * @param string $color The color to print it in
	 * @return string The colored message for CLI output
	 */
	function _($message, $color)
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

		return sprintf("\033[%sm%s\033[0m", $colors[$color], $message);
	}


	/**
	 * A simple assertion wrapper
	 *
	 * @param mixed $value The value to perform assertions on
	 * @param boolean $raw The option to treat the value as non-parseable, default FALSE
	 * @return Assertion An assertion object
	 */
	function assert($value, $raw = FALSE)
	{
		return new Assertion($value, $raw);
	}


	/**
	 * A simple rejection wrapper
	 *
	 * @param mixed $value The value to perform rejection on
	 * @param boolean $raw The option to treat the value as non-parseable, default FALSE
	 * @return Rejection A rejection object
	 */
	function reject($value, $raw = FALSE)
	{
		return new Rejection($value, $raw);
	}


	/**
	 * Gets the configuration for the system, also since it uses needs we can catch the syntax
	 * error and pretty print it special just for our config!
	 *
	 * @param string $file The file containing the config
	 * @return array The system configuration
	 */
	function get_config($file)
	{
		if ($file === NULL) {
			return [
				'tests_directory'     => 'tests',
				'disable_autoloading' => TRUE,
				'setup'               => function(){},
				'cleanup'             => function(){},
				'data'                => []
			];
		}

		try {
			return needs($file);
		} catch (Exception $e) {
			echo _('Config Failed: ', 'red') . $e->getMessage();
			echo LB;
			exit(-1);
		}
	}


	/**
	 * Gets detail about an exception that was thrown.
	 *
	 * @param Exception $e The exception to get details on
	 * @return array A clean array of information about the exception
	 */
	function get_detail(Exception $e)
	{
		$trace  = $e->getTrace();
		$depth  = get_class($e) == 'InvalidArgumentException'
			? 5
			: 0;

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
	 * Depend on a file being included
	 *
	 * @param string $file The file to depend on
	 * @return mixed The return result from including the file
	 */
	function needs($file)
	{

		if (!is_readable($file)) {
			throw new Exception(sprintf(
				'Cannot include %s, file is not readable',
				$file
			));
		}

		exec(sprintf('%s -l %s 2>&1', PHP_BINARY, escapeshellarg($file)), $output);

		if (preg_match_all(REGEX_PARSE_ERROR, $output[0], $matches)) {
			throw new Exception(
				$matches[1][0]               .  // The syntax error
				_(' @ ', 'green')            .  // @
				_($matches[2][0], 'yellow')  .  // File
				'#'                          .  // #
				_($matches[3][0], 'yellow')     // Line number
			);
		}

		return include $file;
	}


	/**
	 * Adds tests from a directory
	 *
	 * @param string $directory
	 */
	function add_tests($directory)
	{
		$test_files = array();
		$test_files = array_merge($test_files, glob($directory . DS . '*.php'));

		foreach (glob($directory . DS . '*', GLOB_ONLYDIR) as $sub_directory) {
			$test_files = array_merge($test_files, add_tests($sub_directory));
		}

		return $test_files;
	}


	/**
	 * Execute Fixtures and Tests.  If no argument was passed to lab, then we will scan the
	 * configured testDirectory.  Otherwise will will execute the single fixture available in the
	 * file path passed as the first argument.
	 *
	 * @return void
	 */
	call_user_func(function() use($argv, &$errors, &$warnings)
	{
		//
		// Include supporting files
		//

		try {
			if (is_dir(__DIR__ . '/parody/src/')) {

				foreach (['Quip', 'Mime'] as $class) {
					include __DIR__ . '/parody/src/' . $class . '.php';

					if (!class_exists('Dotink\\Parody\\' . $class)) {
						throw new Exception(sprintf(
							'Parody appears to be installed, but we cannot find %s',
							$class
						));
					}
				}
			}

			needs(__DIR__ . '/src/Assertion.php');
			needs(__DIR__ . '/src/Rejection.php');

		} catch (Exception $e) {
			echo _('Broken install: ', 'red') . $e->getMessage();
			echo LB;
			exit(-1);
		}

		//
		// Test loading
		//

		$directory = isset($argv[1]) && is_dir($argv[1])
			? rtrim($argv[1], '\\/' . DS)
			: getcwd();

		for (
			$config_path  = realpath($directory);

			$config_path != realpath($config_path . DS . '..')
			&& !is_readable($config_path . DS . 'lab.config');

			$config_path  = realpath($config_path . DS . '..')
		);

		if ($config_path == realpath($config_path . DS . '..')) {
			$config    = get_config(NULL);
			$directory = getcwd();
		} else {
			$config    = get_config($config_path . DS . 'lab.config');
			$directory = $config_path;
		}

		$tests_directory = !preg_match(REGEX_ABSOLUTE_PATH, $config['tests_directory'])
			? realpath($directory . DS . $config['tests_directory'])
			: realpath($config['tests_directory']);

		if (!empty($config['disable_autoloading'])) {
			spl_autoload_register(function($class) {
				throw new Exception(sprintf(
					'Cannot autoload class %s, autoloading disabled, try needs() or using a mock',
					$class
				));
			});
		}

		if (!isset($argv[2])) {
			if ($tests_directory) {
				foreach (add_tests($tests_directory) as $test_file) {
					$command = sprintf(
						'%s -d display_errors=Off %s %s %s %s',
						PHP_BINARY, __FILE__,
						$directory, escapeshellarg($test_file), file_exists('/dev/null')
							? '2>/dev/null'
							: '2> nul'
					);

					passthru($command, $status);

					if ($status !== 0) {
						exit(-1);
					}

				}
			}

			echo _('ALL TESTS PASSING', 'light_cyan') . LB;

			exit(0);
		}

		$data      = $config['data'];
		$shared    = new stdClass();
		$file_path = str_replace($tests_directory . DS, '', $argv[2]);

		try {
			$test_file = needs($argv[2]);
		} catch (Exception $e) {
			echo $e->getMessage();
			exit(-1);
		}

		echo _(sprintf('Running %s', str_replace('.php', '', $file_path)), 'blue') . LB;

		//
		// Setup
		//

		try {
			if (isset($config['setup']) && $config['setup'] instanceof \Closure) {
				call_user_func($config['setup'], $config['data'], $shared);
			}

			if (isset($test_file['setup']) && $test_file['setup'] instanceof \Closure) {
				call_user_func($test_file['setup'], $config['data'], $shared);
			}

		} catch (Exception $e) {
			echo _('Setup Failed: ', 'red');
			echo $e->getMessage();
			exit(-1);
		}

		//
		// Run Tests
		//

		if (isset($test_file['tests']) && is_array($test_file['tests'])) {
			foreach ($test_file['tests'] as $message => $test) {
				if ($test instanceof \Closure) {
					echo TAB . '- ' . $message . ' ';

					try {
						call_user_func($test, $data, $shared);
						echo '[' . _('PASS', 'green') . ']' . LB;

					} catch (Exception $e) {
						if (get_class($e) == 'InvalidArgumentException') {
							echo '[' . _('INVALID TEST', 'yellow') . ']' . LB;
						} else {
							echo '[' . _('FAIL', 'red') . ']' . LB;
						}

						echo TAB . LB;
						echo TAB . $e->getMessage() . LB;
						echo TAB . LB;

						foreach (get_detail($e) as $type => $value) {
							echo TAB . _(str_pad('  ' . $type . ':', 10, ' '), 'cyan') . $value . LB;
						}

						echo LB;

						if (get_class($e) == 'Exception') {
							echo 'PHP Errors (' . count($errors) . ')' . LB;

							foreach ($errors as $error) {
								echo LB . $error;
							}

							exit(-1);
						}
					}
				}
			}
		}

		//
		// Cleanup
		//

		try {
			if (isset($test_file['cleanup']) && $test_file['cleanup'] instanceof \Closure) {
				call_user_func($test_file['cleanup'], $config['data'], $shared);
			}

			if (isset($config['cleanup']) && $config['cleanup'] instanceof \Closure) {
				call_user_func($config['cleanup'], $config['data'], $shared);
			}

		} catch (Exception $e) {
			echo _('Cleanup Failed: ', 'red');
			echo $e->getMessage();
			exit(-1);
		}

		exit(0);
	});
}
