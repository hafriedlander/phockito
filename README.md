# Pokito - Mockito for PHP

Mocking framework based on Mockito for Java

Checkout [the original's website](http://mockito.org/) for the philosophy behind the API and more examples
(although be aware that this is only a partial implementation for now)

## Example mocking:

```php
// Create the mock
$iterator = Pokito::mock('ArrayIterator);

// Use the mock object - doesn't do anything, functions return null
$iterator->append('Test');
$iterator->asort();

// Selectively verify execution
Pokito::verify($iterator)->append('Test');
// 1 is default - can also do 2, 3  for exact numbers, or 1+ for at least one, or 0 for never
Pokito::verify($iterator, 1)->asort();
```

If PHPUnit is available, on failure verify throws a `PHPUnit_Framework_AssertionFailedError` (looks like an assertion failure),
otherwise just throws an `Exception`

## Example stubbing:

```php
// Create the mock
$iterator = Pokito::mock('ArrayIterator);

// Stub in a value
Pokito::when($iterator->offsetGet(0))->return('first');

// Prints "first"
print_r($iterator->offsetGet(0));

// Prints null, because get(999) not stubbed
print_r($iterator->offsetGet(999));
```

### TODO

 - Spies
 - Argument matchers (anyString, etc)
 - Ordered verification
 - Answers (dynamic responses for stubs)
 - doXXX stubbing

### License

Copyright (C) 2011 Hamish Friedlander / SilverStripe. Distributable under the same license as SilverStripe.

