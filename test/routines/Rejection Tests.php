<?php namespace Dotink\Lab
{
	return [

		//
		// Out setup adds a stupid class called Calculator, pretty useless in real life, but
		// not so bad for testing.
		//

		'setup' => function($data, $shared) {

			$shared->coverageEngine->preserveFile($data['root'] . '/src/Rejection.php');

			//
			// A stupid calculator class
			//

			class Calculator
			{
				private $seed  = NULL;
				private $value = NULL;

				public function __construct($seed)
				{
					$this->seed = $seed;
				}

				public function add($subject)
				{
					$this->value = $this->seed + $subject;

					return $this->equals();
				}

				private function equals()
				{
					return $this->value;
				}

			}
		},

		'tests' => [

			//
			// Simple rejections test most common methods on simple input
			//

			'Simple Rejections' => function($data) {
				reject(1+1)->equals(3);
				reject(NULL)->equals(TRUE);

				reject('12345')->measures(6);
				reject('12345')->measures(GT, 5);
				reject('12345')->measures(LT, 5);

				reject('abcd')->measures(GTE, 5);
				reject('abcd')->measures(LTE, 3);

				reject(5)->is(GT, 5);
				reject(4)->is(5);
				reject(6)->is(LTE, 5);
				reject(7)->is(EXACTLY, 10);
				reject(NULL)->is(EXACTLY, FALSE);
				reject(TRUE)->is(EXACTLY, 'non-empty string');
				reject(FALSE)->is('non-empty string');
			},

			//
			//  Rejections on closures use the return value of the closure to test against
			//

			'Rejections on Closures' => function($data) {
				reject(function(){ return 1; })->equals(2, TRUE);
				reject(function(){ return 'test'; })->measures(3);
			},


			//
			// Negated rejections assert that our simple rejections will throw exceptions if
			// they're failed tests.
			//

			'Negated Rejections' => function($data) {

				assert(function(){ reject(1+1)->equals(2);           })->throws('Dotink\Lab\FailedTestException');
				assert(function(){ reject(NULL)->equals(NULL, TRUE); })->throws('Dotink\Lab\FailedTestException');

				assert(function(){ reject('12345')->measures(5);     })->throws('Dotink\Lab\FailedTestException');
				assert(function(){ reject('12345')->measures(GT, 4); })->throws('Dotink\Lab\FailedTestException');
				assert(function(){ reject('12345')->measures(LT, 6); })->throws('Dotink\Lab\FailedTestException');

				assert(function(){ reject('abcd')->measures(GTE, 4); })->throws('Dotink\Lab\FailedTestException');
				assert(function(){ reject('abcd')->measures(LTE, 4); })->throws('Dotink\Lab\FailedTestException');

				assert(function(){ reject('abcd')->measures(GTE, 1); })->throws('Dotink\Lab\FailedTestException');
				assert(function(){ reject('abcd')->measures(LTE, 7); })->throws('Dotink\Lab\FailedTestException');

				assert(function(){ reject(TRUE)->is(EXACTLY, TRUE);  })->throws('Dotink\Lab\FailedTestException');
				assert(function(){ reject(2+2)->is(4);               })->throws('Dotink\Lab\FailedTestException');
				assert(function(){ reject(6)->is(LT, '10');          })->throws('Dotink\Lab\FailedTestException');
			},

			//
			// Tests "smart" (parsed) rejections using a specific object.  This tests both
			// private variable access, private method access, and public method access using
			// with() to pass arguments.
			//

			'Smart Rejections' => function($data) {
				$calculator1 = new Calculator(5);
				$calculator2 = new Calculator(10);
				$calculator3 = new Calculator(-7);

				//
				// Checks a private variable
				//

				reject('Dotink\Lab\Calculator::$seed')
					-> using($calculator1) -> equals(6)
					-> using($calculator2) -> equals(5)
					-> using($calculator3) -> equals(-3)
				;

				//
				// Runs a public method
				//

				reject('Dotink\Lab\Calculator::add')
					-> using($calculator1) -> with(5) -> equals(11)
					-> using($calculator2) -> with(3) -> equals(290)
					-> using($calculator3) -> with(2) -> equals(-3)
				;

				//
				// Access a private method
				//

				reject('Dotink\Lab\Calculator::equals')
					-> using($calculator1) -> equals(3)
					-> using($calculator2) -> equals('hi')
					-> using($calculator3) -> equals(-2)
				;


			},


			//
			//  Dumb Rejections
			//

			'Dumb Rejections' => function($data) {
				reject('ltrim', TRUE)->equals('');
				reject('Dotink\Lab\Calculator::$seed', TRUE)->measures(23);
			},

			//
			// Contains
			//

			'Contains Rejections' => function($data) {
				reject('This is a test string')->contains('foo');
				reject('This is a test string')->contains('FOO', FALSE);

				assert(function(){
					reject('This is a test string')->contains('test');
				})->throws('Dotink\Lab\FailedTestException');

				assert(function(){
					reject('This is a test string')->contains('TEST', FALSE);
				})->throws('Dotink\Lab\FailedTestException');

				assert(function(){
					reject('This is a test string')->contains('test', TRUE);
				})->throws('Dotink\Lab\FailedTestException');

				reject(['a' => 'foo', 'b' => 'bar'])->contains('hello');

				reject(function(){
					reject(['a' => 'foo', 'b' => 'bar'])->contains('foobar');
				})->throws('Dotink\Lab\FailedTestException');

				reject(['a' => 'foo', 'b' => 'bar'])->has('b', 'c');

				assert(function(){
					reject(['a' => 'foo', 'b' => 'bar'])->has('b');
				})->throws('Dotink\Lab\FailedTestException');
			},

			//
			// Ends and Begins
			//

			'Ends and Begins Rejections' => function($data) {
				reject('I have a merry band of brothers')
					-> begins ('I have the')
					-> ends   ('group of brothers');

				assert(function(){
					reject('I have a merry band of brothers')->begins('I have');
				})->throws('Dotink\Lab\FailedTestException');

				assert(function(){
					reject('I have a merry band of brothers')->ends('band of brothers');
				})->throws('Dotink\Lab\FailedTestException');
			}

		]
	];
}
