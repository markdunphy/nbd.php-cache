<?php

namespace Behance\NBD\Cache;

use Behance\NBD\Cache\Test\BaseTest;

class FactoryTest extends BaseTest {

  /**
   * @test
   * @dataProvider typeProvider
   */
  public function constructManual( $type ) {

    $factory = new Factory( $type );

    $this->assertSame( $type, $factory->getType() );

  } // constructManual


  /**
   * @test
   * @dataProvider typeProvider
   * @expectedException Behance\NBD\Cache\Exceptions\SystemRequirementException
   * @expectedExceptionMessage Selected cache adapter
   */
  public function constructManualUnavailable( $type ) {

    $factory = $this->_getDisabledMock( Factory::class, [ '_isExtensionLoaded' ] );

    $factory->expects( $this->once() )
      ->method( '_isExtensionLoaded' )
      ->with( strtolower( $type ) )
      ->will( $this->returnValue( false ) );

    $factory->__construct( $type );

  } // constructManualUnavailable


  /**
   * @test
   * @dataProvider typeProvider
   */
  public function constructAutoPriorityType( $type ) {

    $factory = $this->_getDisabledMock( Factory::class, [ '_isExtensionLoaded' ] );

    // Top priority, should only loop once
    if ( $type === Factory::TYPE_MEMCACHED ) {

      $factory->expects( $this->once() )
        ->method( '_isExtensionLoaded' )
        ->with( strtolower( $type ) )
        ->will( $this->returnValue( true ) );

    } // if memcached

    else {

      $factory->expects( $this->exactly( 2 ) )
        ->method( '_isExtensionLoaded' )
        ->will( $this->onConsecutiveCalls( false, true ) );

    } // else

    $factory->__construct();

    $this->assertSame( $type, $factory->getType() );

  } // constructAutoPriorityType


  /**
   * @test
   * @expectedException Behance\NBD\Cache\Exceptions\SystemRequirementException
   * @expectedExceptionMessage No cache extensions
   */
  public function constructUnavailable() {

    $factory = $this->_getDisabledMock( Factory::class, [ '_isExtensionLoaded' ] );

    $factory->expects( $this->atLeastOnce() )
      ->method( '_isExtensionLoaded' )
      ->will( $this->returnValue( false ) );

    $factory->__construct();

  } // constructUnavailable


  /**
   * @test
   * @expectedException Behance\NBD\Cache\CacheException
   * @expectedExceptionMessage Invalid cache adapter
   */
  public function constructBad() {

    new Factory( 'invalid_type' );

  } // constructBad


  /**
   * @test
   * @dataProvider typeProvider
   */
  public function create( $type ) {

    $config  = [ [ 'host' => 'cache1.com', 'port' => 11211 ] ];

    $adapter = Factory::create( $config, $type );
    $class   = Factory::NAMESPACE_ADAPTERS . $type . Factory::ADAPTER_SUFFIX;

    $this->assertInstanceOf( $class, $adapter );

  } // create


  /**
   * @return array
   */
  public function typeProvider() {

    $types = [];

    if ( extension_loaded( 'memcached' ) ) {
      $types[] = [ Factory::TYPE_MEMCACHED ];
    }

    if ( extension_loaded( 'memcache' ) ) {
      $types[] = [ Factory::TYPE_MEMCACHE ];
    }

    return $types;

  } // typeProvider

} // FactoryTest
