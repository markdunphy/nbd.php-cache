<?php

namespace Behance\NBD\Cache\Adapters;

use Behance\NBD\Cache\Test\BaseTest;

class MemcacheAdapterTest extends BaseTest {

  private $_target        = 'Behance\\NBD\\Cache\\Adapters\\MemcacheAdapter';
  private $_cache         = 'Memcache';
  private $_server_config = [
      [ 'host' => 'cache1.com', 'port' => 12345 ],
      [ 'host' => 'cache2.com', 'port' => 12345 ],
      [ 'host' => 'cache3.com', 'port' => 12345 ],
      [ 'host' => 'cache4.com', 'port' => 12345 ],
  ];

  protected function setUp() {

    if ( !extension_loaded( 'memcache' ) ) {
      $this->markTestSkipped( 'Memcache extension is not available' );
    }

  } // setUp


  /**
   * Ensures that simple wrapper functions will target cache instance correctly
   *
   * @test
   * @dataProvider passthruProvider
   */
  public function passthru( $method, $args, $expected ) {

    $memcache = $this->getMock( $this->_cache, [ $method ] );

    $memcache->expects( $this->once() )
      ->method( $method )
      ->will( $this->returnValue( $expected ) );

    $adapter = new MemcacheAdapter( null, $memcache );
    $results = call_user_func_array( [ $adapter, $method ], $args );

    $this->assertEquals( $expected, $results );

  } // passthru


  /**
   * @return array
   */
  public function passthruProvider() {

    $key   = 'abcdefg';
    $value = 12345;

    return [
        'get'       => [ 'get',       [ $key ],            $value ],
        'set'       => [ 'set',       [ $key, $value ],    true ],
        'add'       => [ 'add',       [ $key, $value ],    true ],
        'replace'   => [ 'replace',   [ $key, $value ],    true ],
        'increment' => [ 'increment', [ $key, $value, 1 ], true ],
        'decrement' => [ 'decrement', [ $key, $value, 1 ], true ],
        'delete'    => [ 'delete',    [ $key ],            true ],
        'close'     => [ 'close',     [],                  true ],
    ];

  } // passthruProvider


  /**
   * @test
   */
  public function getMulti() {

    $keys  = [ 'abcdef', 'defghi' ];
    $value = [ 'abcdef' => 12345, 'defghi' => 67890 ];

    // IMPORTANT: this adapter takes multiple argument types through `get`
    $memcache = $this->getMock( $this->_cache, [ 'get' ] );
    $memcache->expects( $this->once() )
      ->method( 'get' )
      ->with( $keys )
      ->will( $this->returnValue( $value ) );

    $adapter = new MemcacheAdapter( null, $memcache );

    $this->assertEquals( $value, $adapter->getMulti( $keys ) );

  } // getMulti


  /**
   * Ensures that missing results will have requested keys defined
   * @test
   */
  public function getMultiMissing() {

    $keys  = [ 'abc', 'def', 'ghi' ];
    $value = [];

    // IMPORTANT: this adapter takes multiple argument types through `get`
    $memcache = $this->getMock( $this->_cache, [ 'get' ] );
    $memcache->expects( $this->once() )
      ->method( 'get' )
      ->with( $keys )
      ->will( $this->returnValue( $value ) );

    $adapter = new MemcacheAdapter( null, $memcache );

    $results = $adapter->getMulti( $keys );

    $this->assertEquals( $keys, array_keys( $results ) );

  } // getMultiMissing


  /**
   * @test
   */
  public function deleteMulti() {

    $keys  = [ 'abc', 'def', 'ghi' ];

    // IMPORTANT: this adapter only simulates a multi-delete
    $memcache = $this->getMock( $this->_cache, [ 'delete' ] );
    $memcache->expects( $this->exactly( count( $keys ) ) )
      ->method( 'delete' );

    $adapter = new MemcacheAdapter( null, $memcache );

    $adapter->deleteMulti( $keys );

  } // deleteMulti


  /**
   * @test
   */
  public function addServer() {

    $memcache = $this->getMock( $this->_cache, [ 'addServer' ] );

    $host     = 'cache1.com';
    $port     = 11211;
    $persist  = MemcacheAdapter::DEFAULT_PERSISTENT;
    $timeout  = MemcacheAdapter::DEFAULT_TIMEOUT_SECS;
    $retry    = MemcacheAdapter::DEFAULT_RETRY_INTERVAL_SECS;
    $status   = MemcacheAdapter::DEFAULT_SERVER_STATUS;
    $weight   = MemcacheAdapter::DEFAULT_WEIGHT;

    $memcache->expects( $this->once() )
      ->method( 'addServer' )
      ->with( $host, $port, $persist, $weight, $timeout, $retry, $status, $this->isInstanceOf( 'Closure' ) );

    $adapter = new MemcacheAdapter( null, $memcache );

    $adapter->addServer( $host, $port );

  } // addServer


  /**
   * @test
   */
  public function addServers() {

    $mock = $this->getMock( $this->_target, [ 'addServer' ] );

    $mock->expects( $this->exactly( count( $this->_server_config ) ) )
      ->method( 'addServer' );

    $mock->addServers( $this->_server_config );

  } // addServers

} // MemcacheAdapterTest
