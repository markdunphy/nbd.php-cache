<?php

namespace Behance\NBD\Cache\Events;

use Symfony\Component\EventDispatcher\Event;

class QueryEvent extends Event {

  /**
   * @var string|string[]
   */
  private $_key;

  /**
   * @var string
   */
  private $_operation;

  /**
   * @var bool
   */
  private $_mutable;


  /**
   * @param string          $operation  which query function is in use
   * @param string|string[] $key        what was being operated on (one or many)
   * @param bool            $mutable
   */
  public function __construct( $operation, $key_or_keys, $mutable ) {

    $this->_operation = $operation;
    $this->_key       = $key_or_keys;
    $this->_mutable   = $mutable;

  } // __construct


  /**
   * @return string|string[]
   */
  public function getKey() {

    return $this->_key;

  } // getKey


  /**
   * @return bool
   */
  public function hasMultipleKeys() {

    return is_array( $this->_key );

  } // hasMultipleKeys


  /**
   * @return string
   */
  public function getOperation() {

    return $this->_operation;

  } // getOperation


  /**
   * @return bool
   */
  public function isMutable() {

    return $this->_mutable;

  } // isMutable

} // QueryEvent
