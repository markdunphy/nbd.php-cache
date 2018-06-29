[![Build Status](https://travis-ci.org/behance/nbd.php-cache.svg?branch=master)](https://travis-ci.org/behance/nbd.php-cache)
[![Dependency Status](https://www.versioneye.com/user/projects/55302e6210e71490660008fd/badge.svg?style=flat)](https://www.versioneye.com/user/projects/55302e6210e71490660008fd)

# behance/nbd.php-cache
Provides basis for communicating with memcache servers, abstracts away interface differences
between [Memcache](https://pecl.php.net/package/memcached), [Memcached](https://pecl.php.net/package/memcached), and [Redis](https://pecl.php.net/package/redis) PECL extensions

### Goals
---

1. Have minimal dependencies, to be used in very diverse environments.
2. Migration tool: flexibly switch between `Memcache`, `Memcached`, and `Redis` PECL extensions using a single interface
  - Automatically detect PECL extensions and leverage them in priority order (Memcached over Memcache over Redis)
3. Make every attempt to shield connection and management logic from implementer
4. Support limited cache "transaction" functionality: Just like an ACID DB transaction, reads + writes only visible single process until committed. Helpful for embedded cache processes that follow actual DB transactions.
5. Provide deep introspection with events


###Implementation Note

- Redis, at time of writing, connects at the [moment](https://github.com/phpredis/phpredis/issues/934) of [configuration](https://github.com/phpredis/phpredis/issues/504). Until lazy instantiation is fully implemented in the released PECL extension (milestone 3.1.0), initial connection errors are sadly swallowed to work similar to memcache/memcached.

###Usage
---

```
use Behance\NBD\Cache;

$config = [
  [
    'host' => 'cache1.com',
    'port' => 11211
  ],
  [
    'host' => 'cache2.com',
    'port' => 11212
  ],
  //[
  //  ... add as many servers as necessary
  //]
];
```

Create an adapter based on the presence of memcache/memcached/redis extensions

```
$cache = Cache\Factory::create( $config );
```

Or, build a instance of a specific type:

```
$cache = Cache\Factory::create( $config, Factory::TYPE_REDIS );
$cache = Cache\Factory::create( $config, Factory::TYPE_MEMCACHE );
$cache = Cache\Factory::create( $config, Factory::TYPE_MEMCACHED );
```

Retrieve a single value

```
$cache->get( 'abcdefg' );
```

Retrieve multiple values

```
$cache->getMulti( [ 'abcdefg', 'hijklmn' ] ); // Result preserves order
```

### Testing
---
Unit testing, requires `memcache`, `memcached`, and `redis` plugins:
1. `composer install`
2. `./vendor/bin/phpunit`

(preferred) Integration testing: leverages docker / docker-compose, using actual service containers for memcache and redis)
1. (on PHP 7.1) `docker-compose build seven && docker-compose run sevenone`
1. (on PHP 7.2) `docker-compose build seven && docker-compose run seventwo`

### Operations
---

<table>
<tr><th>Method</th><th>Explanation</th></tr>
<tr><td>get( $key )</td><td>Retrieves value of $key</td></tr>
<tr><td>getMulti( array $keys )</td><td>Will return ordered list with all keys defined, set to null if individual is missing</td></tr>
<tr><td>set( $key, $value, $ttl = AdapterInterface::EXPIRATION_DEFAULT )</td><td>Saves $key to $value</td></tr>
<tr><td>add( $key, $value, $ttl = AdapterInterface::EXPIRATION_DEFAULT )</td><td>Saves $key to $value, ONLY if $key does NOT exist already</td></tr>
<tr><td>replace( $key, $value, $ttl = AdapterInterface::EXPIRATION_DEFAULT )</td><td>Saves $value to $key, ONLY if $key already exists</td></tr>
<tr><td>increment( $key, $value = 1 )</td><td>Increments $key by $value</td></tr>
<tr><td>decrement( $key, $value = 1 )</td><td>Decrements $key by $value</td></tr>
<tr><td>delete( $key )</td><td>Removes a single key from server</td></tr>
<tr><td>deleteMulti( array $keys )</td><td>Removes group of keys from server(s)</td></tr>

<tr><td>beginBuffer</td><td>Simulates a transaction, provides consistent state for current connection</td></tr>
<tr><td>rollbackBuffer</td><td>Ends transaction, without committing results</td></tr>
<tr><td>commitBuffer</td><td>Ends transaction, commits results</td></tr>

<tr><td>flush()</td><td>Removes all keys from server(s)</td></tr>
<tr><td>getAllKeys()</td><td>Retrieves the full keylist from server(s)</td></tr>
<tr><td>getStats()</td><td>Retrieves usage stats from server(s)</td></tr>
<tr><td>bind( $event_name, callable $handler )</td><td>Provide handlers for cache-specific events</td></tr>
<tr><td>getBoundEvents()</td><td>Gets a list of the events that are bound</td></tr>
<tr><td>close()</td><td>Disconnects from active connections</td></tr>
</table>
