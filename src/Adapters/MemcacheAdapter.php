<?php

namespace Behance\NBD\Cache\Adapters;

use Behance\NBD\Cache\Interfaces\CacheAdapterInterface;

class MemcacheAdapter implements CacheAdapterInterface {

  /**
   * @var \Memcache
   */
  private $_connection;


  /**
   * @param Memcache $instance
   */
  public function __construct( \Memcache $instance = null ) {

    $this->_connection = $instance ?: new \Memcache();

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
   * @see http://www.php.net/manual/en/memcache.get.php
   */
  public function get( $key ) {

    return $this->_connection->get( $key );

  } // get


  /**
   * {@inheritDoc}
   *
   * @see http://www.php.net/manual/en/memcache.get.php
   */
  public function getMulti( array $keys ) {

    $results = $this->_connection->get( $keys );

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
   * @see http://www.php.net/manual/en/memcache.set.php
   */
  public function set( $key, $value, $ttl = CacheAdapterInterface::EXPIRATION_DEFAULT ) {

    return $this->_connection->set( $key, $value, null, $ttl );

  } // set


  /**
   * {@inheritDoc}
   *
   * @see http://www.php.net/manual/en/memcache.add.php
   */
  public function add( $key, $value, $ttl = CacheAdapterInterface::EXPIRATION_DEFAULT ) {

    return $this->_connection->add( $key, $value, null, $ttl );

  } // add


  /**
   * {@inheritDoc}
   *
   * @see http://www.php.net/manual/en/memcache.replace.php
   */
  public function replace( $key, $value, $ttl = CacheAdapterInterface::EXPIRATION_DEFAULT ) {

    return $this->_connection->replace( $key, $value, null, $ttl );

  } // replace


  /**
   * {@inheritDoc}
   *
   * @see http://www.php.net/manual/en/memcache.increment.php
   */
  public function increment( $key, $value = 1 ) {

    return $this->_connection->increment( $key, $value );

  } // increment


  /**
   * {@inheritDoc}
   *
   * @see http://www.php.net/manual/en/memcache.decrement.php
   */
  public function decrement( $key, $value = 1 ) {

    return $this->_connection->decrement( $key, $value );

  } // decrement


  /**
   * {@inheritDoc}
   *
   * @see http://www.php.net/manual/en/memcache.delete.php
   */
  public function delete( $key ) {

    return $this->_connection->delete( $key );

  } // delete


  /**
   * Simulates multiDelete operation, since Memcache extension does not support
   *
   * @param string[] $keys
   *
   * @return bool
   */
  public function deleteMulti( array $keys ) {

    foreach ( $keys as $key ) {
      $this->_connection->delete( $key );
    }

    return true;

  } // deleteMulti


  /**
   * {@inheritDoc}
   */
  public function close() {

    return $this->_connection->close();

  } // close


  /**
   * Ensures connection is removed in the case of garbage collection
   */
  public function __destruct() {

    $this->close();

  } // __destruct

} // MemcacheAdapter
