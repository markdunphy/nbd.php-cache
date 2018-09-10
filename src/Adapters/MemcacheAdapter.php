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

class MemcacheAdapter extends AdapterAbstract {

  /**
   * @see http://php.net/manual/en/memcache.addserver.php
   */
  const DEFAULT_PERSISTENT = true;
  const DEFAULT_WEIGHT = 1;
  const DEFAULT_TIMEOUT_SECS = 1;
  const DEFAULT_SERVER_STATUS = true;
  const DEFAULT_RETRY_INTERVAL_SECS = 15;
  const DEFAULT_FAILURE_REASON = 'Node failure';

  const STAT_KEY_SLABS = 'slabs';
  const STAT_KEY_ITEMS = 'items';
  const STAT_KEY_DUMP = 'cachedump';


  /**
   * @var \Memcache
   */
  private $_connection;

  /**
   * A broken ->get() interface that noone in the community can appear to resolve
   * forces our hand to work around it.
   *
   * @var int  number of parameters that get takes, which appears to be php version dependent
   * @link https://github.com/websupport-sk/pecl-memcache/issues/7
   */
  private $_memcache_get_requires_filler;


  /**
   * @throws Behance\NBD\Cache\Exceptions\SystemRequirementException  when memcache extension is not loaded
   *
   * @param Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   * @param Memcache $instance
   */
  public function __construct(EventDispatcherInterface $event_dispatcher = null, \Memcache $instance = null) {

    $this->_connection = $instance ?: new \Memcache();

    // Really unfortunate to have to do this
    $this->_memcache_get_requires_filler = (new \ReflectionMethod('Memcache', 'get'))->getNumberOfParameters() > 1;

    parent::__construct($event_dispatcher);

  }


  /**
   * {@inheritDoc}
   */
  public function addServer($host, $port, $weight = self::DEFAULT_WEIGHT) {

    $failure_callback = (function ($hostname, $port) {
      $this->_handleFailure(self::DEFAULT_FAILURE_REASON, $hostname, $port);
    });

    $persist = self::DEFAULT_PERSISTENT;
    $timeout = self::DEFAULT_TIMEOUT_SECS;
    $retry = self::DEFAULT_RETRY_INTERVAL_SECS;
    $status = self::DEFAULT_SERVER_STATUS;

    $this->_connection->addServer($host, $port, $persist, $weight, $timeout, $retry, $status, $failure_callback);

  }


  /**
   * {@inheritDoc}
   */
  public function addServers(array $servers) {

    foreach ($servers as $server) {

      $weight = isset($server['weight']) ?: self::DEFAULT_WEIGHT;

      $this->addServer($server['host'], $server['port'], $weight);

    }

  }


  /**
   * {@inheritDoc}
   *
   * @see http://www.php.net/manual/en/memcache.get.php
   */
  protected function _get($key) {

    $flags = null;  // Passed by reference
    $filler = false; // Passed by reference, undocumented complaint in PHP7 without

    return ($this->_memcache_get_requires_filler)
           ? $this->_connection->get($key, $flags, $filler)
           : $this->_connection->get($key);

  }


  /**
   * {@inheritDoc}
   *
   * @see http://www.php.net/manual/en/memcache.get.php
   */
  protected function _getMulti(array $keys) {

    $data = $this->_get($keys);

    // All keys at least come back defined (as null), and in the requested order
    foreach ($keys as $key) {

      if (!isset($data[$key])) {
        $data[$key] = null;
      }

    }

    return $data;

  }


  /**
   * {@inheritDoc}
   *
   * @see http://www.php.net/manual/en/memcache.set.php
   */
  protected function _set($key, $value, $ttl) {

    return $this->_connection->set($key, $value, null, $ttl);

  }


  /**
   * {@inheritDoc}
   *
   * @see http://www.php.net/manual/en/memcache.add.php
   */
  protected function _add($key, $value, $ttl) {

    return $this->_connection->add($key, $value, null, $ttl);

  }


  /**
   * {@inheritDoc}
   *
   * @see http://www.php.net/manual/en/memcache.replace.php
   */
  protected function _replace($key, $value, $ttl) {

    return $this->_connection->replace($key, $value, null, $ttl);

  }


  /**
   * {@inheritDoc}
   *
   * @see http://www.php.net/manual/en/memcache.increment.php
   */
  protected function _increment($key, $value) {

    return $this->_connection->increment($key, $value);

  }


  /**
   * {@inheritDoc}
   *
   * @see http://www.php.net/manual/en/memcache.decrement.php
   */
  protected function _decrement($key, $value) {

    return $this->_connection->decrement($key, $value);

  }


  /**
   * {@inheritDoc}
   *
   * @see http://www.php.net/manual/en/memcache.delete.php
   */
  protected function _delete($key) {

    return $this->_connection->delete($key);

  }


  /**
   * Simulates multiDelete operation, since Memcache extension does not support
   *
   * {@inheritDoc}
   *
   * @see http://www.php.net/manual/en/memcache.delete.php
   */
  protected function _deleteMulti(array $keys) {

    foreach ($keys as $key) {
      $this->_connection->delete($key);
    }

    return true;

  }


  /**
   * {@inheritDoc}
   *
   * @see http://php.net/manual/en/memcache.flush.php
   */
  protected function _flush() {

    return $this->_connection->flush();

  }


  /**
   * When supported, retrieves a list of all keys being held in pool
   *
   * @param bool $on_reload
   *      In the event of a cache flush, memcache does not actually write a different expiration, simply marks all blocks as invalid. This
   *      unfortunately is not visible when listing all keys, and makes it seem like the flush didn't work.
   *      Use this flag DURING a flush to cause a ->get request to be performed against each key it can find, causing it to remove itself, instead
   *      of doing an individual ->get on each key during a list
   *
   * @return array  indexes are cache keys, values are their age
   */
  protected function _getAllKeys() {

    $results = [];
    $connection = $this->_connection;
    $server_slabs = $connection->getExtendedStats(self::STAT_KEY_SLABS);

    if (empty($server_slabs)) {
      return $results;
    }

    foreach (array_values($server_slabs) as $slabs) {

      $slab_ids = array_keys($slabs);

      foreach ($slab_ids as $slab_id) {

        if (!is_integer($slab_id)) {
          continue;
        }

        $cache_dump = $connection->getExtendedStats(self::STAT_KEY_DUMP, $slab_id);

        foreach (array_values($cache_dump) as $entries) {

          if (!is_array($entries)) {
            continue;
          }

          foreach ($entries as $key_name => $key_data) {

            if (!is_array($key_data)) {
              continue;
            }

            $results[] = $key_name;

          }

        }

      }

    }

    return $results;

  }


  /**
   * @return array
   */
  protected function _getStats() {

    return $this->_connection->getStats();

  }


  /**
   * {@inheritDoc}
   */
  protected function _close() {

    return $this->_connection->close();

  }

}
