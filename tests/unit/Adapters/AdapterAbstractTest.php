<?php

namespace Behance\NBD\Cache\Adapters;

use Behance\NBD\Cache\Test\BaseTest;
use Behance\NBD\Cache\Interfaces\CacheAdapterInterface;

class AdapterAbstractTest extends BaseTest {

  private $_target     = 'Behance\\NBD\\Cache\\Adapters\\AdapterAbstract',
          $_dispatcher = 'Symfony\\Component\\EventDispatcher\\EventDispatcher';


  /**
   * @test
   * @dataProvider operationProvider
   */
  public function operation( $name, $multiple, $with_events ) {

    $key    = 'abc';
    $keys   = [ 'def', 'ghi', 'jkl', 'mnop' ];
    $value  = 123;
    $return = 'arbitrary';
    $args   = ( $with_events )
      ? [ $this->getMock( $this->_dispatcher ) ]
      : [];

    $mock = $this->_getAbstractMock( $this->_target, [], $args );

    $mock->expects( $this->once() )
      ->method( '_' . $name )
      ->will( $this->returnValue( $return ) );

    $result = ( $multiple )
      ? $mock->$name( $keys, $value )
      : $mock->$name( $key, $value );

    $this->assertEquals( $return, $result );

  } // operation


  public function operationProvider() {

    return [
        [ 'get',         false, false ],
        [ 'get',         false, true ],
        [ 'getMulti',    true,  false ],
        [ 'getMulti',    true,  true ],
        [ 'set',         false, false ],
        [ 'set',         false, true ],
        [ 'add',         false, false ],
        [ 'add',         false, true ],
        [ 'replace',     false, false ],
        [ 'replace',     false, true ],
        [ 'increment',   false, false ],
        [ 'increment',   false, true ],
        [ 'decrement',   false, false ],
        [ 'decrement',   false, true ],
        [ 'delete',      false, false ],
        [ 'delete',      false, true ],
        [ 'deleteMulti', true,  false ],
        [ 'deleteMulti', true,  true ],
    ];

  } // operationProvider


  /**
   * @test
   * @dataProvider eventNameProvider
   */
  public function bind( $event_name ) {

    $dispatcher = $this->getMock( $this->_dispatcher );
    $mock       = $this->_getAbstractMock( $this->_target, [ '_buildEventDispatcher' ], [ $dispatcher ] );
    $handler    = ( function() {} );

    $mock->expects( $this->never() )
      ->method( '_buildEventDispatcher' );

    $dispatcher->expects( $this->once() )
      ->method( 'addListener' )
      ->with( $event_name, $handler );

    $mock->bind( $event_name, $handler );

  } // bind


  /**
   * Ensures calling ->bind() with a preassigned event dispatcher will generate one
   *
   * @test
   */
  public function bindBuildDispatcher() {

    $mock       = $this->_getAbstractMock( $this->_target, [ '_buildEventDispatcher' ] );
    $dispatcher = $this->getMock( $this->_dispatcher );
    $callable   = ( function() {} );

    $mock->expects( $this->once() )
      ->method( '_buildEventDispatcher' )
      ->will( $this->returnValue( $dispatcher ) );

    $mock->bind( CacheAdapterInterface::EVENT_QUERY_BEFORE, $callable );

    // Ensure calling it unmocked does not explode
    $vanilla_mock = $this->_getAbstractMock( $this->_target );
    $vanilla_mock->bind( CacheAdapterInterface::EVENT_QUERY_BEFORE, $callable );

  } // bindBuildDispatcher


  /**
   * @return array
   */
  public function eventNameProvider() {

    return [
        [ CacheAdapterInterface::EVENT_QUERY_BEFORE ],
        [ CacheAdapterInterface::EVENT_QUERY_AFTER ],
    ];

  } // eventNameProvider


} // AdapterAbstractTest
