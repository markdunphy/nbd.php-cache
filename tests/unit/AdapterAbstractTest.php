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

use Behance\NBD\Cache\Test\BaseTest;

use Symfony\Component\EventDispatcher\EventDispatcher;

class AdapterAbstractTest extends BaseTest {

  /**
   * @test
   * @dataProvider operationProvider
   */
  public function operation($name, $multiple, $with_events) {

    $key = 'abc';
    $keys = ['def', 'ghi', 'jkl', 'mnop'];
    $value = 123;
    $return = 'arbitrary';
    $args = ($with_events)
              ? [$this->createMock(EventDispatcher::class)]
              : [];

    $mock = $this->_getAbstractMock(AdapterAbstract::class, [], $args);

    $mock->expects($this->once())
      ->method('_' . $name)
      ->will($this->returnValue($return));

    $result = ($multiple)
              ? $mock->$name($keys, $value)
              : $mock->$name($key, $value);

    $this->assertEquals($return, $result);

  }


  public function operationProvider() {

    return [
      ['get', false, false],
      ['get', false, true],
      ['getMulti', true, false],
      ['getMulti', true, true],
      ['set', false, false],
      ['set', false, true],
      ['add', false, false],
      ['add', false, true],
      ['replace', false, false],
      ['replace', false, true],
      ['increment', false, false],
      ['increment', false, true],
      ['decrement', false, false],
      ['decrement', false, true],
      ['delete', false, false],
      ['delete', false, true],
      ['deleteMulti', true, false],
      ['deleteMulti', true, true],
    ];

  }


  /**
   * @test
   * @dataProvider eventNameProvider
   */
  public function bindEvent($event_name) {

    $dispatcher = $this->createMock(EventDispatcher::class);
    $mock = $this->_getAbstractMock(AdapterAbstract::class, ['_buildEventDispatcher'], [$dispatcher]);
    $handler = (function () {});

    $mock->expects($this->never())
      ->method('_buildEventDispatcher');

    $dispatcher->expects($this->once())
      ->method('addListener')
      ->with($event_name, $handler);

    $mock->bindEvent($event_name, $handler);

  }


  /**
   * @test
   */
  public function getBoundEvents() {

    $mock = $this->_getAbstractMock(AdapterAbstract::class);

    $this->assertEquals([], $mock->getBoundEvents());

    $handler_1 = (function () {});
    $handler_2 = (function () {});

    $mock->bindEvent('test_event_1', $handler_1);
    $mock->bindEvent('test_event_2', $handler_2);

    $events = $mock->getBoundEvents();

    $this->assertSame($handler_1, $events['test_event_1'][0]);
    $this->assertSame($handler_2, $events['test_event_2'][0]);

  }


  /**
   * Ensures calling ->bindEvent() with a preassigned event dispatcher will generate one
   *
   * @test
   */
  public function bindBuildDispatcher() {

    $mock = $this->_getAbstractMock(AdapterAbstract::class, ['_buildEventDispatcher']);
    $dispatcher = $this->createMock(EventDispatcher::class);
    $callable = (function () {});

    $mock->expects($this->once())
      ->method('_buildEventDispatcher')
      ->will($this->returnValue($dispatcher));

    $mock->bindEvent(AdapterInterface::EVENT_QUERY_BEFORE, $callable);

    // Ensure calling it unmocked does not explode
    $vanilla_mock = $this->_getAbstractMock(AdapterAbstract::class);
    $vanilla_mock->bindEvent(AdapterInterface::EVENT_QUERY_BEFORE, $callable);

  }


  /**
   * @return array
   */
  public function eventNameProvider() {

    return [
      [AdapterInterface::EVENT_QUERY_BEFORE],
      [AdapterInterface::EVENT_QUERY_AFTER],
    ];

  }


  /**
   * @test
   */
  public function isBuffering() {

    $mock = $this->_getAbstractMock(AdapterAbstract::class);

    $this->assertFalse($mock->isBuffering());

    $mock->beginBuffer();

    $this->assertTrue($mock->isBuffering());

  }


  /**
   * Ensure that both rollback and commit reset the state of buffering
   *
   * @test
   * @dataProvider opBoolProvider
   */
  public function isBufferingAfter($commit) {

    $mock = $this->_getAbstractMock(AdapterAbstract::class);

    $mock->beginBuffer();

    $this->assertTrue($mock->isBuffering());

    if ($commit) {
      $mock->commitBuffer();
    } else {
      $mock->rollbackBuffer();
    }

    $this->assertFalse($mock->isBuffering());

  }


  /**
   * @return array
   */
  public function opBoolProvider() {

    return [
      'Commit' => [true],
      'Rollback' => [false],
    ];

  }


