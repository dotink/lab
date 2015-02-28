# Lab

[![Join the chat at https://gitter.im/dotink/lab](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/dotink/lab?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)
[![Build Status](https://travis-ci.org/dotink/lab.svg?branch=master)](https://travis-ci.org/dotink/lab)

Lab is a concise and direct testing framework that isolates testing contexts, class interaction,
etc.  It is simple to use and very direct.  It works well with
[Parody](http://www.github.com/dotink/parody) to provide much of the same testing capacity as more
complex frameworks with a sweeter API and overall simpler approach.


## Installation

```bash
composer global require dotink/lab
```

## Basic Usage

Once you have lab installed, getting started is easy!

```bash
cd <path to project>
cp ~/.composer/vendor/dotink/lab.config ./lab.config
mkdir -p test/routines
```

You can now run lab for the first time

![Lab running for the first time](https://dl.dropbox.com/u/31068853/Images/Dotink%20Lab/lab-first_run.png)

### Adding a Test Set and Tests

To add a set of tests to Lab you can create a file in the tests folder with the name of the test
set you want.  Unlike other testing frameworks, this file is a simple PHP file that just returns an
array.  Let's add the following template to a file called `Example Tests.php`:

```php
<?php namespace Dotink\Lab
{
	return [
		'tests' => [

			// Our first test

			'First Test' => function($data){

			},
		]
	];
}
```

We can now re-run Lab and see our first test result:

![Lab running our first test](https://dl.dropbox.com/u/31068853/Images/Dotink%20Lab/lab-first_test.png)

### Making an Assertion

Each test will make one or more assertion.  Lab provides an `Assertion` class which can be used to
make assertions, however, you can use a third party library or basic PHP code if you prefer.
Regardless of how you make assertions, a test will fail in Lab whenever an uncaught exception is
thrown.  Let's examine this with a basic assertion using the Lab `assert` function which will
create a new instance of the build in `Assertion` class.

```php
<?php namespace Dotink\Lab
{
	return [
		'tests' => [

			// Our first test

			'First Test' => function($data){
				assert(2 + 2)->equals(5);
			},
		]
	];
}
```

This time, when we rerunning Lab, we see the failure immediately as well as some information
regarding the failure:

![Lab with a failing assertion](https://dl.dropbox.com/u/31068853/Images/Dotink%20Lab/lab-first_assertion.png)

#### "Smart" Assertions

The `Assertion` class is designed to provide features for the 90% of test cases you will need to
run with a concise and flexible syntax.  Part of its convenience is how it parses string input and
attempts to determine whether or not the string represents another piece of code, for example, a
class method, a property, or a function.  The following two lines of code are, for the most part,
equivalent:

```php
assert(ltrim('test', 't'))->equals('est', TRUE);

assert('ltrim')->with('test', 't')->equals('est');
```

##### Multiple Assertions

Although each of the above will do the exact same comparison to assert equal values, the second
benefits in two ways.  Firstly, by providing a function, method, or property name to `assert()`
directly, it is  possible to run multiple assertions over a single method without repeating the
actual method name:

```php
assert('ltrim')
	-> with   ('test', 't')
	-> equals ('est')

	-> with   ('another', 'an')
	-> equals ('other')

	-> with   ('  default  ')
	-> equals ('default  ')
;
```

##### Testing Private/Protected

In addition to running multiple assertions easily, using a "smart" assertions allows you to access
`private` and `protected` methods and properties.  Let's use the following absolutely useless class
to illustrate this:

```php
class Adder
{
	protected $seed = 0;

	public function __construct($seed)
	{
		$this->seed = $seed;
	}

	private function add($num)
	{
		return $this->seed + $num;
	}
}
```

Using the class above we can easily do the following assertions despite that the `add` method is
not publicly visible:

```php
assert('Adder::add')
	-> using  (new Adder(3))
	-> with   (2)
	-> equals (5)

	-> using  (new Adder(5))
	-> with   (3)
	-> equals (8)
;
```

Similarly, we could check the value of the `seed` property with a slightly different call:

```php
assert('Adder::$seed')
	-> using  (new Adder(3))
	-> equals (3)

	-> using  (new Adder(5))
	-> equals (5)
;
```

The above code also shows how we can call the `using` method to specify on which object we want to
access the property or method.  This allows us to easily test a number of objects which may have
been instantiated differently to ensure that behavior is consistent across a wider number of cases.

### Fixtures

It is important to understand that each test set / file which is added to Lab will run in a
completely separate execution of PHP.  Although we recommend organizing test sets per fixture, you
can create separate fixture includes and add them across multiple test sets if need be.  In all
cases, anything you need to do to prepare your testing environment or create data to test against
should be added to the `setup` key in the test set array:

```php
<?php namespace Dotink\Lab
{
	return [
		'setup' => function($data) {
			// setup code here
		},

		'tests' => [
			// tests here
		]
	];
}
```

If you have any setup that is required across **all** test sets / files, then you can add that
logic to the `lab.config` file in the closure referenced by the same key name.  Similarly you
will also find a `cleanup` key there which can be used for global cleanup code as well as added
to each separate test set for specific cleanup code:

```php
//
// The global 'cleanup' key can contain a closure to run fixture cleanup logic at the end
// of every test file
//

'cleanup' => function($data) {

},
```

### Custom Configuration Data

By now you may have realized that every closure either in the `lab.config` file or in a test set
takes in a `$data` parameter.  You may have also noted the `data` key in the `lab.config` which
points to an array.  By default this array contains only a `root` key which points to the directory
where the `lab.config` file is found.

You can add any arbitrary pieces of information you might need on a per test / setup / or cleanup
basis to this array.  Or use the provided root, for example, to load your classes using the `needs`
function:

```php
'setup' => function($data) {
	needs($data['root'] . '/src/Adder.php');
}
```

### Dependencies

The `needs` function, as seen in code above, provides a clean way to require your source files with
the nicely formatted output of Lab:

![Lab with a failing needs](https://dl.dropbox.com/u/31068853/Images/Dotink%20Lab/lab-failed_needs.png)

You may be, however, otherwise tempted to throw an autoloader or something similar in your `setup`
function(s).  While this is 100% possible, you'll need to set the `disable_autoloading` to `FALSE`
in the `lab.config` file.  If this is set to `TRUE` (default), Lab will register an autoloader
almost immediately which will prevent (by throwing an Exception) classes from being loaded in that
manner.  This is to reduce the chance that an unknown or unseen dependency will cause your unit
tests to become something more like integration tests.

## Conclusion

Lab is an easy-to-use, quick-to-set-up, and generally fun testing framework.  Combined with
Parody it represents a powerful tool for PHP testing which encompasses a lot of best practices
with regards to testing, including by not limited to:

- Simple, clear, and expressive API (limits mistakes in tests themselves)
- High degree of code isolation (disabling autoloading by default, explicit needs or mimicking)
- Limiting irrelevant context (each test set is executed independently by PHP)
- Non-negotiable hard fails (complete death on failure until it's resolved)
