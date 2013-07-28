# Phockito - Mockito for PHP

Mocking framework inspired by Mockito for Java

Checkout [the original's website](http://mockito.org/) for the philosophy behind the API and more examples
(although be aware that this is only a partial implementation for now)

Thanks to the developers of Mockito for the inspiration, and hamcrest-php for making this easy.

## Example mocking:

```php
// Create the mock
$iterator = Phockito::mock('ArrayIterator');

// Use the mock object - doesn't do anything, functions return null
$iterator->append('Test');
$iterator->asort();

// Selectively verify execution
Phockito::verify($iterator)->append('Test');
// 1 is default - can also do 2, 3  for exact numbers, or 1+ for at least one, or 0 for never
Phockito::verify($iterator, 1)->asort();
```

If PHPUnit is available, on failure verify throws a `PHPUnit_Framework_AssertionFailedError` (looks like an assertion failure),
otherwise just throws an `Exception`

## Example stubbing:

```php
// Create the mock
$iterator = Phockito::mock('ArrayIterator');

// Stub in a value
Phockito::when($iterator->offsetGet(0))->return('first');

// Prints "first"
print_r($iterator->offsetGet(0));

// Prints null, because get(999) not stubbed
print_r($iterator->offsetGet(999));
```

Alternative API, jsMockito style

```php
// Stub in a value
Phockito::when($iterator)->offsetGet(0)->return('first');
```

## Spies

Mocks are full mocks - method calls to unstubbed function always return null, and never call the parent function.

You can also create partial mocks by calling `spy` instead of `mock`. With spies, method calls to unstubbed functions
call the parent function.

Because spies are proper subclasses, this lets you stub in methods that are called by other methods in a class

```php
class A {
	function Foo(){ return 'Foo'; }
	function Bar(){ return $this->Foo() . 'Bar'; }
}

// Create a mock
$mock = Phockito::mock('A');
print_r($mock->Foo()); // 'null'
print_r($mock->Bar()); // 'null'

// Create a spy
$spy = Phockito::spy('A');
print_r($spy->Foo()); // 'Foo'
print_r($spy->Bar()); // 'FooBar'

// Stub a method 
Phockito::when($spy)->Foo()->return('Zap');
print_r($spy->Foo()); // 'Zap'
print_r($spy->Bar()); // 'ZapBar'
```

## Argument matching

Phockito allows the use of [Hamcrest](http://code.google.com/p/hamcrest/) matchers on any argument. Hamcrest is a library of "matching functions" that, given a value, return true if that value
matches some rule.

Hamcrest matchers are not included by default, so the first step is to call `Phockito::include_hamcrest();` immediately after including Phockito. 
Note that this will import the Hamcrest matchers as global functions - passing false as an argument will keep your namespace clean by making all matchers only available as static methods of `Hamcrest` (at the expense of worse looking test code).

Once included you can pass a Hamcrest matcher as an argument in your when or verify rule, eg:

```php
class A {
	function Foo($a){ }
}

$stub = Phockito::mock('A');
Phockito::when($stub)->Foo(anything())->return('Zap');
```

Some common Hamcrest matchers:

- Core
	* `anything` - always matches, useful if you don't care what the object under test is
- Logical
	* `allOf` - matches if all matchers match, short circuits (like PHP &&)
	* `anyOf` - matches if any matchers match, short circuits (like PHP ||)
	* `not` - matches if the wrapped matcher doesn't match and vice versa
- Object
	* `equalTo` - test object equality using the == operator
	* `anInstanceOf` - test type
	* `notNullValue`, `nullValue` - test for null
- Number
	* `closeTo` - test floating point values are close to a given value
	* `greaterThan`, `greaterThanOrEqualTo`, `lessThan`, `lessThanOrEqualTo` - test ordering
- Text
	* `equalToIgnoringCase` - test string equality ignoring case
	* `equalToIgnoringWhiteSpace` - test string equality ignoring differences in runs of whitespace
	* `containsString`, `endsWith`, `startsWith` - test string matching

## Differences from Mockito

#### Stubbing methods more flexible

In Mockito, the methods when building a stub are limited to thenReturns, thenThrows. In Phockito, you can use any method
as long as it has 'return' or 'throw' in it, so `Phockito::when(...)->return(1)->thenReturn(2)` is fine.

#### Type-safe argument matching

In Mockito, to use a Hamcrest matcher, the `argThat` method is used to satisfy the type checker. In PHP, a little extra
help is needed. Phockito provides the `argOfTypeThat` for provided Hamcrest matchers to type-hinted parameters:

```php
class A {
    function Foo(B $b){ }
}

class B {}

$stub = Phockito::mock('A');
$b = new B();
Phockito::when($stub)->Foo(argOfTypeThat('B', is(equalTo($b))))->return('Zap');
```

It's also possible to pass a mock to 'when', rather than the result of a method call on a mock, e.g.
`Phockito::when($mock)->methodToStub(...)->thenReturn(...)`. This side-steps the type system entirely.

Note that `argOfTypeThat` is only compatible with object type-hints; arguments with `array` or `callable` type-hints
cannot be handled in a type-safe way.

#### Verify 'times' argument changed

In Mockito, the 'times' argument to verify is an object of interface VerificationMode (like returned by the functions times,
atLeastOnce, etc).

For now we just take either an integer, or an integer followed by '+'. It's not extensible.

#### Callback instead of answers

In Mockito, you can return dynamic results from a stubbed method by calling thenAnswer with an instance of an object
that has a specific method. In Phockito you call thenCallback with a `callback` argument, which gets called with the
arguments the stubbed method was called with.

#### Default arguments

PHP has default arguments, unlike Java. If you don't specify a default argument in your stub or verify matcher, it'll
match the default argument.

```php
class Foo {
  function Bar($a, $b = 2){ /* NOP */ }
}

// Create the mock
$mock = Phockito::mock('Foo');

// Set up a stub
Phockito::when($mock->Bar(1))->return('A');

$mock->Bar(1); // Returns 'A'
$mock->Bar(1, 2); // Also returns 'A'
$mock->Bar(1, 3); // Returns null, since no stubbed return value matches
```

#### Return typing

Mockito returns a type-compatible false, based on the declared return type. We don't have defined type values in
PHP, so we always return null. TODO: Support using phpdoc @return when declared.

## TODO

 - Mockito-specific hamcrest matchers (anyString, etc)
 - Ordered verification

## License

Copyright (C) 2012 Hamish Friedlander / SilverStripe. 

Distributable under either the same license as SilverStripe or the 
Apache Public License V2 (http://www.apache.org/licenses/LICENSE-2.0.html) at your choice

You don’t have to do anything special to choose  one license or the other and you don’t 
have to notify anyone which license you are using.

Hamcrest-php is under it's own license - see hamcrest-php/LICENSE.txt.