  /**
   * @test
   * @expectedException Behance\NBD\Cache\Exceptions\DuplicateActionException
   */
  public function beginBufferingDuplicate() {

    $mock = $this->_getAbstractMock(AdapterAbstract::class);

    $mock->beginBuffer();
    $mock->beginBuffer();

  }


  /**
   * @test
   * @dataProvider cacheGetSetProvider
   */
  public function bufferedGetSetRollback(array $key_values, $expected_key, $expected_value) {

    $mock = $this->_getAbstractMock(AdapterAbstract::class, ['_performExecute']);

    // Ensure sets never hit adapter
    $mock->expects($this->never())
      ->method('_performExecute');

    $mock->beginBuffer();

    foreach ($key_values as $key => $value) {
      $mock->set($key, $value);
    }

    $this->assertEquals($expected_value, $mock->get($expected_key));
    $this->assertEquals([$expected_key => $expected_value], $mock->getMulti([$expected_key]));
    $this->assertEquals($key_values, $mock->getMulti(array_keys($key_values)));

    $mock->rollbackBuffer();

  }


  /**
   * @test
   * @dataProvider cacheGetSetProvider
   */
  public function bufferedGetSetCommit(array $key_values, $expected_key, $expected_value) {

    $mock = $this->_getAbstractMock(AdapterAbstract::class, ['_performExecute']);

    // TODO: figure out how to match keys in-order of being set during commit
    $mock->expects($this->exactly(count($key_values)))
      ->method('_performExecute')
      ->with($this->anything(), 'set', $this->anything(), true);

    $mock->beginBuffer();

    foreach ($key_values as $key => $value) {
      $mock->set($key, $value);
    }

    // Creating operations, just to ensure these don't get buffered accidentally
    $this->assertEquals($expected_value, $mock->get($expected_key));
    $this->assertEquals([$expected_key => $expected_value], $mock->getMulti([$expected_key]));

    $mock->commitBuffer();

  }


  /**
   * Ensure the order of operations do not affect the results
   *
   * @return array
   */
  public function cacheGetSetProvider() {

    $key = 'abcdefg';
    $value = 123456;

    $map1 = [
      'abc' => 123,
      'def' => 456,
      $key => $value
    ];

    $map2 = [
      $key => $value,
      'abc' => 123,
      'def' => 456,
    ];

    $map3 = [
      'abc' => 123,
      $key => $value,
      'def' => 456,
    ];

    return [
      [$map1, $key, $value],
      [$map2, $key, $value],
      [$map3, $key, $value],
    ];

  }


  /**
   * @test
   * @dataProvider cacheSetDeleteGetProvider
   */
  public function bufferedSetDeleteGet(array $key_values, array $deleted_keys, $expected_key, $expected_value, $multi_delete) {

    $mock = $this->_getAbstractMock(AdapterAbstract::class, ['_performExecute']);

    // Ensure set's/delete's never actually hit
    $mock->expects($this->never())
      ->method('_performExecute');

    $mock->beginBuffer();

    foreach ($key_values as $key => $value) {
      $mock->set($key, $value);
    }

    // Ensure buffer is consistent whether or not keys were deleted via delete() or deleteMulti()
    if ($multi_delete) {
      $mock->deleteMulti($deleted_keys);
    } else {
      foreach ($deleted_keys as $deleted_key) {
        $mock->delete($deleted_key);
      }
    }

    $this->assertEquals($expected_value, $mock->get($expected_key));
    $this->assertEquals([$expected_key => $expected_value], $mock->getMulti([$expected_key]));

    $mock->rollbackBuffer();

  }


  /**
   * @test
   * @dataProvider cacheSetDeleteGetProvider
   */
  public function bufferedSetDeleteGetCommit(array $key_values, array $deleted_keys, $expected_key, $expected_value, $multi_delete) {

    $mock = $this->_getAbstractMock(AdapterAbstract::class, ['_performExecute']);

    // Ensure set's/delete's never actually hit
    $ops = ($multi_delete)
           ? count($key_values) + 1
           : count($key_values) + count($deleted_keys);

    $mock->expects($this->exactly($ops))
      ->method('_performExecute');

    $mock->beginBuffer();

    foreach ($key_values as $key => $value) {
      $mock->set($key, $value);
    }

    // Ensure buffer is consistent whether or not keys were deleted via delete() or deleteMulti()
    if ($multi_delete) {
      $mock->deleteMulti($deleted_keys);
    } else {
      foreach ($deleted_keys as $deleted_key) {
        $mock->delete($deleted_key);
      }
    }

    // Creating operations, just to ensure these don't get buffered accidentally
    $this->assertEquals($expected_value, $mock->get($expected_key));
    $this->assertEquals([$expected_key => $expected_value], $mock->getMulti([$expected_key]));

    $mock->commitBuffer();

  }


