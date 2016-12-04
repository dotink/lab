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

				accept(1+1)->equals(2);
				accept(NULL)->equals(FALSE);

				accept('12345')->measures(5);
				accept('12345')->measures(GT, 4);
				accept('12345')->measures(LT, 6);

				accept('abcd')->measures(GTE, 3);
				accept('abcd')->measures(LTE, 5);

				accept(5)->is(GT, 4);
				accept(4)->is(4);
				accept(6)->is(LTE, 6);
				accept(7)->is(EXACTLY, 7);
				accept(NULL)->is(FALSE);
				accept(TRUE)->is('non-empty string');
			},

			//
			//  Assertions on closures use the return value of the closure to test against
			//

			'Assertions on Closures' => function($data) {
				accept(function(){ return 1; })->equals(1, TRUE);
				accept(function(){ return 'test'; })->measures(4);
			},


			//
			// Negated assertions make sure our simple assertions will throw exceptions if they're
			// failed tests.
			//

			'Negated Assertions' => function($data) {

				accept(function(){ accept(1+1)->equals(3);            })->throws('Dotink\Lab\FailedTestException');
				accept(function(){ accept(NULL)->equals(FALSE, TRUE); })->throws('Dotink\Lab\FailedTestException');

				accept(function(){ accept('12345')->measures(6);      })->throws('Dotink\Lab\FailedTestException');
				accept(function(){ accept('12345')->measures(GT, 5);  })->throws('Dotink\Lab\FailedTestException');
				accept(function(){ accept('12345')->measures(LT, 5);  })->throws('Dotink\Lab\FailedTestException');

				accept(function(){ accept('abcd')->measures(GTE, 5);  })->throws('Dotink\Lab\FailedTestException');
				accept(function(){ accept('abcd')->measures(LTE, 3);  })->throws('Dotink\Lab\FailedTestException');

				accept(function(){ accept(TRUE)->is(EXACTLY, '1');    })->throws('Dotink\Lab\FailedTestException');
				accept(function(){ accept(2+2)->is(5);                })->throws('Dotink\Lab\FailedTestException');
				accept(function(){ accept(6)->is(GT, '10');           })->throws('Dotink\Lab\FailedTestException');
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

				accept('Dotink\Lab\Calculator::$seed')
					-> using($calculator1) -> equals(5)
					-> using($calculator2) -> equals(10)
					-> using($calculator3) -> equals(-7)
				;

				//
				// Runs a public method
				//

				accept('Dotink\Lab\Calculator::add')
					-> using($calculator1) -> with(5) -> equals(10)
					-> using($calculator2) -> with(3) -> equals(13)
					-> using($calculator3) -> with(2) -> equals(-5)
				;

				//
				// Access a private method
				//

				accept('Dotink\Lab\Calculator::equals')
					-> using($calculator1) -> equals(10)
					-> using($calculator2) -> equals(13)
					-> using($calculator3) -> equals(-5)
				;


			},


			//
			//  Dumb Assertions
			//

			'Dumb Assertions' => function($data) {
				accept('ltrim', TRUE)->equals('ltrim');
				accept('Dotink\Lab\Calculator::$seed', TRUE)->measures(28);
			},

			//
			// Contains
			//

			'Contains Assertions' => function($data) {
				accept('This is a test string')->contains('test');
				accept('This is a test string')->contains('Test', FALSE);

				accept(function(){
					accept('This is a test string')->contains('foo');
				})->throws('Dotink\Lab\FailedTestException');

				accept(function(){
					accept('This is a test string')->contains('foo');
				})->throws('Dotink\Lab\FailedTestException');

				accept(function(){
					accept('This is a test string')->contains('foo', FALSE);
				})->throws('Dotink\Lab\FailedTestException');

				accept(['a' => 'foo', 'b' => 'bar'])->contains('foo');

				accept(function(){
					accept(['a' => 'foo', 'b' => 'bar'])->contains('foobar');
				})->throws('Dotink\Lab\FailedTestException');

				accept(['a' => 'foo', 'b' => 'bar'])->has('b');

				accept(function(){
					accept(['a' => 'foo', 'b' => 'bar'])->has('c');
				})->throws('Dotink\Lab\FailedTestException');
			},

			//
			// Ends and Begins
			//

			'Ends and Begins Assertions' => function($data) {
				accept('I have a merry band of brothers')
					-> begins ('I have a')
					-> ends   ('band of brothers');

				accept(function(){
					accept('I have a merry band of brothers')->begins('You have');
				})->throws('Dotink\Lab\FailedTestException');

				accept(function(){
					accept('I have a merry band of brothers')->ends('group of brothers');
				})->throws('Dotink\Lab\FailedTestException');
			}

		]
	];
}
