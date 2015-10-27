[![Build Status](https://travis-ci.org/behance/nbd.php-cache.svg?branch=master)](https://travis-ci.org/behance/nbd.php-cache)
[![Dependency Status](https://www.versioneye.com/user/projects/55302e6210e71490660008fd/badge.svg?style=flat)](https://www.versioneye.com/user/projects/55302e6210e71490660008fd)

# behance/nbd.php-cache
Provides basis for communicating with memcache servers, abstracts away interface differences
between [Memcache](https://pecl.php.net/package/memcached) and [Memcached](https://pecl.php.net/package/memcached) PECL extensions

### Goals
---

1. Very minimal dependencies, to be used in very diverse environments
2. Provide flexibility for using `Memcache` vs. `Memcached` PECL extensions
3. Make every attempt to shield connection and management logic from implementer
4. Support limited cache "transaction" functionality: Just like an ACID DB transaction, reads + writes only visible single process until committed. Helpful for embedded cache processes that follow actual DB transactions.
5. Provide deep introspection with events


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

// Creates an adapter based on the presence of memcache/memcached extensions
$cache = Cache\Factory::create( $config );

// Retrieve a single value
$cache->get( 'abcdefg' );

// Retrieve multiple values
$cache->getMulti( [ 'abcdefg', 'hijklmn' ] ); // Result preserves order
```

### Testing
---   
Unit testing: 
1. `composer install`
2. `./vendor/bin/phpunit`

Integration testing: leveraging Docker, using actual mysql container
1. `docker-compose up -d`
2. `docker exec -it nbdphpcache_web_1 /bin/bash`
3. `cd /app`
4. `./vendor/bin/phpunit`

### Operations
---

<table>
<tr><th>Method</th><th>Explanation</th></tr>
<tr><td>get( $key )</td><td>Retrieves value of $key</td></tr>
<tr><td>getMulti( array $keys )</td><td>Will return ordered list with all keys defined, set to null if individual is missing</td></tr>
<tr><td>set( $key, $value, $ttl = CacheAdapterInterface::EXPIRATION_DEFAULT )</td><td>Saves $key to $value</td></tr>
<tr><td>add( $key, $value, $ttl = CacheAdapterInterface::EXPIRATION_DEFAULT )</td><td>Saves $key to $value, ONLY if $key does NOT exist already</td></tr>
<tr><td>replace( $key, $value, $ttl = CacheAdapterInterface::EXPIRATION_DEFAULT )</td><td>Saves $value to $key, ONLY if $key already exists</td></tr>
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
<tr><td>close()</td><td>Disconnects from active connections</td></tr>
</table>
