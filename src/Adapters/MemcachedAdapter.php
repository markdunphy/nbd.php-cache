<?php

namespace Behance\NBD\Cache\Adapters;

use Behance\NBD\Cache\Interfaces\CacheAdapterInterface;

class MemcachedAdapter implements CacheAdapterInterface {

  /**
   * @var \Memcached
   */
  private $_connection;


  /**
   * @param Memcached $instance
   */
  public function __construct( \Memcached $instance = null ) {

    $this->_connection = $instance ?: new \Memcached();

  } // __construct


  /**
   * {@inheritDoc}
   */
  public function addServer( $host, $port ) {

    $this->_connection->addServer( $host, $port );

  } // addServer


  /**
   * {@inheritDoc}
   *
   * @see http://php.net/manual/en/memcached.get.php
   */
  public function get( $key ) {

    return $this->_connection->get( $key );

  } // get


  /**
   * {@inheritDoc}
   *
   * @see http://php.net/manual/en/memcached.getMulti.php
   */
  public function getMulti( array $keys ) {

    $results = $this->_connection->getMulti( $keys );

    // All keys at least come back with defined, and in the requested order
    foreach ( $keys as $key ) {

      $results[ $key ] = ( isset( $results[ $key ] ) )
                         ? $results[ $key ]
                         : false;
    } // foreach keys

    return $results;

  } // getMulti


  /**
   * {@inheritDoc}
   *
   * @see http://php.net/manual/en/memcached.set.php
   */
  public function set( $key, $value, $ttl = CacheAdapterInterface::EXPIRATION_DEFAULT ) {

    return $this->_connection->set( $key, $value, $ttl );

  } // set


  /**
   * {@inheritDoc}
   *
   * @see http://php.net/manual/en/memcached.add.php
   */
  public function add( $key, $value, $ttl = CacheAdapterInterface::EXPIRATION_DEFAULT ) {

    return $this->_connection->add( $key, $value, $ttl );

  } // add


  /**
   * {@inheritDoc}
   *
   * @see http://php.net/manual/en/memcached.replace.php
   */
  public function replace( $key, $value, $ttl = CacheAdapterInterface::EXPIRATION_DEFAULT ) {

    return $this->_connection->replace( $key, $value, $ttl );

  } // replace


  /**
   * {@inheritDoc}
   *
   * @see http://php.net/manual/en/memcached.increment.php
   */
  public function increment( $key, $value = 1 ) {

    return $this->_connection->increment( $key, $value );

  } // increment


  /**
   * {@inheritDoc}
   *
   * @see http://php.net/manual/en/memcached.decrement.php
   */
  public function decrement( $key, $value = 1 ) {

    return $this->_connection->decrement( $key, $value );

  } // decrement


  /**
   * {@inheritDoc}
   *
   * @see http://php.net/manual/en/memcached.delete.php
   */
  public function delete( $key ) {

    return $this->_connection->delete( $key );

  } // delete


  /**
   * {@inheritDoc}
   *
   * @see http://php.net/manual/en/memcached.deleteMulti.php
   */
  public function deleteMulti( array $keys ) {

    return $this->_connection->deleteMulti( $keys );

  } // deleteMulti


  /**
   * Closes connection to server(s)
   */
  public function close() {

    return $this->_connection->quit();

  } // close


  /**
   * Ensures connection is removed in the case of garbage collection
   */
  public function __destruct() {

    $this->close();

  } // __destruct

} // MemcachedAdapter
