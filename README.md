[![Build Status](https://travis-ci.org/behance/nbd.php-cache.svg?branch=master)](https://travis-ci.org/behance/nbd.php-cache)
[![Dependency Status](https://www.versioneye.com/user/projects/55302e6210e71490660008fd/badge.svg?style=flat)](https://www.versioneye.com/user/projects/55302e6210e71490660008fd)

NBD.php - Cache Component
=========================

Provides basis for communicating with memcache servers, abstracts away interface differences
between [Memcache](https://pecl.php.net/package/memcached) and [Memcached](https://pecl.php.net/package/memcached) PECL extensions

Usage
-----

#####Quickly create adapter and grab key 'abcdefg'


```
use Behance\NBD\Cache\Factory;
use Behance\NBD\Cache\Services\ConfigService;

$config = new ConfigService();
$config->addServer( [ 'host' => 'cache1.com', 'port' => 11211 ] );

$adapter = Factory::create( $config, Factory::TYPE_MEMCACHE ); // Or TYPE_MEMCACHED, if available

$adapter->get( 'abcdefg' );
```


Methods
-----

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
<tr><td>flush()</td><td>Removes all keys from server(s)</td></tr>
<tr><td>getAllKeys()</td><td>Retrieves the full keylist from server(s)</td></tr>
<tr><td>getStats()</td><td>Retrieves usage stats from server(s)</td></tr>
<tr><td>bind( $event_name, callable $handler )</td><td>Provide handlers for cache-specific events</td></tr>
<tr><td>close()</td><td>Disconnects from active connections</td></tr>
</table>


TODO
-----

- Stats calls
- Key listings
