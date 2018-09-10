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

class QueryFailEvent extends Event {

  /**
   * @var string
   */
  private $_hostname;

  /**
   * @var int
   */
  private $_port;


  /**
   * @var string
   */
  private $_reason;


  /**
   * @var int
   */
  private $_code;


  /**
   * @param string $reason
   * @param string $hostname
   * @param int    $port
   * @param int    $code
   */
  public function __construct($reason, $hostname = null, $port = null, $code = null) {

    $this->_reason = $reason;
    $this->_hostname = $hostname;
    $this->_port = $port;
    $this->_code = $code;

  }


  /**
   * @return string
   */
  public function getReason() {

    return $this->_reason;

  }


  /**
   * @return string
   */
  public function getHostname() {

    return $this->_hostname;

  }


  /**
   * @return int
   */
  public function getPort() {

    return $this->_port;

  }


  /**
   * @return int
   */
  public function getCode() {

    return $this->_code;

  }

}