  /**
   * Ensure the order of operations do not affect the results
   *
   * @return array
   */
  public function cacheSetDeleteGetProvider() {

    $key = 'abcdefg';
    $value = 123456;

    $map1 = [
      'abc' => 123,
      'def' => 456,
      $key => $value
    ];

    $map2 = [
      $key => $value,
      'abc' => 123,
      'def' => 456,
    ];

    $map3 = [
      'abc' => 123,
      $key => $value,
      'def' => 456,
    ];

    return [
      [$map1, ['abc'], $key, $value, true],
      [$map1, ['abc'], $key, $value, false],
      [$map1, ['def'], $key, $value, true],
      [$map1, ['def'], $key, $value, false],
      [$map1, ['abc', 'def'], $key, $value, true],
      [$map1, ['abc', 'def'], $key, $value, false],
      [$map1, [$key], $key, false, true],
      [$map1, [$key], $key, false, false],
      [$map1, ['abc', 'def', $key], $key, false, true],
      [$map1, ['abc', 'def', $key], $key, false, false],

      [$map2, ['abc'], $key, $value, true],
      [$map2, ['abc'], $key, $value, false],
      [$map2, ['def'], $key, $value, true],
      [$map2, ['def'], $key, $value, false],
      [$map2, ['abc', 'def'], $key, $value, true],
      [$map2, ['abc', 'def'], $key, $value, false],
      [$map2, [$key], $key, false, true],
      [$map2, [$key], $key, false, false],
      [$map2, ['abc', 'def', $key], $key, false, true],
      [$map2, ['abc', 'def', $key], $key, false, false],

      [$map3, ['abc'], $key, $value, true],
      [$map3, ['abc'], $key, $value, false],
      [$map3, ['def'], $key, $value, true],
      [$map3, ['def'], $key, $value, false],
      [$map3, ['abc', 'def'], $key, $value, true],
      [$map3, ['abc', 'def'], $key, $value, false],
      [$map3, [$key], $key, false, true],
      [$map3, [$key], $key, false, false],
      [$map3, ['abc', 'def', $key], $key, false, true],
      [$map3, ['abc', 'def', $key], $key, false, false],
    ];

  }


  /**
   * Ensure combo of buffered + unbuffered keys will mix consistently
   *
   * @test
   * @dataProvider cacheBufferUnbufferProvider
   */
  public function unbufferedSetGet(array $key_values, $expected_key, $expected_value) {

    $mock = $this->_getAbstractMock(AdapterAbstract::class, ['_performExecute']);

    $mock->expects($this->once())
      ->method('_performExecute')
      ->with($this->anything(), 'get', $expected_key, false)
      ->will($this->returnValue($expected_value));

    $mock->beginBuffer();

    foreach ($key_values as $key => $value) {
      $mock->set($key, $value);
    }

    $this->assertEquals($expected_value, $mock->get($expected_key));
    $this->assertEquals($key_values, $mock->getMulti(array_keys($key_values)));

  }


  /**
   * Ensure combo of buffered + unbuffered keys will mix consistently
   *
   * @test
   * @dataProvider cacheBufferUnbufferProvider
   */
  public function unbufferedSetGetMulti(array $key_values, $expected_key, $expected_value) {

    $mock = $this->_getAbstractMock(AdapterAbstract::class, ['_performExecute']);

    $mock->expects($this->once())
      ->method('_performExecute')
      ->with($this->anything(), 'getMulti', [$expected_key], false)
      ->will($this->returnValue([$expected_key => $expected_value]));

    $mock->beginBuffer();

    foreach ($key_values as $key => $value) {
      $mock->set($key, $value);
    }

    $keys = array_keys($key_values);
    $keys[] = $expected_key;

    // Add expected into full mix for comparison
    $key_values[$expected_key] = $expected_value;

    $this->assertEquals($key_values, $mock->getMulti($keys));

  }


  /**
   * @return array
   */
  public function cacheBufferUnbufferProvider() {

    $key = 'abcdefg';
    $value = 123456;

    $map1 = [
      'abc' => 123,
      'def' => 456,
      'ghi' => 789
    ];

    return [
      'Unbuffered Key' => [$map1, $key, $value]
    ];

  }


  /**
   * @test
   * @dataProvider unsupportedBufferOpProvider
   * @expectedException Behance\NBD\Cache\Exceptions\OperationNotSupportedException
   */
  public function unsupportedBufferedOps($operation) {

    $mock = $this->_getAbstractMock(AdapterAbstract::class, ['_performExecute']);

    // TODO: figure out how to match keys in-order of being set during commit
    $mock->expects($this->never())
      ->method('_performExecute');

    $mock->beginBuffer();

    $mock->{$operation}('abcdef', 123);

  }


  /**
   * @return arrau
   */
  public function unsupportedBufferOpProvider() {

    return [
      ['add'],
      ['replace'],
      ['increment'],
      ['decrement'],
      ['flush'],
    ];

  }

}
