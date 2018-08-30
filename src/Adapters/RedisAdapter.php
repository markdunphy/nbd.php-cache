<?php

/*************************************************************************
 * ADOBE CONFIDENTIAL
 * ___________________
 *
 * Copyright 2018 Adobe
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Adobe and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Adobe
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Dissemination of this information or reproduction of this material
 * is strictly forbidden unless prior written permission is obtained
 * from Adobe.
 *************************************************************************/


namespace Behance\NBD\Cache\Adapters;

use Behance\NBD\Cache\AdapterAbstract;
use Behance\NBD\Cache\Exceptions\SystemRequirementException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class RedisAdapter extends AdapterAbstract {

  // Since memcache has a TTL setting for unlimited or permanent keys, and Redis
  // uses a different mechanism for this, swap "permanent" for a pseudo max length
  // Otherwise, Redis will *not* cache this value

  const PSEUDO_MAX = 2592000; // 30 days in seconds


  /**
   * @var \Redis
   */
  private $_connection;


  /**
   * @param Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   * @param Redis $instance
   */
  public function __construct(EventDispatcherInterface $event_dispatcher = null, \Redis $instance = null) {

    $this->_connection = $instance ?: new \Redis();

    parent::__construct($event_dispatcher);

  }


  /**
   * {@inheritDoc}
   *
   * @link https://github.com/phpredis/phpredis#pconnect-popen
   */
  public function addServer($host, $port) {

    try {
      @$this->_connection->pconnect($host, $port);
    } catch(\RedisException $e) {
      // TODO: since memcache/memcached adapters do not throw exceptions because
      // their connection are established lazily, this exception must unfortunately need to be swallowed
    }

  }


  /**
   * {@inheritDoc}
   */
  public function addServers(array $servers) {

    foreach ($servers as $server) {
      $this->addServer($server['host'], $server['port']);
    }

    // TODO: custom options functionality needs to be implemented
    // This is only a baseline to prevent data structures from flattening/corrupting
    // (!) Cannot be moved to the constructor, as this requires servers to already be added

    // Depressing: https://github.com/phpredis/phpredis/issues/504
    try {
      $this->_connection->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_PHP);
    } catch(\RedisException $e) {
      // TODO: since memcache/memcached adapters do not throw exceptions because
      // their connection are established lazily, this exception must unfortunately need to be swallowed
    }

  }


  /**
   * {@inheritDoc}
   *
   * @link https://github.com/phpredis/phpredis#get
   */
  protected function _get($key) {

    return $this->_connection->get($key);

  }


  /**
   * {@inheritDoc}
   *
   * @link https://github.com/phpredis/phpredis#mget-getmultiple
   */
  protected function _getMulti(array $keys) {

    $values = $this->_connection->getMultiple($keys);

    if (empty($values)) {
      return array_fill_keys($keys, false);
    }

    // Note: returns an array of *only* values, keys need to be merged
    return array_combine($keys, $values);

  }


  /**
   * {@inheritDoc}
   *
   * @link https://github.com/phpredis/phpredis#setex-psetex
   */
  protected function _set($key, $value, $ttl) {

    $ttl = $this->_validateTtl($ttl);

    // NOTE: position of 2nd argument in Redis object is different than interface
    return $this->_connection->setEx($key, $ttl, $value);

  }


  /**
   * {@inheritDoc}
   *
   * @link https://github.com/phpredis/phpredis#set
   */
  protected function _add($key, $value, $ttl) {

    $ttl = $this->_validateTtl($ttl);

    // Using extended options to supply a TTL (in secs), and also only set if does NOT exit (nx)
    return $this->_connection->set($key, $value, ['nx', 'ex' => $ttl]);

  }


  /**
   * {@inheritDoc}
   *
   * @link https://github.com/phpredis/phpredis#set
   */
  protected function _replace($key, $value, $ttl) {

    $ttl = $this->_validateTtl($ttl);

    // Using extended options to supply a TTL (in secs), and also only set if DOES exit (xx)
    return $this->_connection->set($key, $value, ['xx', 'ex' => $ttl]);

  }


  /**
   * {@inheritDoc}
   *
   * @link https://github.com/phpredis/phpredis#incr-incrby
   */
  protected function _increment($key, $value) {

    return $this->_connection->incrBy($key, $value);

  }


  /**
   * {@inheritDoc}
   *
   * @link https://github.com/phpredis/phpredis#decr-decrby
   */
  protected function _decrement($key, $value) {

    return $this->_connection->decrBy($key, $value);

  }


  /**
   * {@inheritDoc}
   *
   * @link https://github.com/phpredis/phpredis#del-delete
   */
  protected function _delete($key) {

    return (bool)$this->_connection->delete($key);

  }


  /**
   * {@inheritDoc}
   *
   * @link https://github.com/phpredis/phpredis#del-delete
   */
  protected function _deleteMulti(array $keys) {

    return $this->_connection->delete($keys);

  }


  /**
   * {@inheritDoc}
   *
   * @link https://github.com/phpredis/phpredis#flushdb
   */
  protected function _flush() {

    // Deliberately *not* flushing all keys from all databases, just in case.
    return $this->_connection->flushDb();

  }


  /**
   * {@inheritDoc}
   *
   * @link https://github.com/phpredis/phpredis#class-redisexception
   */
  protected function _execute(\Closure $action, $operation, $key_or_keys, $mutable = false, $value = null) {

    // Redis may throw an exception for any connectivity error, unlike memcache and memcached. Suppress exception, log, and return false
    $protected_action = (function () use ($action) {

      try {
        return $action();
      } catch(\RedisException $e) {
        $this->_handleFailure($e->getMessage(), null, null, $e->getCode());
        return false;
      }

    }); // protected_action

    return parent::_execute($protected_action, $operation, $key_or_keys, $mutable, $value);

  }


  /**
   * {@inheritDoc}
   *
   * @link https://github.com/phpredis/phpredis#scan
   */
  protected function _getAllKeys() {

    $iterator = null;
    $keys = [];

    // Retry when we get no keys back
    $this->_connection->setOption(\Redis::OPT_SCAN, \Redis::SCAN_RETRY);

    while ($scanned_keys = $this->_connection->scan($iterator)) {
      $keys += $scanned_keys;
    }

    return $keys;

  }


  /**
   * {@inheritDoc}
   *
   * @link https://github.com/phpredis/phpredis#info
   *
   * Does some minor transformation to ensure a baseline of info between Memcache and Redis
   */
  protected function _getStats() {

    $info = $this->_connection->info();

    // Ensure info keyspace is populated before returning
    $defaults = [
      'keyspace_hits' => 0,
      'keyspace_misses' => 0,
      'evicted_keys' => 0,
      'maxmemory' => 0,
      'process_id' => 0,
      'uptime_in_seconds' => 0,
      'lru_clock' => 0,
      'redis_version' => 0,
      'total_system_memory' => 0,
      'connected_clients' => 0,
      'total_commands_processed' => 0,
      'total_net_input_bytes' => 0,
      'total_net_output_bytes' => 0
    ];

    $info = array_merge($defaults, $info);

    $info['get_hits'] = $info['keyspace_hits'];
    $info['get_misses'] = $info['keyspace_misses'];
    $info['evictions'] = $info['evicted_keys'];
    $info['pointer_size'] = $info['maxmemory'];
    $info['pid'] = $info['process_id'];
    $info['uptime'] = $info['uptime_in_seconds'];

    $info['time'] = $info['lru_clock'];
    $info['version'] = $info['redis_version'];
    $info['bytes'] = 0;
    $info['bytes_read'] = $info['total_net_output_bytes'];
    $info['bytes_written'] = $info['total_net_input_bytes'];
    $info['limit_maxbytes'] = $info['total_system_memory'];
    $info['curr_items'] = 0;
    $info['total_items'] = 0;
    $info['curr_connections'] = $info['connected_clients'];
    $info['total_connections'] = $info['total_commands_processed'];
    $info['cmd_get'] = $info['keyspace_hits'];
    $info['cmd_set'] = 0;


    return $info;

  }


  /**
   * Closes connection to server(s)
   */
  protected function _close() {

    return $this->_connection->close();

  }


  /**
   * Ensures legacy memcache clients using 0 for "permanent" become some arbitrarily large value, like a month
   *
   * @param int $ttl
   *
   * @return int
   */
  private function _validateTtl($ttl) {

    return ($ttl) ?: self::PSEUDO_MAX;

  }

}
