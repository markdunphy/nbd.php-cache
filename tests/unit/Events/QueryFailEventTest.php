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

use Behance\NBD\Cache\Test\BaseTest;

class QueryFailEventTest extends BaseTest {

  /**
   * @test
   */
  public function construct() {

    $reason = 'anything at all';
    $hostname = 'cache1.com';
    $port = 11211;
    $code = 123456;

    $event = new QueryFailEvent($reason, $hostname, $port, $code);

    $this->assertEquals($reason, $event->getReason());
    $this->assertEquals($hostname, $event->getHostname());
    $this->assertEquals($port, $event->getPort());
    $this->assertEquals($code, $event->getCode());

  }

}
