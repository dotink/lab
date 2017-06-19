<?php

namespace Lab;

use Exception;

/**
 * A simple rejection library
 *
 * This class is essentially a proxy class for Assertion.  That is to say, you should expect
 * that any publically accessible methods available on Assertion are available on this class
 * and follow the same API.  The *only* difference between Assertion and this class is that
 * Assertion makes positive claims and this class makes negative claims.
 *
 * @copyright Copyright (c) 2013, Matthew J. Sahagian
 * @author Matthew J. Sahagian [mjs] <msahagian@dotink.org>
 *
 * @license Please reference the LICENSE.md file at the root of this distribution
 *
 * @package Lab
 */
class InvertedAssertion
{
	private $assertion = NULL;

	/**
	 * Create a new rejection
	 *
	 * @access public
	 * @param mixed $value The subject of our assertion
	 * @param boolean $raw Whether we should attempt anything smart on $value, default FALSE
	 * @return void
	 */
	public function __construct($value, $raw = FALSE)
	{
		$this->assertion = new Assertion($value, $raw);
	}

	/**
	 * Proxies methods to our internal assertion
	 *
	 * If the method called is actually one of the assertion methods, such as `equals()` or
	 * `has()`, for example, then this method will wrap the assertion and ensure it throws
	 * an exception, thereby asserting the opposite, or, rejecting.
	 *
	 * @access public
	 * @param string $method The method called
	 * @param array $args The arguments passed
	 * @return Rejection The Rejeciton object for method chaining
	 */
	public function __call($method, $args)
	{
		$assertion_success = FALSE;
		$assertion_methods = [
			'begins', 'contains', 'has', 'is', 'ends', 'equals', 'measures', 'throws'
		];


		if (in_array(strtolower($method), $assertion_methods)) {
			try {
				$assertion_success = call_user_func_array(
					[$this->assertion, $method],
					$args
				);

			} catch (FailedTestException $e) {

				//
				// If assertion threw the FailedTestException, this
				// means that the assertion failed which means the
				// rejection succeeded -- DO NOTHING
				//

			}

			if ($assertion_success) {
				throw new FailedTestException(sprintf(
					'Inverted Assertion Failed: %s',
					$this->assertion->alertSuccess()
				));
			}


		} else {
			call_user_func_array([$this->assertion, $method], $args);
		}

		return $this;
	}
}
