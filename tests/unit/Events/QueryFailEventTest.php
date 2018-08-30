<?php

namespace Behance\NBD\Cache\Events;

use Behance\NBD\Cache\Test\BaseTest;

class QueryFailEventTest extends BaseTest
{

  /**
   * @test
   */
    public function construct()
    {

        $reason   = 'anything at all';
        $hostname = 'cache1.com';
        $port     = 11211;
        $code     = 123456;

        $event = new QueryFailEvent($reason, $hostname, $port, $code);

        $this->assertEquals($reason, $event->getReason());
        $this->assertEquals($hostname, $event->getHostname());
        $this->assertEquals($port, $event->getPort());
        $this->assertEquals($code, $event->getCode());
    } // construct
} // QueryFailEventTest
