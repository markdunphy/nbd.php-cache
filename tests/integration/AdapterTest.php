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


namespace Behance\NBD\Cache;

use Behance\NBD\Cache\Test\IntegrationTest;

class AdapterTest extends IntegrationTest {

  private $_key = 'abcdef';
  private $_value = 12345;

  /**
   * @test
   * @dataProvider typeProvider
   */
  public function addRemove($type) {

    $adapter = $this->_getLiveAdapter($type);

    $this->assertFalse($adapter->get($this->_key));

    $adapter->set($this->_key, $this->_value);

    $this->assertEquals($this->_value, $adapter->get($this->_key));

    $this->assertTrue($adapter->delete($this->_key));

    $this->assertFalse($adapter->get($this->_key));

  }


  /**
   * @test
   * @dataProvider typeProvider
   */
  public function getMulti($type) {

    $adapter = $this->_getLiveAdapter($type);
    $data = [
      'abc' => 123,
      'def' => 456,
      'ghi' => 789
    ];

    foreach ($data as $key => $value) {
      $adapter->set($key, $value);
    }

    $keys = array_keys($data);
    $results = $adapter->getMulti($keys);

    $this->assertSame($data, $results);

  }


  /**
   * Place values into cache in one order, retrieve them in another order.
   * Ensure order is preserved
   *
   * @test
   * @dataProvider typeProvider
   */
  public function getMultiInOrder($type) {

    $adapter = $this->_getLiveAdapter($type);
    $data = [
      'abc' => 123,
      'def' => 456,
      'ghi' => 789
    ];

    $reversed = array_reverse($data);

    // Place values in cache backwards from how they will be requested
    foreach ($reversed as $key => $value) {
      $adapter->set($key, $value);
    }

    $keys = array_keys($data);
    $results = $adapter->getMulti($keys);

    $this->assertSame($data, $results);

  }


  /**
   * @test
   * @dataProvider typeProvider
   */
  public function failedConnection($type) {

    $bad_server = [
      'host' => '127.0.0.1',
      'port' => 11211
    ];

    $failed = false;
    $adapter = Factory::create([$bad_server], $type);

    // Ensure failure events are propegated
    $adapter->bindEvent(AdapterInterface::EVENT_QUERY_FAIL, function () use (&$failed) {
      $failed = true;
    });

    $this->assertFalse($adapter->get($this->_key));
    $this->assertTrue($failed);

  }


  /**
   * @test
   * @dataProvider typeProvider
   */
  public function getAllKeys($type) {

    $adapter = $this->_getLiveAdapter($type);
    $data = [
      'abc' => 123,
      'def' => 456,
      'ghi' => 789
    ];

    foreach ($data as $key => $value) {
      $adapter->set($key, $value);
    }

    $cache_keys = $adapter->getAllKeys();
    $all_keys = array_keys($data);

    foreach ($all_keys as $key) {
      $this->assertContains($key, $cache_keys);
    }

  }

}
