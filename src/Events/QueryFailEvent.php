<?php

namespace Behance\NBD\Cache\Events;

use Symfony\Component\EventDispatcher\Event;

class QueryFailEvent extends Event
{

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
    public function __construct($reason, $hostname = null, $port = null, $code = null)
    {

        $this->_reason   = $reason;
        $this->_hostname = $hostname;
        $this->_port     = $port;
        $this->_code     = $code;
    } // __construct


  /**
   * @return string
   */
    public function getReason()
    {

        return $this->_reason;
    } // getReason


  /**
   * @return string
   */
    public function getHostname()
    {

        return $this->_hostname;
    } // getHostname


  /**
   * @return int
   */
    public function getPort()
    {

        return $this->_port;
    } // getPort


  /**
   * @return int
   */
    public function getCode()
    {

        return $this->_code;
    } // getCode
} // QueryFailEvent
