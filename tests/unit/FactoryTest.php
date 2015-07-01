<?php

namespace Behance\NBD\Cache;

use Behance\NBD\Cache\Test\BaseTest;

class FactoryTest extends BaseTest {

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

    if ( extension_loaded( 'memcache' ) ) {
      $types[] = [ Factory::TYPE_MEMCACHE ];
    }

    if ( extension_loaded( 'memcached' ) ) {
      $types[] = [ Factory::TYPE_MEMCACHED ];
    }

    return $types;

  } // typeProvider

} // FactoryTest
