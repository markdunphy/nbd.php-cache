<?php

namespace Behance\NBD\Cache\Interfaces;

interface CacheAdapterInterface {

  const EXPIRATION_DEFAULT = 1209600; // Two weeks in seconds

  const EVENT_QUERY_BEFORE = 'cache.query.before';
  const EVENT_QUERY_AFTER  = 'cache.query.after';
  const EVENT_QUERY_FAIL   = 'cache.query.fail';


  /**
   * @param string $host
   * @param int    $port
   */
  public function addServer( $host, $port );


  /**
   * @param array $servers
   */
  public function addServers( array $servers );


  /**
   * @param string $key
   *
   * @return mixed|bool  false when key is missing
   */
  public function get( $key );


  /**
   * @param array $keys
   *
   * @return array  key indexes present, but with false as value when not available
   */
  public function getMulti( array $keys );


  /**
   * @param string $key
   * @param mixed  $value
   * @param int    $ttl
   *
   * @return bool
   */
  public function set( $key, $value, $ttl = self::EXPIRATION_DEFAULT );


  /**
   * @param string $key
   * @param mixed  $value
   * @param int    $ttl
   *
   * @return bool
   */
  public function add( $key, $value, $ttl = self::EXPIRATION_DEFAULT );


  /**
   * @param string $key
   * @param mixed  $value
   * @param int    $ttl
   *
   * @return bool
   */
  public function replace( $key, $value, $ttl = self::EXPIRATION_DEFAULT );


  /**
   * @param string $key
   * @param int    $value
   *
   * @return bool
   */
  public function increment( $key, $value = 1 );


  /**
   * @param string $key
   * @param int    $value
   *
   * @return bool
   */
  public function decrement( $key, $value = 1 );


  /**
   * @param string $key
   *
   * @return bool
   */
  public function delete( $key );


  /**
   * @param array $keys
   *
   * @return bool
   */
  public function deleteMulti( array $keys );


  /**
   * Similar to a database transaction, when buffering, cache will not be altered visible
   * to other connections until ->commitBuffer()
   *
   * @throws Behance\NBD\Cache\Exceptions\DuplicateActionException  when called while already buffering
   */
  public function beginBuffer();


  /**
   * Processed any buffered actions so they may be seen by other connections
   */
  public function commitBuffer();


  /**
   * Cancels any mutable actions that took place during the buffering period
   */
  public function rollbackBuffer();


  /**
   * Whether or not connection is buffering
   *
   * @return bool
   */
  public function isBuffering();


  /**
   * Invalidates the entire contents of a cache pool
   *
   * @return bool
   */
  public function flush();


  /**
   * Retrieves the first 1MB of keys, only should be used during development
   *
   * @return array
   */
  public function getAllKeys();


  /**
   * @return array
   */
  public function getStats();


  /**
   * @param string   $event_name
   * @param callable $handler
   */
  public function bindEvent( $event_name, callable $handler );


  /**
   * Disconnects from server(s)
   */
  public function close();

} // CacheAdapterInterface
