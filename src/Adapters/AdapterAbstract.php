<?php

namespace Behance\NBD\Cache\Adapters;

use Behance\NBD\Cache\Events\QueryEvent;
use Behance\NBD\Cache\Events\QueryFailEvent;

use Behance\NBD\Cache\Interfaces\CacheAdapterInterface;

use Behance\NBD\Cache\Exceptions\DuplicateActionException;
use Behance\NBD\Cache\Exceptions\OperationNotSupportedException;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

abstract class AdapterAbstract implements CacheAdapterInterface {

  /**
   * @var Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $_dispatcher;

  /**
   * @var bool  indicates whether or not connection is in buffering mode
   */
  protected $_is_buffering = false;

  /**
   * TODO: convert to class constant once PHP support <5.6 is dropped
   * @var string[]  defines a small set of caching operations that can function in a buffered state
   */
  protected $_SUPPORTED_BUFFERED_OPS = [
      'set',
      'get',
      'getMulti',
      'delete',
      'deleteMulti'
  ];

  /**
   * @var array  queued operations to take place when committing buffer
   */
  protected $_buffered_ops;

  /**
   * @var array  acts as key-value storage during buffer operations
   */
  protected $_buffer;


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

    // NOTE: $keys are passed/used, since count/values may change in buffering mode
    $action = ( function( $keys ) {
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

    return $this->_execute( $action, __FUNCTION__, $key, true, $value );

  } // set


  /**
   * {@inheritDoc}
   */
  public function add( $key, $value, $ttl = CacheAdapterInterface::EXPIRATION_DEFAULT ) {

    $action = ( function() use ( $key, $value, $ttl ) {
      return $this->_add( $key, $value, $ttl );
    } );

    return $this->_execute( $action, __FUNCTION__, $key, true, $value );

  } // add


  /**
   * {@inheritDoc}
   */
  public function replace( $key, $value, $ttl = CacheAdapterInterface::EXPIRATION_DEFAULT ) {

    $action = ( function() use ( $key, $value, $ttl ) {
      return $this->_replace( $key, $value, $ttl );
    } );

    return $this->_execute( $action, __FUNCTION__, $key, true, $value );

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

    return $this->_execute( $action, __FUNCTION__, $key, true, $value );

  } // increment


  /**
   * {@inheritDoc}
   */
  public function decrement( $key, $value = 1 ) {

    $action = ( function() use ( $key, $value ) {
      return $this->_decrement( $key, $value );
    } );

    return $this->_execute( $action, __FUNCTION__, $key, true, $value );

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
   * {@inheritDoc}
   */
  public function beginBuffer() {

    if ( $this->isBuffering() ) {
      throw new DuplicateActionException( "Buffering already started" );
    }

    $this->_is_buffering = true;
    $this->_buffered_ops = [];
    $this->_buffer       = [];

  } // beginBuffer


  /**
   * {@inheritDoc}
   */
  public function commitBuffer() {

    foreach ( $this->_buffered_ops as $args ) {

      // Unmarshal arguments to prepare for direct execution
      list( $action, $operation, $key_or_keys, $mutable ) = $args;

      $this->_performExecute( $action, $operation, $key_or_keys, $mutable );

    } // foreach buffered_operations

    // IMPORTANT: buffer is now committed, no longer in buffered mode
    $this->_is_buffering = false;
    $this->_bufferFlush();

  } // commitBuffer


  /**
   * {@inheritDoc}
   */
  public function rollbackBuffer() {

    // IMPORTANT: buffer is reverted, cache is untouched, no longer in buffered mode
    $this->_is_buffering = false;
    $this->_bufferFlush();

  } // rollbackBuffer


  /**
   * {@inheritDoc}
   */
  public function isBuffering() {

    return $this->_is_buffering;

  } // isBuffering


  /**
   * {@inheritDoc}
   */
  public function flush() {

    $action = ( function() {
      return $this->_flush();
    } );

    return $this->_execute( $action, __FUNCTION__, null, true );

  } // flush


  /**
   * {@inheritDoc}
   */
  public function getAllKeys() {

    $action = ( function() {
      return $this->_getAllKeys();
    } );

    return $this->_execute( $action, __FUNCTION__, '' );

  } // getAllKeys


  /**
   * {@inheritDoc}
   */
  public function getStats() {

    $action = ( function() {
      return $this->_getStats();
    } );

    return $this->_execute( $action, __FUNCTION__, '' );

  } // getStats


  /**
   * {@inheritDoc}
   */
  public function bindEvent( $event_name, callable $handler ) {

    // Build a dispatcher if one doesn't already exist
    if ( !$this->_dispatcher ) {
      $this->_dispatcher = $this->_buildEventDispatcher();
    }

    $this->_dispatcher->addListener( $event_name, $handler );

  } // bindEvent


  /**
   * {@inheritDoc}
   */
  public function close() {

    return $this->_close();

  } // close


  /**
   * When not buffering action is performed directly, otherwise, gets queued for execution
   *
   * @param \Closure        $action
   * @param string          $operation
   * @param string|string[] $key_or_keys
   * @param bool            $mutable
   * @param mixed           $value
   *
   * @return mixed
   */
  protected function _execute( \Closure $action, $operation, $key_or_keys, $mutable = false, $value = null ) {

    if ( !$this->isBuffering() ) {
      return $this->_performExecute( $action, $operation, $key_or_keys, $mutable );
    }

    if ( !in_array( $operation, $this->_SUPPORTED_BUFFERED_OPS ) ) {
      throw new OperationNotSupportedException( sprintf( '%s not supported during buffering', $operation ) );
    }

    return $this->_performBufferedExecute( $action, $operation, $key_or_keys, $mutable, $value );

  } // _execute


  /**
   * Used to wrap every direct action with a before and after event
   *
   * @param \Closure        $action
   * @param string          $operation
   * @param string|string[] $key_or_keys
   * @param bool            $mutable
   *
   * @return mixed
   */
  protected function _performExecute( \Closure $action, $operation, $key_or_keys, $mutable ) {

    $this->_emitQueryEvent( CacheAdapterInterface::EVENT_QUERY_BEFORE, $operation, $key_or_keys, $mutable );

    $result = $action( $key_or_keys );

    $this->_emitQueryEvent( CacheAdapterInterface::EVENT_QUERY_AFTER, $operation, $key_or_keys, $mutable );

    return $result;

  } // _performExecute


  /**
   * Selectively performs actions as they may be experienced with the buffer committed,
   * queues mutable actions for execution later
   * IMPORTANT: unsupported operations return false
   *
   * @param \Closure        $action
   * @param string          $operation
   * @param string|string[] $key_or_keys
   * @param bool            $mutable
   * @param mixed           $value
   *
   * @return mixed
   */
  protected function _performBufferedExecute( \Closure $action, $operation, $key_or_keys, $mutable, $value ) {

    switch ( $operation ) {

      case 'get':
        // Query operation results are important: if one exists in the current buffer, return it
        // ...otherwise, this op is safe to pass through to execute, do *not* buffer
        return ( $this->_bufferHasKey( $key_or_keys ) )
               ? $this->_bufferGet( $key_or_keys )
               : $this->_performExecute( $action, $operation, $key_or_keys, $mutable );

      case 'getMulti':
        // Combines results from buffered and actual cache outputs (unbuffered)
        $buffered    = [];
        $keys_to_get = [];

        foreach ( $key_or_keys as $get_key ) {

          if ( $this->_bufferHasKey( $get_key ) ) {
            $buffered[ $get_key ] = $this->_bufferGet( $get_key );
          }

          else {
            $keys_to_get[] = $get_key;
          }

        } // foreach key_or_keys

        if ( !empty( $keys_to_get ) ) {
          $unbuffered = $this->_performExecute( $action, $operation, $keys_to_get, $mutable );
        }

        $results = [];

        foreach ( $key_or_keys as $get_key ) {

          $results[ $get_key ] = ( isset( $buffered[ $get_key ] ) )
                                 ? $buffered[ $get_key ]
                                 : $unbuffered[ $get_key ];

        } // foreach key_or_keys

        return $results;

      case 'set':
        $this->_bufferSet( $key_or_keys, $value );
        break;

      case 'delete':
        $this->_bufferDelete( $key_or_keys );
        break;

      case 'deleteMulti':
        foreach ( $key_or_keys as $delete_key ) {
          $this->_bufferDelete( $delete_key );
        }
        break;

    } // switch operation

    // If fall-through, add operation to buffer queue, which will be replayed in-order on commit
    $this->_buffered_ops[] = [ $action, $operation, $key_or_keys, $mutable ];

  } // _performBufferedExecute


  /**
   * Resets buffer to empty state
   */
  protected function _bufferFlush() {

    $this->_buffered_ops = null;
    $this->_buffer       = null;

  } // _bufferFlush


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
   * Ensure callers have *already* checked for key in buffer,
   * since the result of this (and real) on a deleted call is a bool
   *
   * @param string $key
   *
   * @return mixed|bool  false to indicate key was deleted already
   */
  protected function _bufferGet( $key ) {

    // IMPORTANT: if key exists but is null --- this is to indicate a DELETED key
    return ( $this->_buffer[ $key ] === null )
           ? false
           : $this->_buffer[ $key ];

  } // _bufferGet


  /**
   * @param string $key
   * @param mixed  $value   set as null to indicate a DELETED key
   */
  protected function _bufferSet( $key, $value ) {

    $this->_buffer[ $key ] = $value;

  } // _bufferSet


  /**
   * @param string $key
   */
  protected function _bufferDelete( $key ) {

    $this->_buffer[ $key ] = null;

  } // _bufferDelete


  /**
   * Whether or not the local buffer contains the specified key
   *
   * @param string $key
   *
   * @return bool
   */
  protected function _bufferHasKey( $key ) {

    return array_key_exists( $key, $this->_buffer );

  } // _bufferHasKey


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


  /**
   * Disconnect from server
   */
  abstract protected function _close();

} // AdapterAbstract
