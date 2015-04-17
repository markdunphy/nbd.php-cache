<?php

namespace Behance\NBD\Cache\Adapters;

use Behance\NBD\Cache\Events\QueryEvent;
use Behance\NBD\Cache\Events\QueryFailEvent;

use Behance\NBD\Cache\Interfaces\CacheAdapterInterface;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

abstract class AdapterAbstract implements CacheAdapterInterface {

  /**
   * @var Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $_dispatcher;


  /**
   * @param Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  public function __construct( EventDispatcherInterface $event_dispatcher = null ) {

    $this->_dispatcher = $event_dispatcher;

  } // __construct


  /**
   * {@inheritDoc}
   */
  abstract public function addServer( $host, $port );


  /**
   * {@inheritDoc}
   */
  abstract public function addServers( array $servers );



  /**
   * {@inheritDoc}
   */
  public function get( $key ) {

    $action = ( function() use ( $key ) {
      return $this->_get( $key );
    } );

    return $this->_execute( $action, __FUNCTION__, $key );

  } // get


  /**
   * {@inheritDoc}
   */
  public function getMulti( array $keys ) {

    $action = ( function() use ( $keys ) {
      return $this->_getMulti( $keys );
    } );

    return $this->_execute( $action, __FUNCTION__, $keys );

  } // getMulti


  /**
   * {@inheritDoc}
   */
  public function set( $key, $value, $ttl = CacheAdapterInterface::EXPIRATION_DEFAULT ) {

    $action = ( function() use ( $key, $value, $ttl ) {
      return $this->_set( $key, $value, $ttl );
    } );

    return $this->_execute( $action, __FUNCTION__, $key, true );

  } // set


  /**
   * {@inheritDoc}
   */
  public function add( $key, $value, $ttl = CacheAdapterInterface::EXPIRATION_DEFAULT ) {

    $action = ( function() use ( $key, $value, $ttl ) {
      return $this->_add( $key, $value, $ttl );
    } );

    return $this->_execute( $action, __FUNCTION__, $key, true );

  } // add


  /**
   * {@inheritDoc}
   */
  public function replace( $key, $value, $ttl = CacheAdapterInterface::EXPIRATION_DEFAULT ) {

    $action = ( function() use ( $key, $value, $ttl ) {
      return $this->_replace( $key, $value, $ttl );
    } );

    return $this->_execute( $action, __FUNCTION__, $key, true );

  } // replace


  /**
   * {@inheritDoc}
   *
   * @see http://www.php.net/manual/en/memcache.increment.php
   */
  public function increment( $key, $value = 1 ) {

    $action = ( function() use ( $key, $value ) {
      return $this->_increment( $key, $value );
    } );

    return $this->_execute( $action, __FUNCTION__, $key, true );

  } // increment


  /**
   * {@inheritDoc}
   */
  public function decrement( $key, $value = 1 ) {

    $action = ( function() use ( $key, $value ) {
      return $this->_decrement( $key, $value );
    } );

    return $this->_execute( $action, __FUNCTION__, $key, true );

  } // decrement


  /**
   * {@inheritDoc}
   */
  public function delete( $key ) {

    $action = ( function() use ( $key ) {
      return $this->_delete( $key );
    } );

    return $this->_execute( $action, __FUNCTION__, $key, true );

  } // delete


  /**
   * {@inheritDoc}
   */
  public function deleteMulti( array $keys ) {

    $action = ( function() use ( $keys ) {
      return $this->_deleteMulti( $keys );
    } );

    return $this->_execute( $action, __FUNCTION__, $keys, true );

  } // deleteMulti


  /**
   * @param string   $event_name
   * @param callable $handler
   */
  public function bind( $event_name, callable $handler ) {

    // Build a dispatcher if one doesn't already exist
    if ( !$this->_dispatcher ) {
      $this->_dispatcher = $this->_buildEventDispatcher();
    }

    $this->_dispatcher->addListener( $event_name, $handler );

  } // bind


  /**
   * {@inheritDoc}
   */
  public function close() {

    return $this->_close();

  } // close


  /**
   * Used to wrap every action with a before and after event
   *
   * @param \Closure        $action
   * @param string          $operation
   * @param string|string[] $key_or_keys
   * @param bool            $mutable
   *
   * @return mixed
   */
  protected function _execute( \Closure $action, $operation, $key_or_keys, $mutable = false ) {

    $this->_emitQueryEvent( CacheAdapterInterface::EVENT_QUERY_BEFORE, $operation, $key_or_keys, $mutable );

    $result = $action();

    $this->_emitQueryEvent( CacheAdapterInterface::EVENT_QUERY_AFTER, $operation, $key_or_keys, $mutable );

    return $result;

  } // _execute


  /**
   * Called in the event of a query failure
   *
   * @param string $reason
   * @param string $hostname
   * @param int    $port
   * @param int    $code
   */
  protected function _handleFailure( $reason, $hostname = null, $port = null, $code = null ) {

    if ( !$this->_dispatcher ) {
      return;
    }

    $event = new QueryFailEvent( $reason, $hostname, $port, $code );

    $this->_dispatcher->dispatch( CacheAdapterInterface::EVENT_QUERY_FAIL, $event );

  } // _handleFailure


  /**
   * @param string          $event_name
   * @param string          $operation
   * @param string|string[] $key
   * @param bool            $mutable
   */
  protected function _emitQueryEvent( $event_name, $operation, $key_or_keys, $mutable ) {

    if ( !$this->_dispatcher ) {
      return;
    }

    $event = new QueryEvent( $operation, $key_or_keys, $mutable );

    $this->_dispatcher->dispatch( $event_name, $event );

  } // _emitQueryEvent


  /**
   * @return Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected function _buildEventDispatcher() {

    return new EventDispatcher();

  } // _buildEventDispatcher


  /**
   * @param string $key
   *
   * @return mixed
   */
  abstract protected function _get( $key );


  /**
   * @param array $keys
   *
   * @return mixed
   */
  abstract protected function _getMulti( array $keys );


  /**
   * @param string $key
   * @param string $value
   * @param int    $ttl
   *
   * @return bool
   */
  abstract protected function _set( $key, $value, $ttl );


  /**
   * @param string $key
   * @param string $value
   * @param int    $ttl
   *
   * @return bool
   */
  abstract protected function _add( $key, $value, $ttl );


  /**
   * @param string $key
   * @param string $value
   * @param int    $ttl
   *
   * @return bool
   */
  abstract protected function _replace( $key, $value, $ttl );


  /**
   * @param string $key
   * @param int    $value
   */
  abstract protected function _increment( $key, $value );


  /**
   * @param string $key
   * @param int    $value
   */
  abstract protected function _decrement( $key, $value );


  /**
   * @param string $key
   */
  abstract protected function _delete( $key );


  /**
   * @param array $keys
   */
  abstract protected function _deleteMulti( array $keys );


  abstract protected function _close();


} // AdapterAbstract
