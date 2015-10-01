# Compleeted

A bc breaking fork of [Compleet](https://github.com/etrepat/compleet), itself a PHP port of the awesome [Soulmate](https://github.com/seatgeek/soulmate) ruby gem, which was written by Eric Waller. All credit should go to the original library author and a mention to the author of the PHP derivative.

This library will help you solve the common problem of developing a fast autocomplete feature. It uses Redis's sorted sets to build an index of partially completed words and the corresponding top matching items, and provides a simple interface to query them. Compleeted finishes your sentences.

Compleeted requires [PHP](http://www.php.net) >= 5.6 and its only dependency is [Phpredis](https://github.com/phpredis/phpredis) PHP extension for Redis.

## Getting Started

Compleeted is distributed standalone, so just copy the files to a preferred location and access them using namespacing etc as per usual.

## Usage

Compleeted is designed to be simple and fast, and its main features are:

* Provide suggestions for multiple types of items in a single query.
* Sort results by user-specified score, lexicographically otherwise.
* Store arbitrary metadata for each item. You may store url's, thumbnail paths, etc.

An *item* is a simple JSON object that looks like:

```json
{
  "id": 3,
  "term": "Citi Field",
  "score": 81,
  "data": {
    "url": "/citi-field-tickets/",
    "subtitle": "Flushing, NY"
  }
}
```

Where `id` is a unique identifier (within the specific type), `term` is the phrase you wish to provide completions for, `score` is a user-specified ranking metric (redis will order things lexicographically for items with the same score), and `data` is an optional container for metadata you'd like to return when this item is matched.

### Managing items

Before being able to perform any autocomplete search we must first load some data into our redis database. To feed data into a Redis server instance for later querying, Compleeted provides the `Loader` class:

```php
// I assume you have a PSR suitable autoloader. If not add includes to the files as required (the 'use' list in each is a clue) 
namespace yournamespace;

use Compleeted\Loader;

$loader = new Loader('venues');
```

The constructor parameter is the type of the items we are actually going to add/remove or load. We'll later use this same type for search. This is used so we can add several kinds of completely differentiated data and index it into Redis separately.

By default a `Compleeted\Loader` object will instantiate a connection to a local redis instance as soon as it is needed. If you wish to change this behaviour, you may either provide a connection into the constructor:

```php
// I assume you have a PSR suitable autoloader. If not add includes to the files as required (the 'use' list in each is a clue) 
namespace yournamespace;

use Compleeted\Loader;

$redis = new Redis();

$redis->pconnect(
  '127.0.0.1',
  6379
);

$redis->select(0);

$loader = new Compleeted\Loader('venues', $redis);
```

or use the `setConnection` method:

```php
// I assume you have a PSR suitable autoloader. If not add includes to the files as required (the 'use' list in each is a clue) 
namespace yournamespace;

use Compleeted\Loader;

$redis = new Redis();

$redis->pconnect(
  '127.0.0.1',
  6379
);

$redis->select(0);

$loader = new Compleeted\Loader('venues');
$loader->setConnection($redis);
```

#### Loading items

Now that we have a loader object in place we probably want to use it to load a bunch of items into our redis database. Imagine we have the following PHP item array (which follows the previous JSON structure):

```php
$items = array(
  array(
    'id' => 1,
    'term' => 'Dodger Stadium',
    'score' => 85,
    'data' => array(
      'url' => '/dodger-stadium/tickets',
      'subtitle' => 'Los Angeles, CA'
    )
  ),
  array(
    'id' => 28,
    'term' => 'Angel Stadium',
    'score' => 85,
    'data' => array(
      'url' => '/angel-stadium- tickets/',
      'subtitle' => 'Anaheim, CA'
    )
  )
  ... etc ...
);
```

To load these items into Redis for later querying and previously clearing all existing data we have the `load` method:

```php
$loader->load($items);
```

#### Adding items

We may add a single item in a similar fashion with the `add` method:

```php
$item = array(
  'id' => 30,
  'term' => 'Chase Field',
  'score' => 85,
  'data' => array(
    'url' => '/chase-field-ticket/',
    'subtitle' => 'Phoenix, AZ'
  )
);

$loader->add($item);
```

The `add` method appends items individually without previously clearing the index.

For both the `load` and `add` methods, each item must supply the `id` and `term` array keys. All other attributes are optional.

#### Removing items

Similarly if we need to remove an individual item, the `remove` method will do just that:

```php
$item = array(
  'id' => 30,
  'term' => 'Chase Field',
  'score' => 85,
  'data' => array(
    'url' => '/chase-field-ticket/',
    'subtitle' => 'Phoenix, AZ'
  )
);

$loader->remove($item);
```

Only the `id` key will be used and is actually mandatory.

#### Clearing the index

To wipe out all of the previously indexed data we may use the `clear` method:

```php
$loader->clear();
```

### Querying

Analogous to the `Compleeted\Loader` class, Compleeted provides the `Matcher` class which will allow us to query against previously indexed data. It works pretty similarly and accepts the same constructor arguments:

```php
// I assume you have a PSR suitable autoloader. If not add includes to the files as required (the 'use' list in each is a clue) 
namespace yournamespace;

namespace yournamespace;

use Compleeted\Matcher;

$redis = new Redis();

$redis->pconnect(
  '127.0.0.1',
  6379
);

$redis->select(0);


$matcher = new Compleeted\Matcher('venues', $redis);
// or:
$matcher = new Compleeted\Matcher('venues');
$matcher->setConnection($redis);
```

Again, the first constructor parameter is the type of the items we are actually going query against.

Then, the `matches` method will allow us to query the supplied term against the indexed data:

```php
$results = $matcher->matches('stad');
```

This will perform a search against the index of partially completed words for the `venues` type and it will return an array of matching items for the term `stad`. The resulting array will be sorted by the supplied score or alphabetically if none was given.

The `matches` method also supports passing an array of options as a second argument:

```php
$queryOptions = array(
  // resultset size limit (defaults to 5)
  'limit' => 5,

  // whether to cache the results or not (defaults to true)
  'cache' => true

  // cache expiry time in seconds (defaults to 600)
  'expiry' => 600
);

$results = $matcher->matches('stad', $queryOptions);
```

Setting the `limit` option to `0` will return *all* results which match the provided term.

Matching a single term against multiple types of items should be easy enough:

```php
$types = array('products', 'categories', 'brands');

$results = array();

foreach($types as $type) {
  $matcher = new Matcher($type);
  $results[$type] = $matcher->matches('some term');
}
```

## Working with the CLI

Compleeted does not support te CLI, as Compleet does - haven't a need myself and not gotten to it.

## Using Compleeted with your framework

Compleeted is a standalone package and should work out of the box regardless of the PHP framework you use. In fact, none is needed.

## License

Compleeted is licensed under the terms of the [MIT License](http://opensource.org/licenses/MIT)
(See LICENSE file for details).

---

Conversion Coded by [Kate Sylvia (katesylvia)](katesylvia@code-cubed.com).
