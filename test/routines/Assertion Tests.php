<?php namespace Dotink\Lab
{

	use Dotink\Parody;

	return [

		//
		// Out setup adds a stupid class called Calculator, pretty useless in real life, but
		// not so bad for testing.
		//

		'setup' => function($data, $shared) {

			$shared->coverageEngine->preserveFile($data['root'] . '/src/Assertion.php');

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
			// Simple assertions test most common methods on simple input
			//

			'Simple Assertions' => function($data) {

				assert(1+1)->equals(2);
				assert(NULL)->equals(FALSE);

				assert('12345')->measures(5);
				assert('12345')->measures(GT, 4);
				assert('12345')->measures(LT, 6);

				assert('abcd')->measures(GTE, 3);
				assert('abcd')->measures(LTE, 5);

				assert(5)->is(GT, 4);
				assert(4)->is(4);
				assert(6)->is(LTE, 6);
				assert(7)->is(EXACTLY, 7);
				assert(NULL)->is(FALSE);
				assert(TRUE)->is('non-empty string');
			},

			//
			//  Assertions on closures use the return value of the closure to test against
			//

			'Assertions on Closures' => function($data) {
				assert(function(){ return 1; })->equals(1, TRUE);
				assert(function(){ return 'test'; })->measures(4);
			},


			//
			// Negated assertions make sure our simple assertions will throw exceptions if they're
			// failed tests.
			//

			'Negated Assertions' => function($data) {

				assert(function(){ assert(1+1)->equals(3);            })->throws('Exception');
				assert(function(){ assert(NULL)->equals(FALSE, TRUE); })->throws('Exception');

				assert(function(){ assert('12345')->measures(6);      })->throws('Exception');
				assert(function(){ assert('12345')->measures(GT, 5);  })->throws('Exception');
				assert(function(){ assert('12345')->measures(LT, 5);  })->throws('Exception');

				assert(function(){ assert('abcd')->measures(GTE, 5);  })->throws('Exception');
				assert(function(){ assert('abcd')->measures(LTE, 3);  })->throws('Exception');

				assert(function(){ assert(TRUE)->is(EXACTLY, '1');    })->throws('Exception');
				assert(function(){ assert(2+2)->is(5);                })->throws('Exception');
				assert(function(){ assert(6)->is(GT, '10');           })->throws('Exception');
			},

			//
			// Tests "smart" (parsed) assertions using a specific object.  This tests both
			// private variable access, private method access, and public method access using
			// with() to pass arguments.
			//

			'Smart Assertions' => function($data) {
				$calculator1 = new Calculator(5);
				$calculator2 = new Calculator(10);
				$calculator3 = new Calculator(-7);

				//
				// Checks a private variable
				//

				assert('Dotink\Lab\Calculator::$seed')
					-> using($calculator1) -> equals(5)
					-> using($calculator2) -> equals(10)
					-> using($calculator3) -> equals(-7)
				;

				//
				// Runs a public method
				//

				assert('Dotink\Lab\Calculator::add')
					-> using($calculator1) -> with(5) -> equals(10)
					-> using($calculator2) -> with(3) -> equals(13)
					-> using($calculator3) -> with(2) -> equals(-5)
				;

				//
				// Access a private method
				//

				assert('Dotink\Lab\Calculator::equals')
					-> using($calculator1) -> equals(10)
					-> using($calculator2) -> equals(13)
					-> using($calculator3) -> equals(-5)
				;


			},


			//
			//  Dumb Assertions
			//

			'Dumb Assertions' => function($data) {
				assert('ltrim', TRUE)->equals('ltrim');
				assert('Dotink\Lab\Calculator::$seed', TRUE)->measures(28);
			},

			//
			// Contains
			//

			'Contains Assertions' => function($data) {
				assert('This is a test string')->contains('test');
				assert('This is a test string')->contains('Test', FALSE);

				assert(function(){
					assert('This is a test string')->contains('foo');
				})->throws('Exception');

				assert(function(){
					assert('This is a test string')->contains('foo');
				})->throws('Exception');

				assert(function(){
					assert('This is a test string')->contains('foo', FALSE);
				})->throws('Exception');

				assert(['a' => 'foo', 'b' => 'bar'])->contains('foo');

				assert(function(){
					assert(['a' => 'foo', 'b' => 'bar'])->contains('foobar');
				})->throws('Exception');

				assert(['a' => 'foo', 'b' => 'bar'])->has('b');

				assert(function(){
					assert(['a' => 'foo', 'b' => 'bar'])->has('c');
				})->throws('Exception');
			},

			//
			// Ends and Begins
			//

			'Ends and Begins Assertions' => function($data) {
				assert('I have a merry band of brothers')
					-> begins ('I have a')
					-> ends   ('band of brothers');

				assert(function(){
					assert('I have a merry band of brothers')->begins('You have');
				})->throws('Exception');

				assert(function(){
					assert('I have a merry band of brothers')->ends('group of brothers');
				})->throws('Exception');
			}

		]
	];
}
