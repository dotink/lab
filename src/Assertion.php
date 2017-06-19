<?php namespace Lab
{
	use Exception;
	use Closure;

	/**
	 * A simple assertion library
	 *
	 * @copyright Copyright (c) 2013, Matthew J. Sahagian
	 * @author Matthew J. Sahagian [mjs] <msahagian@dotink.org>
	 *
	 * @license Please reference the LICENSE.md file at the root of this distribution
	 *
	 * @package Lab
	 */
	class Assertion
	{
		const REGEX_PHP_METHOD = '/
			[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]* # Class
			\:\:                                     # Separator
			[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]* # Method
		/x';


		const REGEX_PHP_PROPERTY = '/
			[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*   # Class
			\:\:                                       # Separator
			\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]* # Property
		/x';


		/**
		 * Arguments held for callable assertions
		 *
		 * @access private
		 * @var array
		 */
		private $args = array();


		/**
		 * The class for the currently asserted method, property, or object
		 *
		 * @access private
		 * @var string
		 */
		private $class = NULL;


		/**
		 * The most recent exception thrown - can be analyzed with `analyzeException()`
		 *
		 * @access private
		 * @var Exception
		 */
		private $exception = NULL;


		/**
		 * Whether not the assertion is a Class
		 *
		 * @access private
		 * @var boolean
		 */
		private $isClass = FALSE;


		/**
		 * Whether not the assertion is a boolean
		 *
		 * @access private
		 * @var boolean
		 */
		private $isBoolean = FALSE;


		/**
		 * Whether not the assertion is a closure
		 *
		 * @access private
		 * @var boolean
		 */
		private $isClosure = FALSE;


		/**
		 * Whether not the assertion is a function
		 *
		 * @access private
		 * @var boolean
		 */
		private $isFunction = FALSE;


		/**
		 * Whether not the assertion is a method
		 *
		 * @access private
		 * @var boolean
		 */
		private $isMethod = FALSE;


		/**
		 * Whether not the assertion is numeric (float or integer)
		 *
		 * @access private
		 * @var boolean
		 */
		private $isNumber = FALSE;


		/**
		 * Whether not the assertion is an object
		 *
		 * @access private
		 * @var boolean
		 */
		private $isObject = FALSE;


		/**
		 * Whether not the assertion is a property
		 *
		 * @access private
		 * @var boolean
		 */
		private $isProperty = FALSE;


		/**
		 * Whether not the assertion is a string
		 *
		 * @access private
		 * @var boolean
		 */
		private $isString = FALSE;


		/**
		 * The method for a class or object we're asserting on
		 *
		 * @access private
		 * @var string
		 */
		private $method = NULL;


		/**
		 * Whether or not the assertion needs an object (such as for object methods/properties)
		 *
		 * @access private
		 * @var boolean
		 */
		private $needsObject = FALSE;


		/**
		 * The object we're asserting on
		 *
		 * @access private
		 * @var object
		 */
		private $object = NULL;


		/**
		 * The property for a class or object we're asserting on
		 *
		 * @access private
		 * @var string
		 */
		private $property = NULL;


		/**
		 * The success message if the assertion was successful
		 *
		 * @access private
		 * @var string
		 */
		private $success = FALSE;


		/**
		 * The PHP determined type of the value
		 *
		 * @access private
		 * @var mixed
		 */
		private $type = NULL;


		/**
		 * The original value of the assertion
		 *
		 * @access private
		 * @var mixed
		 */
		private $value = NULL;


		/**
		 * An abstracted comparison function which assumed values are already reduced
		 *
		 * @param mixed $subject The subject for comparison
		 * @param mixed $type The type of comparison (should use constants)
		 * @param mixed $comparison The comparison value
		 */
		static private function compareReduced($subject, $type, $comparison)
		{
			switch ($type) {
				case GT:      return $subject >   $comparison;
				case LT:      return $subject <   $comparison;
				case GTE:     return $subject >=  $comparison;
				case LTE:     return $subject <=  $comparison;
				case EXACTLY: return $subject === $comparison;

				case IN:      return in_array($subject, $comparison);

				default:      return $subject ==  $comparison;
			}
		}


		/**
		 * Create a new assertion, this will determine much about the nature of our value
		 *
		 * @access public
		 * @param mixed $value The value to assert
		 * @param boolean $raw Whether we should disable special interpretation, default FALSE
		 * @return void
		 */
		public function __construct($value, $raw = FALSE)
		{
			$this->value = $value;
			$this->type  = gettype($value);

			switch ($this->type) {
				case 'string':
					$this->loadString($raw);
					break;

				case 'object':
					$this->loadObject();
					break;

				case 'array':
					$this->loadArray();
					break;

				case 'integer':
				case 'float':
					$this->loadNumber();
					break;

				case 'boolean':
					$this->loadBoolean();
					break;

				case 'NULL':
					break;

				default:
					throw new InvalidTestException(sprintf(
						'Cannot provide assertion or rejection for type %s, not supported',
						$this->type
					));
			}
		}


		/**
		 * Gets the last success message posted
		 *
		 * @access public
		 * @return string The last success message posted, FALSE if nothing has succeeded
		 */
		public function alertSuccess()
		{
			return $this->success;
		}


		/**
		 * Allows user to analyze an exception thrown in a test using a callback
		 *
		 * @access public
		 * @param callable $callback The callback to analyze the exception (passed as first arg)
		 * @return mixed The return value of the callback
		 */
		public function analyzeException(callable $callback)
		{
			return $callback($this->exception);
		}


		/**
		 * Asserts that one or more values is contained in the result
		 *
		 * @access public
		 * @param mixed $value A value to check for in the result
		 * @param ...
		 * @return Assertion The original assertion for method chaining
		 */
		public function contains($value, $case_sensitive = TRUE)
		{
			$result  = $this->resolve();
			$values  = func_get_args();
			$missing = array();

			if (is_array($result) || (is_object($result) && $result instanceof \ArrayAccess)) {
				foreach ($values as $value) {
					if (array_search($value, $result) === FALSE) {
						$missing[] = $value;
					}
				}

				if (count($missing)) {
					throw new FailedTestException(sprintf(
						'Assertion Failed: %d of the values could not be found in result %s',
						count($missing),
						$this->formatValue($result)
					));
				}

				$this->success = sprintf(
					'All of the values could be found in result %s',
					$this->formatValue($result)
				);

				return $this;

			} elseif (is_string($result)) {
				$contains_value = !$case_sensitive
					? stripos($result, $value)
					: strpos($result, $value);

				if ($contains_value === FALSE) {
					throw new FailedTestException(sprintf(
						'Assertion Failed: %s does not contain %s',
						$this->formatValue($result),
						$this->formatValue($value)
					));
				}

				$this->success = sprintf(
					'%s contains %s',
					$this->formatValue($result),
					$this->formatValue($value)
				);

				return $this;
			}

			throw new InvalidTestException(sprintf(
				'Cannot use %s() on assertion or rejection of type "%s"',
				__FUNCTION__,
				gettype($result)
			));
		}


		/**
		 * Asserts that the result begins with a certain value
		 *
		 * @access public
		 * @param mixed $beginning A value equal to the beginning
		 * @return Assertion The original assertion for method chaining
		 */
		public function begins($beginning)
		{
			$result = $this->resolve();

			if (is_string($result)) {
				$encoding           = mb_detect_encoding($result . 'e', 'UTF-8, ISO-8859-1');
				$beginning_encoding = mb_detect_encoding($result . 'e', 'UTF-8, ISO-8859-1');

				if ($encoding != $beginning_encoding) {
					$encoding = $beginning_encoding;
				}

				$size         = mb_strlen($result, $encoding);
				$length       = mb_strlen($beginning, $encoding);
				$alert_values = [
					$this->formatValue($result),
					$this->formatValue($beginning)
				];

				if ($size >= $length) {


					if ($beginning != mb_substr($result, 0, $length)) {
						throw new FailedTestException(vsprintf(
							'Assertion Failed: Result %s does not begin with %s',
							$alert_values
						));
					}

					$this->success = vsprintf(
						'Result %s begins with %s',
						$alert_values
					);

					return $this;
				}

				throw new FailedTestException(sprintf(
					'Assertion Failed: Result %s is not long enough to begin with %s',
					$alert_values
				));
			}

			throw new InvalidTestException(sprintf(
				'Cannot use %s() on assertion or rejection of type "%s"',
				__FUNCTION__,
				gettype($result)
			));
		}


		/**
		 * Asserts that the result ends with a certain value
		 *
		 * @access public
		 * @param mixed $end A value equal to the ending
		 * @return Assertion The original assertion for method chaining
		 */
		public function ends($end)
		{
			$result = $this->resolve();

			if (is_string($result)) {
				$encoding     = mb_detect_encoding($result . 'e', 'UTF-8, ISO-8859-1');
				$end_encoding = mb_detect_encoding($result . 'e', 'UTF-8, ISO-8859-1');

				if ($encoding != $end_encoding) {
					$encoding = $end_encoding;
				}

				$length       = mb_strlen($result, $encoding);
				$start        = mb_strlen($end, $encoding);
				$alert_values = [
					$this->formatValue($result),
					$this->formatValue($end)
				];

				if ($length >= $start) {
					if ($end != mb_substr($result, $length - $start)) {
						throw new FailedTestException(vsprintf(
							'Assertion Failed: Result %s does not end with %s',
							$alert_values
						));
					}

					$this->success = vsprintf(
						'Result %s ends with %s',
						$alert_values
					);

					return $this;
				}

				throw new FailedTestException(vsprintf(
					'Assertion Failed: Result %s is not long enough to end with %s',
					$alert_values
				));
			}

			throw new InvalidTestException(sprintf(
				'Cannot use %s() on assertion or rejection of type "%s"',
				__FUNCTION__,
				gettype($result)
			));
		}


		/**
		 * Asserts that the result is equal to a value
		 *
		 * @access public
		 * @param mixed $value The value to check for equality
		 * @param boolean|string $exactly Whether or not the comparision should be exact
		 * @return Assertion The original assertion for method chaining
		 */
		public function equals($value, $exactly = FALSE)
		{
			$result    = $this->resolve();
			$condition = $exactly
				? ($result === $value)
				: ($result == $value);

			if ($condition) {
				$this->success = sprintf(
					'Expected %s%s and got %s',
					$this->formatValue($value),
					$exactly ? ' (exactly)' : NULL,
					$this->formatValue($result)
				);

				return $this;
			}

			throw new FailedTestException(sprintf(
				'Assertion Failed: Expected %s%s but got %s',
				$this->formatValue($value),
				$exactly ? ' (exactly)' : NULL,
				$this->formatValue($result)
			));
		}


		/**
		 * Asserts that the result has a given key or keys
		 *
		 * @access public
		 * @param int|string $key A key to check for
		 * @param ...
		 * @return Assertion The original assertion for method chaining
		 */
		public function has($key)
		{
			$result  = $this->resolve();
			$keys    = func_get_args();
			$missing = array();

			if (is_array($result) || (is_object($result) && $result instanceof \ArrayAccess)) {
				foreach ($keys as $key) {
					if (!(is_string($key) || is_int($key))) {
						throw new InvalidTestException(sprintf(
							'Invalid key %s provided to has()',
							$this->formatValue($key)
						));
					}

					if (!array_key_exists($key, $result)) {
						$missing[] = $key;
					}
				}

				if ($num_missing = count($missing)) {
					throw new FailedTestException(sprintf(
						'Assertion Failed: Value %s is missing %s out of %s keys',
						$this->formatValue($result),
						$num_missing,
						count($keys)
					));
				}

				$this->success = sprintf(
					'Value %s has all the keys specified: %s',
					$this->formatValue($result),
					$this->formatArray($keys)
				);

				return $this;
			}

			throw new InvalidTestException(sprintf(
				'Cannot use %s() on assertion or rejection of non-array-accessible type "%s"',
				__FUNCTION__,
				$this->formatType($result)
			));
		}


		/**
		 * Determines if an object is an instance of a certain class
		 *
		 */
		public function isInstanceOf($class, $modifier = NULL)
		{
			$result = $this->resolve();

			if (!is_object($result)) {
				throw new FailedTestException(sprintf(
					'Assertion Failed: Value %s is not an object',
					$this->formatValue($result)
				));
			}

			if ($modifier == EXACTLY && get_class($result) != ltrim($class, '\\')) {
				throw new FailedTestException(sprintf(
					'Assertion Failed: Value has a class of %s, not %s',
					get_class($result),
					$class
				));

			} elseif (!($result instanceof $class))  {
				throw new FailedTestException(sprintf(
					'Assertion Failed: Value is an instance of %s, not %s',
					get_class($result),
					$class
				));
			}
		}


		/**
		 * A more flexible pseudonym for equals() that allows for more complex comparisons
		 *
		 * When used with a single argument, this method provides very similar functionality to
		 * `equals()`, however, an additional/optional first parameter can be passed
		 *
		 * @access public
		 * @param string $modifier An optional string to modify the type of comparison
		 * @param mixed $value The value to compare our subject to
		 * @return Assertion The original assertion for method chaining
		 */
		public function is($value)
		{
			$result   = $this->resolve();
			$modifier = NULL;

			if (func_num_args() == 2) {
				$modifier = func_get_arg(0);
				$value    = func_get_arg(1);
			}

			$alert_values = [
				$this->formatValue($result),
				$modifier
					? (strpos($modifier, '=') !== FALSE ? $modifier . ' to' : $modifier)
					: 'equal to',
				$this->formatValue($value)
			];

			if (!self::compareReduced($result, $modifier, $value)) {
				throw new FailedTestException(vsprintf(
					'Assertion Failed: Value %s is not %s %s',
					$alert_values
				));
			}

			$this->success = vsprintf(
				'Value %s is %s %s',
				$alert_values
			);

			return $this;
		}


		/**
		 * Asserts that the length/size of the result measures to a certain number
		 *
		 * @access public
		 * @param string $condition An optional condition: GT, LT, GTE, LTE
		 * @param int $size The size to compare to
		 * @return Assertion The original assertion for method chaining
		 */
		public function measures($size)
		{
			$result   = $this->resolve();
			$length   = NULL;
			$modifier = NULL;

			if (func_num_args() == 2) {
				$modifier = func_get_arg(0);
				$size     = func_get_arg(1);
			}


			if (is_array($result) || (is_object($result) && $result instanceof \Countable)) {
				$length = count($result);
			} elseif (is_string($result)) {
				$encoding = mb_detect_encoding($result . 'e', 'UTF-8, ISO-8859-1, ASCII');
				$length   = mb_strlen($result, $encoding);
			}

			if (isset($length)) {

				if (!self::compareReduced($length, $modifier, $size)) {
					throw new FailedTestException(sprintf(
						'Assertion Failed: Value %s measures %d instead of %s%d',
						$this->formatValue($result),
						$length,
						$modifier ? ($modifier . ' ') : NULL,
						$size
					));
				}

				$this->success = sprintf(
					'Value %s measures %d, as expected',
					$this->formatValue($result),
					$size
				);

				return $this;
			}

			throw new InvalidTestException(sprintf(
				'Cannot use %s() on non countable or sizeable assertion / rejection of type "%s"',
				__FUNCTION__,
				gettype($result)
			));
		}


		/**
		 * Tests the current assertion to see if it throws an exception
		 *
		 * @access public
		 * @param string $class The exception class to test for
		 * @return Assertion The original assertion for method chaining
		 */
		public function throws($class)
		{
			$classes = !is_array($class)
				? [$class]
				: $class;

			foreach ($classes as $i => $class) {
				$classes[$i] = ltrim($class, '\\');
			}

			if (!($this->isMethod || $this->isFunction || $this->isClosure)) {
				throw new InvalidTestException(sprintf(
					'Cannot use non-callable value %s to assert or reject an exception is thrown',
					$this->formatValue($this->value)
				));
			}

			try {
				$result = $this->resolve();

			} catch (Exception $e) {
				$this->exception = $e;
				$exception_class = get_class($e);

				if (in_array($exception_class, $classes)) {

					$this->success = sprintf(
						'Callable %s threw one of the exceptions in %s',
						$this->formatCallable($this->value),
                                        	$this->formatArray($classes)
					);

					return $this;

				} else {
					throw new FailedTestException(sprintf(
						'Assertion Failed: Callable %s threw "%s" instead of "%s" (%s)',
						$this->formatCallable($this->value),
						$this->formatValue($exception_class),
						$this->formatArray($classes),
						$e->getMessage()
					));
				}
			}

			throw new FailedTestException(sprintf(
				'Assertion Failed: Callable %s returned %s instead of throwing one of %s',
				$this->formatCallable($this->value),
				$this->formatValue($result),
				$this->formatArray($classes)
			));
		}


		/**
		 * Provide an object to use for assertions which require an object
		 *
		 * @access public
		 * @param object $object The object to use
		 * @return Assertion The assertion, for method chaining
		 */
		public function using($object)
		{
			if (!$this->needsObject) {
				throw new InvalidTestException(sprintf(
					'Cannot assert or reject `using()` object instance on static "%s"',
					$this->value
				));

			} elseif (!is_object($object)) {
				throw new InvalidTestException(sprintf(
					'Cannot assert or reject "%s" `using()` non-object %s',
					$this->value,
					$this->formatValue($object)
				));

			} elseif (!($object instanceof $this->class)) {
				throw new InvalidTestException(sprintf(
					'Cannot assert or reject "%s" `using()` object of class "%s"',
					$this->value,
					$this->class
				));
			}

			$this->object = $object;

			return $this;
		}


		/**
		 * Provide arguments for assertions which are callable
		 *
		 * @access public
		 * @param mixed $arg The first argument
		 * @param ...
		 * @return Assertion The assertion, for method chaining
		 */
		public function with()
		{
			if (!($this->isMethod || $this->isFunction || $this->isClosure)) {
				throw new InvalidTestException(sprintf(
					'Cannot assert or reject `with()` on non-callable %s',
					$this->formatValue($this->value)
				));
			}

			$this->args = func_get_args();

			return $this;
		}


		/**
		 * Checks whether or not an assertion requiring an object needs ones.
		 *
		 * @access private
		 * @return boolean TRUE if an object is needed and available, FALSE otherwise
		 */
		private function checkObject()
		{
			if (($this->isMethod || $this->isProperty) && $this->needsObject) {
				if ($this->object !== NULL) {
					return TRUE;
				}

				if ($this->isMethod) {
					throw new InvalidTestException(sprintf(
						'Cannot assert or reject instance method "%s" without `using()` an object',
						$this->method
					));

				} else {
					throw new InvalidTestException(sprintf(
						'Cannot assert or reject instance property "%s" without `using()` an object',
						$this->property
					));
				}
			}

			return FALSE;
		}


		/**
		 * All the requisite logic for loading an array assertion
		 *
		 * @access private
		 * @return void
		 */
		private function loadArray()
		{
			$this->isArray = TRUE;
		}


		/**
		 * All the requisite logic for loading a boolean assertion
		 *
		 * @access private
		 * @return void
		 */
		private function loadBoolean()
		{
			$this->isBoolean = TRUE;
		}


		/**
		 * All the requisite logic for loading a numeric assertion
		 *
		 * @access private
		 * @return void
		 */
		private function loadNumber()
		{
			$this->isNumber = TRUE;
		}


		/**
		 * All the requisite logic for loading a string assertion
		 *
		 * @access private
		 * @param boolean $raw Whether or not we should try special interpretations
		 * @return void
		 */
		private function loadString($raw)
		{
			if ($raw) {
				$this->isString = TRUE;

			} else {
				if (strpos($this->value, '::') !== FALSE) {
					if (preg_match(self::REGEX_PHP_METHOD, $this->value)) {
						$this->reflectMethod();
					} elseif (preg_match(self::REGEX_PHP_PROPERTY, $this->value)) {
						$this->reflectProperty();
					}

				} elseif (class_exists($this->value, FALSE)) {
					$this->isClass = TRUE;
					$this->class   = $this->value;

				} elseif (function_exists($this->value)) {
					$this->isFunction = TRUE;
					$this->call       = function($args) {
						return call_user_func_array($this->value, $args);
					};

				} else {
					$this->isString = TRUE;
				}
			}
		}


		/**
		 * All the requisite logic for loading an object assertion
		 *
		 * @access private
		 * @return void
		 */
		private function loadObject() {
			$this->isObject = TRUE;
			$this->class    = get_class($this->value);
			$this->object   = $this->value;

			if (is_callable($this->value)) {
				$this->call      = $this->value;
				$this->isClosure = TRUE;
			}
		}


		/**
		 * Formats an array
		 *
		 * @access private
		 * @param array $value The array to be formatted
		 * @return string The formatted array
		 */
		private function formatArray(array $value)
		{
			$formatted_array = '[\'' . join($value, '\', \'') . '\']';

			return $formatted_array;
		}


		/**
		 * Formats a Callable
		 *
		 * @access private
		 * @param callable $value The callable to be formatted
		 * @return string The formatted callable
		 */
		private function formatCallable($value)
		{
			if (is_object($value) && $value instanceof Closure) {
				return '[Closure]';
			} elseif (is_array($value)) {
				if (count($value) == 2) {
					if (is_object($value[0])) {
						return get_class($value[0]) . '::' . $value[1];
					} else {
						return ((string) $value[0]) . '::' . $value[1];
					}
				}
			} else {
				return (string) $value;
			}
		}


		/**
		 * Formats a type
		 *
		 * @access private
		 * @param mixed $value A value whose formatted type we want
		 * @return string The formatted type
		 */
		private function formatType($value)
		{
			if (is_object($value)) {
				return sprintf('object [%s]', get_class($value));
			} else {
				return gettype($value);
			}
		}


		/**
		 * Formats a value somewhat neatly (depending on type) into a printable string
		 *
		 * @access private
		 * @param mixed $value The value to format
		 * @return string A hopefully nice string represenation of the original value
		 */
		private function formatValue($value)
		{
			$detail = NULL;

			switch($type = gettype($value)) {
				case 'object':
					$value = get_class($value);
					break;
				case 'string':
					$value = '"' . $value . '"';
					break;
				case 'array':
					$value = count($value);
					break;
				case 'boolean':
					$value = $value
						? 'TRUE'
						: 'FALSE';
					break;
				case 'NULL':
					$type  = NULL;
					$value = 'NULL';
					break;
				default:
					$value = $value;
					break;
			}

			return ($type ? '[' . $type . ']' : '')           // A type if available (not on NULL)
				   . '('
				   .     $value                               // The easy representation of a value
				   .     ($detail ? (' : ' . $detail) : NULL) // Available detail
				   . ')';

		}


		/**
		 * Reflects a method and provides resolution callable
		 *
		 * @access private
		 * @return void
		 */
		private function reflectMethod()
		{
			list($class, $method) = explode('::', $this->value);
			$this->class          = ltrim($class, '\\');
			$this->method         = $method;
			$this->isMethod       = TRUE;

			try {
				$reflection = new \ReflectionMethod($class, $method);

				if (!$reflection->isPublic()) {
					$reflection->setAccessible(TRUE);
				}

				if ($reflection->isStatic()) {
					$this->call = function($args) use ($reflection) {
						return $reflection->invokeArgs(NULL, $args);
					};

				} else {
					$this->needsObject = TRUE;

					$this->call = function($object, $args) use ($reflection) {
						return $reflection->invokeArgs($object, $args);
					};
				}

			} catch (\ReflectionException $e) {
				throw new InvalidTestException(sprintf(
					'Cannot assert or reject undefined method "%s" on class "%s"',
					$this->method,
					$this->class
				));
			}
		}


		/**
		 * Reflects a property and provides resolution callable
		 *
		 * @access private
		 * @return void
		 */
		private function reflectProperty()
		{
			list($class, $property) = explode('::', $this->value);
			$this->class            = ltrim($class, '\\');
			$this->property         = ltrim($property, '\\$');
			$this->isProperty       = TRUE;

			try {
				$reflection = new \ReflectionProperty($this->class, $this->property);

				if (!$reflection->isPublic()) {
					$reflection->setAccessible(TRUE);
				}

				if ($reflection->isStatic()) {
					$this->call = function() use ($reflection) {
						return $reflection->getValue();
					};

				} else {
					$this->needsObject = TRUE;

					$this->call = function($object) use ($reflection) {
						return $reflection->getvalue($object);
					};
				}

			} catch (\ReflectionException $e) {
				throw new InvalidTestException(sprintf(
					'Cannot assert or reject undefined property "%s" on class "%s"',
					$this->property,
					$this->class
				));
			}
		}


		/**
		 * Resolves the complete assertion
		 *
		 * @access private
		 * @return mixed The assertion resolution
		 */
		private function resolve()
		{
			if ($this->isMethod || $this->isFunction || $this->isClosure) {
				$call = $this->call;

				return $this->checkObject()
					? $call($this->object, $this->args)
					: $call($this->args);

			} elseif ($this->isProperty) {
				$call = $this->call;

				return $this->checkObject()
					? $call($this->object)
					: $call();

			} else {
				return $this->value;
			}
		}
	}
}
