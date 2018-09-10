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

class QueryEventTest extends BaseTest {

  /**
   * @test
   * @dataProvider constructProvider
   */
  public function construct($operation, $key_or_keys, $mutable) {

    $event = new QueryEvent($operation, $key_or_keys, $mutable);

    $this->assertEquals($operation, $event->getOperation());
    $this->assertEquals($key_or_keys, $event->getKey());
    $this->assertEquals(is_array($key_or_keys), $event->hasMultipleKeys());
    $this->assertEquals($mutable, $event->isMutable());

  }

  /**
   * @return array
   */
  public function constructProvider() {

    return [
      ['get', 'abcefg', false],
      ['add', 'abcefg', true],
      ['getMulti', ['abc', 'efg'], false],
      ['deleteMulti', ['abc', 'efg'], true],
    ];

  }

}
