<?php

namespace Behance\NBD\Cache\Adapters;

use Behance\NBD\Cache\AdapterAbstract;
use Behance\NBD\Cache\Exceptions\SystemRequirementException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class MemcachedAdapter extends AdapterAbstract
{

  /**
   * @var \Memcached
   */
    private $_connection;

  /**
   * A clone of the functionality from laravel port
   *
   * @var bool  whether or not to use the breaking memcached 3.0 interface
   * @link https://github.com/laravel/framework/pull/15739/files
   */
    private $_memcached_version_3_0;


  /**
   * @param Symfony\Component\EventDispatcher\EventDispatcherInterface
   * @param Memcached $instance
   */
    public function __construct(EventDispatcherInterface $event_dispatcher = null, \Memcached $instance = null)
    {

        $this->_connection = $instance ?: new \Memcached();

        // IMPORTANT: Unfortunately, there is a breaking change in the 3.0 version of Memcached driver...handle it
        $this->_memcached_version_3_0 = (new \ReflectionMethod('Memcached', 'getMulti'))->getNumberOfParameters() == 2;

        parent::__construct($event_dispatcher);
    } // __construct


  /**
   * {@inheritDoc}
   */
    public function addServer($host, $port)
    {

        $this->_connection->addServer($host, $port);
    } // addServer


  /**
   * {@inheritDoc}
   */
    public function addServers(array $servers)
    {

        $configs = [];

        foreach ($servers as $server) {
            $configs[] = [ $server['host'], $server['port'] ];
        }

        $this->_connection->addServers($configs);
    } // addServers


  /**
   * {@inheritDoc}
   *
   * @see http://php.net/manual/en/memcached.get.php
   */
    protected function _get($key)
    {

        return $this->_connection->get($key);
    } // _get


  /**
   * {@inheritDoc}
   *
   * @see http://php.net/manual/en/memcached.getMulti.php
   */
    protected function _getMulti(array $keys)
    {

        $null = null;

        return ( $this->_memcached_version_3_0 )
           ? $this->_connection->getMulti($keys, \Memcached::GET_PRESERVE_ORDER)
           : $this->_connection->getMulti($keys, $null, \Memcached::GET_PRESERVE_ORDER);
    } // _getMulti


  /**
   * {@inheritDoc}
   */
    protected function _execute(\Closure $action, $operation, $key_or_keys, $mutable = false, $value = null)
    {

        $result = parent::_execute($action, $operation, $key_or_keys, $mutable, $value);

        // Adapter connection itself will only report correctly when not currently buffering results
        if (!$this->isBuffering()) {
            $code = $this->_connection->getResultCode();

            if ($code !== \Memcached::RES_SUCCESS) {
                $this->_handleFailure($this->_connection->getResultMessage(), null, null, $code);
            }
        } // if !isBuffering

        return $result;
    } // _execute


  /**
   * {@inheritDoc}
   *
   * @see http://php.net/manual/en/memcached.set.php
   */
    protected function _set($key, $value, $ttl)
    {

        return $this->_connection->set($key, $value, $ttl);
    } // _set


  /**
   * {@inheritDoc}
   *
   * @see http://php.net/manual/en/memcached.add.php
   */
    protected function _add($key, $value, $ttl)
    {

        return $this->_connection->add($key, $value, $ttl);
    } // _add


  /**
   * {@inheritDoc}
   *
   * @see http://php.net/manual/en/memcached.replace.php
   */
    protected function _replace($key, $value, $ttl)
    {

        return $this->_connection->replace($key, $value, $ttl);
    } // _replace


  /**
   * {@inheritDoc}
   *
   * @see http://php.net/manual/en/memcached.increment.php
   */
    protected function _increment($key, $value)
    {

        return $this->_connection->increment($key, $value);
    } // _increment


  /**
   * {@inheritDoc}
   *
   * @see http://php.net/manual/en/memcached.decrement.php
   */
    protected function _decrement($key, $value)
    {

        return $this->_connection->decrement($key, $value);
    } // _decrement


  /**
   * {@inheritDoc}
   *
   * @see http://php.net/manual/en/memcached.delete.php
   */
    protected function _delete($key)
    {

        return $this->_connection->delete($key);
    } // _delete


  /**
   * {@inheritDoc}
   *
   * @see http://php.net/manual/en/memcached.deleteMulti.php
   */
    protected function _deleteMulti(array $keys)
    {

        return $this->_connection->deleteMulti($keys);
    } // _deleteMulti


  /**
   * {@inheritDoc}
   *
   * @see http://php.net/manual/en/memcache.flush.php
   */
    protected function _flush()
    {

        return $this->_connection->flush();
    } // _flush


  /**
   * {@inheritDoc}
   *
   * @see http://php.net/manual/en/memcached.getallkeys.php
   */
    protected function _getAllKeys()
    {

        return $this->_connection->getAllKeys();
    } // _getAllKeys


  /**
   * {@inheritDoc}
   *
   * @see http://php.net/manual/en/memcache.getstats.php
   */
    protected function _getStats($type = 'items')
    {

        return $this->_connection->getStats($type);
    } // _getStats


  /**
   * Closes connection to server(s)
   */
    protected function _close()
    {

        return $this->_connection->quit();
    } // _close
} // MemcachedAdapter
