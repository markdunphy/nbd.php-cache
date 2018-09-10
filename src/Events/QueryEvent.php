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
   * @param string          $operation    which query function is in use
   * @param string|string[] $key_or_keys  what was being operated on (one or many)
   * @param bool            $mutable      whether operation was safe
   */
  public function __construct($operation, $key_or_keys, $mutable) {

    $this->_operation = $operation;
    $this->_key = $key_or_keys;
    $this->_mutable = $mutable;

  }


  /**
   * @return string|string[]
   */
  public function getKey() {

    return $this->_key;

  }


  /**
   * @return bool
   */
  public function hasMultipleKeys() {

    return is_array($this->_key);

  }


  /**
   * @return string
   */
  public function getOperation() {

    return $this->_operation;

  }


  /**
   * @return bool
   */
  public function isMutable() {

    return $this->_mutable;

  }

}
