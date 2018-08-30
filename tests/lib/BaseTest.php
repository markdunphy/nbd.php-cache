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


namespace Behance\NBD\Cache\Test;

abstract class BaseTest extends \PHPUnit\Framework\TestCase {

  /**
   * @param string $class
   * @param array  $functions
   *
   * @return mixed  instace mock of $class
   */
  protected function _getDisabledMock($class, array $functions = null) {

    return $this->getMockBuilder($class)
      ->setMethods($functions)
      ->disableOriginalConstructor()
      ->getMock();

  }


  /**
   * @param string $class
   * @param array  $functions
   * @param array  $arguments
   *
   * @return mixed  instace mock of $class
   */
  protected function _getAbstractMock($class, array $functions = [], array $arguments = []) {

    return $this->getMockForAbstractClass($class, $arguments, '', true, true, true, $functions);

  }

}
