# Assertions

Assertions are the core of any testing framework.  Lab provides a single `Assertion` class which can be used to make assertions.  Assertions basically the most granular level of testing you can get to and follow the idea that you're code is saying something like, "I want to assert that Y X Z" where Y is one value, X is a comparison, and Z is another value.  For example, I want to assert 2 + 2 equals 4.  To do this in lab I would write the following inside a test:

```php
accept(2+2)->equals(4);
```

The above line of code shows us using the `accept()` helper function that is provided by the `lab.php` script.  If you're using the `Assertion` class separately, the equivalent code would look something like this:

```php
$asserted_value = new Assertion(2+2);
$asserted_value->equals(4);
```

## Assertion Methods

There are probably different types of assertions you want to make.  For example, just as in real life, you might want to say something like, "I assert racecar contains the word 'ace'."  Using various methods on an assertion, we can do many of the same types of assertions we make in language in our code.

```php
accept('racecar')->contains('ace');
```

To get an idea of exactly what types of assertions can be made, check out the [API Docs](./api/classes/Dotink/Lab/Assertion.md).

## Multiple Assertions

The `Assertion` class supports method chaining in such a way that multiple assertions can be made with easy:

```php
accept('We band of merry men, will find a quest for thee.')
	-> begins ('We band of merry men')
	-> ends   ('will find a quest for thee.')
;
```

## False Assertions

What happens if we assert something that is not true?  For example, what if I tried to assert that 2 + 2 was equal to 5 instead of 4?  In cases of a failed assertion, the `Assertion` class will throw an exception with a message containing more detailed information.  For example:

```php
try {
	$asserted_value = new Assertion(2+2);
	$asserted_value->equals(5);
} catch (\Exception $e) {
	echo $e->getMessage();
}
```

The above would output something like the following:

```
Assertion Failed: Expected [integer](5) but got [integer](4)
```

In short, we told it that it would see 5, but it really saw 4, so it's calling us out on it.

## Smart Assertions

Because the `Assertion` class is designed to pivot around a single value or subject, we've made it smart enough to recognize certain types of input as more meaningful than others.  For example, you might expect the following to be a valid assertion:

```php
accept('ltrim')->equals('ltrim');
```

This, however, throws, an exception.  If we take a look at our message we may get some clue as to what's going on.  The message reads: `Assertion Failed: Expected [string]("ltrim") but got (NULL)`.  Lab's assertion library recognizes that this is a function and rather than test the function name itself (probably not very valuable), it attempts to test the return value of the function instead.  We can modify our assertion and watch the following go by without issue:

```php
accept('ltrim')->equals(NULL);
```

### Adding Context

Smart assertions often need context to be useful.  For example, the `ltrim` function discussed above, while testable all on its own, would probably make more sense if we tested its output when provided a given string.  The `Assertion` class has a number of methods for providing context to various smart assertions.

#### with()

The `with()` method allows us to define the arguments that a smart assertion is executed with.  Using our previous example, we can see a more complex and still valid assertion:

```php
accept('ltrim')->with('alphabet', 'alph')->equals('bet');
```

#### using()

Smart assertions are not just useful for simple functions.  You can also use them to work with classes and objects.  If you're attempting to make an assertion on a non-static method, i.e. one that requires and object, you can chain the `using()` method onto your assertion to give it object context:

```php
accept('MyClass::method')
	-> using  ($my_object)
	-> equals ('some value')
;
```

The above example would run the `MyClass::method` using an instantiated object of the same class and stored in `$my_object`; lastly, it would assert that the result of running that method on that object was equal to `'some value'`.  Similarly, we could assert the value of a property:

```php
accept('MyClass::$property')
	-> using  ($my_object)
	-> equals ('property value')
;
```

### Putting It All Together

Combining what we now know about multiple assertions, smart assertions, and combining contexts, we can see how we can quickly build complex assertions with very little code.  Let's work with PHP's `DateTime` class as an example:

```php
//
// Assuming the date is Saturday, March 23rd of 2013
//

$today     = new \DateTime();
$yesterday = new \DateTime('yesterday');

accept('DateTime::format')

	//
	// Today
	//

	-> using  ($today)

	-> with   ('Y')
	-> equals ('2013')

	-> with   ('F')
	-> equals ('March')

	//
	// Yesterday
	//

	-> using  ($yesterday)

	-> with   ('l')
	-> equals ('Friday')
;
```

### Private and Protected Methods or Properties

Lab's `Assertion` class is not just smart enough to recognize a method, property, or function for what it is, it's also smart enough to realize that if you're trying to make a direct assertion on it that it shouldn't get in your way.  For this reason, it is 100% possible to test private and protected methods or properties using a smart assertion.  This will not void any encapsulated access restrictions, that is to say, simply because you want to assert that a private method, for example, returns a specific value in a certain context, it does not mean that private method will have unwarranted access to the private methods of other classes, for example.

This ensure you get the best of both worlds.  That is, you can be sure that you won't be inadvertently accessing data or logic during testing that would otherwise be inaccessible during actual execution of your program.

## Dumb Assertions

What if you actually do want to run a test against a string that looks like a function or method call, or even an array callback?  You can force the `Assertion` class to use a "dumb" assertion by passing a `TRUE` value as the second argument to its constructor:

```php
$today = new \DateTime();

accept([$today, 'format'], TRUE)->measures(2)->contains('format');
````

## Advanced Assertions

Many other assertion libraries use additional methods like `isNotEqualTo` or `assertNotNull` to allow you to make negative assertions.  To do this Lab bundles another class along with `Assertion`, and you'll be know that it's API is **exactly the same** with one minor difference.  Instead of using the `Assertion` class or the `accept()` helper function, yo use the `Rejection()` class or the `reject()` helper function.

```php
reject(2+2)->equals(5);
```

Unlike an assertion with the same arguments, this line will not throw an exception and as you would expect, the following line would:

```php
reject(2+2)->equals(4);
```

In this regard, it is much better to think about Lab's tests as a series of claims as opposed to simply a series of assertions.  Your claims is either that you **assert** something to be the case or that you **reject** that it is the case.  We can understand how a `Rejection` works by understanding more about how `Assertion` works.  We stated previously that when an assertion failed in Lab it would throw an `Exception`.  What happens, however, if some of the code that the assertion relies on throws an exception?  It also fails.  Well, unless you use...

### throws()

The `throws()` method is a simple way to check whether or not an assertion that requires additional logic throws an exception.  Since we can actually use a smart assertion for a `Closure` object, let's illustrate this briefly:

```php
accept(function() {
	throw new \Exception('fail!');
})->throws('Exception');
```

Despite its best efforts, our anonymous function was unable to refute our assertion because, in the end, our assertion was merely that it would try to do so!  We can see from the above that we have to be explicit about what is being thrown, however, since we can now understand how Lab is able to deal with this circumstance, we can understand how the aforementioned `Rejection` class works.

### Asserting an Assertion Failed

An assertion being one kind of claim, and a rejection being another, it is important to realize that one necessitates the other and often times many examples of the other.  For example, an assertion that the sky is blue is necessarily a rejection that it is red assuming blue and red are mutually exclusive.  It is, similarly, a rejection that it is green.  The opposite is not always true though.  For example, I could reject the assertion that the sky is green.  This does not, however, necessitate that I assert it to be some other color, merely that I assert it not to be green.

Using this principle combined with our previous mention of `throws()`, we can see how the `Rejection` class works:

```php
accept(function() {
	accept(2+2)->equals(5);
})->throws('Exception');
```

In short, if an assertion using the same information would fail, a rejection would pass.
