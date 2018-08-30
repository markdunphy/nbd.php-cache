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


namespace Behance\NBD\Cache\Adapters;

use Behance\NBD\Cache\AdapterInterface;
use Behance\NBD\Cache\Test\BaseTest;
use Symfony\Component\EventDispatcher\EventDispatcher;

class MemcachedAdapterTest extends BaseTest {

  private $_cache = 'Memcached';


  protected function setUp() {

    if (!extension_loaded('memcached')) {
      $this->markTestSkipped('Memcached extension is not available');
    }

  }


  /**
   * Ensures that simple wrapper functions will target cache instance correctly
   *
   * @test
   * @dataProvider passthruProvider
   */
  public function passthru($method, $args, $expected) {

    $memcache = $this->createMock($this->_cache);

    $memcache->expects($this->once())
      ->method($method)
      ->will($this->returnValue($expected));

    $adapter = new MemcachedAdapter(null, $memcache);
    $results = call_user_func_array([$adapter, $method], $args);

    $this->assertEquals($expected, $results);

  }


  /**
   * @return array
   */
  public function passthruProvider() {

    $key = 'abcdefg';
    $value = 12345;

    $keys = ['abc', 'def', 'ghi'];
    $values = ['abc' => 123, 'def' => 456, 'ghi' => 789];

    return [
      'addServer' => ['addServer', ['cache1.com', 11211], null],
      'get' => ['get', [$key], $value],
      'getMulti' => ['getMulti', [$keys], $values],
      'set' => ['set', [$key, $value], true],
      'add' => ['add', [$key, $value], true],
      'replace' => ['replace', [$key, $value], true],
      'increment' => ['increment', [$key, $value, 1], true],
      'decrement' => ['decrement', [$key, $value, 1], true],
      'delete' => ['delete', [$key], true],
      'deleteMulti' => ['deleteMulti', [$keys], true],
      'flush' => ['flush', [], true],
      'getStats' => ['getStats', [], $keys],
      'getAllKeys' => ['getAllKeys', [], $keys],
    ];

  }


  /**
   * @test
   */
  public function close() {

    $memcache = $this->getMockBuilder($this->_cache)
      ->setMethods(['quit'])
      ->getMock();

    $memcache->expects($this->once())
      ->method('quit');

    $adapter = new MemcachedAdapter(null, $memcache);
    $adapter->close();

  }


  /**
   * @test
   */
  public function addServer() {

    $memcache = $this->getMockBuilder($this->_cache)
      ->setMethods(['addServer'])
      ->getMock();

    $host = 'cache1.com';
    $port = 11211;

    $memcache->expects($this->once())
      ->method('addServer')
      ->with($host, $port);

    $adapter = new MemcachedAdapter(null, $memcache);

    $adapter->addServer($host, $port);

  }


  /**
   * @test
   */
  public function addServers() {

    $memcache = $this->getMockBuilder($this->_cache)
      ->setMethods(['addServers'])
      ->getMock();

    $host1 = 'cache1.com';
    $host2 = 'cache2.com';
    $port = 11211;

    $servers = [
      ['host' => $host1, 'port' => $port],
      ['host' => $host2, 'port' => $port],
    ];

    $formatted = [
      [$host1, $port],
      [$host2, $port]
    ];

    $memcache->expects($this->once())
      ->method('addServers')
      ->with($formatted);

    $adapter = new MemcachedAdapter(null, $memcache);

    $adapter->addServers($servers);

  }


  /**
   * @test
   */
  public function executeFail() {

    $memcache = $this->getMockBuilder($this->_cache)
      ->setMethods(['add', 'getResultCode'])
      ->getMock();

    $result = false;

    $memcache->expects($this->once())
      ->method('getResultCode')
      ->will($this->returnValue(\Memcached::RES_FAILURE));

    $memcache->expects($this->once())
      ->method('add')
      ->will($this->returnValue($result));

    $dispatcher = new EventDispatcher();
    $adapter = new MemcachedAdapter($dispatcher, $memcache);
    $hit = false;

    $dispatcher->addListener(AdapterInterface::EVENT_QUERY_FAIL, function () use (&$hit) {
      $hit = true;
    });

    $this->assertEquals($result, $adapter->add('abc', 123));
    $this->assertTrue($hit);

  }

}
