<?php

namespace Behance\NBD\Cache\Test;

abstract class BaseTest extends \PHPUnit\Framework\TestCase {

  /**
   * @param string $class
   * @param array  $functions
   *
   * @return mixed  instace mock of $class
   */
  protected function _getDisabledMock( $class, array $functions = null ) {

    return $this->getMockBuilder( $class )
      ->setMethods( $functions )
      ->disableOriginalConstructor()
      ->getMock();

  } // _getDisabledMock


  /**
   * @param string $class
   * @param array  $functions
   * @param array  $arguments
   *
   * @return mixed  instace mock of $class
   */
  protected function _getAbstractMock( $class, array $functions = [], array $arguments = [] ) {

    return $this->getMockForAbstractClass( $class, $arguments, '', true, true, true, $functions );

  } // _getAbstractMock

} // BaseTest
