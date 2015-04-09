<?php

namespace Behance\NBD\Cache\Adapters;

use Behance\NBD\Cache\Adapters\AdapterAbstract;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class MemcacheAdapter extends AdapterAbstract {

  /**
   * @see http://php.net/manual/en/memcache.addserver.php
   */
  const DEFAULT_PERSISTENT          = true;
  const DEFAULT_WEIGHT              = 10;
  const DEFAULT_TIMEOUT_SECS        = 1;
  const DEFAULT_SERVER_STATUS       = true;
  const DEFAULT_RETRY_INTERVAL_SECS = 15;
  const DEFAULT_FAILURE_REASON      = 'Node failure';


  /**
   * @var \Memcache
   */
  private $_connection;


  /**
   * @param Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   * @param Memcache $instance
   */
  public function __construct( EventDispatcherInterface $event_dispatcher = null, \Memcache $instance = null ) {

    $this->_connection = $instance ?: new \Memcache();

    parent::__construct( $event_dispatcher );

  } // __construct


  /**
   * {@inheritDoc}
   */
  public function addServer( $host, $port, $weight = self::DEFAULT_WEIGHT ) {

    $failure_callback = ( function( $hostname, $port ) {
      $this->_handleFailure( self::DEFAULT_FAILURE_REASON, $hostname, $port );
    } );

    $persist = self::DEFAULT_PERSISTENT;
    $timeout = self::DEFAULT_TIMEOUT_SECS;
    $retry   = self::DEFAULT_RETRY_INTERVAL_SECS;
    $status  = self::DEFAULT_SERVER_STATUS;

    $this->_connection->addServer( $host, $port, $persist, $weight, $timeout, $retry, $status, $failure_callback );

  } // addServer


  /**
   * {@inheritDoc}
   */
  public function addServers( array $servers ) {

    foreach ( $servers as $server ) {

      $weight = isset( $server['weight'] ) ?: self::DEFAULT_WEIGHT;

      $this->addServer( $server['host'], $server['port'], $weight );

    } // foreach servers

  } // addServers


  /**
   * {@inheritDoc}
   *
   * @see http://www.php.net/manual/en/memcache.get.php
   */
  protected function _get( $key ) {

    return $this->_connection->get( $key );

  } // _get


  /**
   * {@inheritDoc}
   *
   * @see http://www.php.net/manual/en/memcache.get.php
   */
  protected function _getMulti( array $keys ) {

    $data = $this->_connection->get( $keys );

    // All keys at least come back defined (as null), and in the requested order
    foreach ( $keys as $key ) {

      if ( !isset( $data[ $key ] ) ) {
        $data[ $key ] = null;
      }

    } // foreach keys

    return $data;

  } // _getMulti


  /**
   * {@inheritDoc}
   *
   * @see http://www.php.net/manual/en/memcache.set.php
   */
  protected function _set( $key, $value, $ttl ) {

    return $this->_connection->set( $key, $value, null, $ttl );

  } // _set


  /**
   * {@inheritDoc}
   *
   * @see http://www.php.net/manual/en/memcache.add.php
   */
  protected function _add( $key, $value, $ttl ) {

    return $this->_connection->add( $key, $value, null, $ttl );

  } // _add


  /**
   * {@inheritDoc}
   *
   * @see http://www.php.net/manual/en/memcache.replace.php
   */
  protected function _replace( $key, $value, $ttl ) {

    return $this->_connection->replace( $key, $value, null, $ttl );

  } // _replace


  /**
   * {@inheritDoc}
   *
   * @see http://www.php.net/manual/en/memcache.increment.php
   */
  protected function _increment( $key, $value ) {

    return $this->_connection->increment( $key, $value );

  } // _increment


  /**
   * {@inheritDoc}
   *
   * @see http://www.php.net/manual/en/memcache.decrement.php
   */
  protected function _decrement( $key, $value = 1 ) {

    return $this->_connection->decrement( $key, $value );

  } // _decrement


  /**
   * {@inheritDoc}
   *
   * @see http://www.php.net/manual/en/memcache.delete.php
   */
  protected function _delete( $key ) {

    return $this->_connection->delete( $key );

  } // _delete


  /**
   * Simulates multiDelete operation, since Memcache extension does not support
   *
   * {@inheritDoc}
   *
   * @see http://www.php.net/manual/en/memcache.delete.php
   */
  protected function _deleteMulti( array $keys ) {

    foreach ( $keys as $key ) {
      $this->_connection->delete( $key );
    }

    return true;

  } // _deleteMulti


  /**
   * {@inheritDoc}
   */
  protected function _close() {

    return $this->_connection->close();

  } // _close

} // MemcacheAdapter
