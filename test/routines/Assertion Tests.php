<?php namespace Lab
{

	use Dotink\Parody;

	return [

		//
		// Out setup adds a stupid class called Calculator, pretty useless in real life, but
		// not so bad for testing.
		//

		'setup' => function($data, $shared) {

			// $shared->coverageEngine->preserveFile($data['root'] . '/src/Assertion.php');

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

				$this->accept(1+1)->equals(2);
				$this->accept(NULL)->equals(FALSE);

				$this->accept('12345')->measures(5);
				$this->accept('12345')->measures(GT, 4);
				$this->accept('12345')->measures(LT, 6);

				$this->accept('abcd')->measures(GTE, 3);
				$this->accept('abcd')->measures(LTE, 5);

				$this->accept(5)->is(GT, 4);
				$this->accept(4)->is(4);
				$this->accept(6)->is(LTE, 6);
				$this->accept(7)->is(EXACTLY, 7);
				$this->accept(NULL)->is(FALSE);
				$this->accept(TRUE)->is('non-empty string');
			},

			//
			//  Assertions on closures use the return value of the closure to test against
			//

			'Assertions on Closures' => function($data) {
				$this->accept(function(){ return 1; })->equals(1, TRUE);
				$this->accept(function(){ return 'test'; })->measures(4);
			},


			//
			// Negated assertions make sure our simple assertions will throw exceptions if they're
			// failed tests.
			//

			'Negated Assertions' => function($data) {

				$this->accept(function(){ $this->accept(1+1)->equals(3);            })->throws('Lab\FailedTestException');
				$this->accept(function(){ $this->accept(NULL)->equals(FALSE, TRUE); })->throws('Lab\FailedTestException');

				$this->accept(function(){ $this->accept('12345')->measures(6);      })->throws('Lab\FailedTestException');
				$this->accept(function(){ $this->accept('12345')->measures(GT, 5);  })->throws('Lab\FailedTestException');
				$this->accept(function(){ $this->accept('12345')->measures(LT, 5);  })->throws('Lab\FailedTestException');

				$this->accept(function(){ $this->accept('abcd')->measures(GTE, 5);  })->throws('Lab\FailedTestException');
				$this->accept(function(){ $this->accept('abcd')->measures(LTE, 3);  })->throws('Lab\FailedTestException');

				$this->accept(function(){ $this->accept(TRUE)->is(EXACTLY, '1');    })->throws('Lab\FailedTestException');
				$this->accept(function(){ $this->accept(2+2)->is(5);                })->throws('Lab\FailedTestException');
				$this->accept(function(){ $this->accept(6)->is(GT, '10');           })->throws('Lab\FailedTestException');
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

				$this->accept('Lab\Calculator::$seed')
					-> using($calculator1) -> equals(5)
					-> using($calculator2) -> equals(10)
					-> using($calculator3) -> equals(-7)
				;

				//
				// Runs a public method
				//

				$this->accept('Lab\Calculator::add')
					-> using($calculator1) -> with(5) -> equals(10)
					-> using($calculator2) -> with(3) -> equals(13)
					-> using($calculator3) -> with(2) -> equals(-5)
				;

				//
				// Access a private method
				//

				$this->accept('Lab\Calculator::equals')
					-> using($calculator1) -> equals(10)
					-> using($calculator2) -> equals(13)
					-> using($calculator3) -> equals(-5)
				;


			},


			//
			//  Dumb Assertions
			//

			'Dumb Assertions' => function($data) {
				$this->accept('ltrim', TRUE)->equals('ltrim');
				$this->accept('Lab\Calculator::$seed', TRUE)->measures(21);
			},

			//
			// Contains
			//

			'Contains Assertions' => function($data) {
				$this->accept('This is a test string')->contains('test');
				$this->accept('This is a test string')->contains('Test', FALSE);

				$this->accept(function(){
					$this->accept('This is a test string')->contains('foo');
				})->throws('Lab\FailedTestException');

				$this->accept(function(){
					$this->accept('This is a test string')->contains('foo');
				})->throws('Lab\FailedTestException');

				$this->accept(function(){
					$this->accept('This is a test string')->contains('foo', FALSE);
				})->throws('Lab\FailedTestException');

				$this->accept(['a' => 'foo', 'b' => 'bar'])->contains('foo');

				$this->accept(function(){
					$this->accept(['a' => 'foo', 'b' => 'bar'])->contains('foobar');
				})->throws('Lab\FailedTestException');

				$this->accept(['a' => 'foo', 'b' => 'bar'])->has('b');

				$this->accept(function(){
					$this->accept(['a' => 'foo', 'b' => 'bar'])->has('c');
				})->throws('Lab\FailedTestException');
			},

			//
			// Ends and Begins
			//

			'Ends and Begins Assertions' => function($data) {
				$this->accept('I have a merry band of brothers')
					-> begins ('I have a')
					-> ends   ('band of brothers');

				$this->accept(function(){
					$this->accept('I have a merry band of brothers')->begins('You have');
				})->throws('Lab\FailedTestException');

				$this->accept(function(){
					$this->accept('I have a merry band of brothers')->ends('group of brothers');
				})->throws('Lab\FailedTestException');
			}

		]
	];
}
