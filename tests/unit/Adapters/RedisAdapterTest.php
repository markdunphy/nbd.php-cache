<?php

namespace Behance\NBD\Cache\Adapters;

use Behance\NBD\Cache\AdapterInterface;
use Behance\NBD\Cache\Test\BaseTest;
use Symfony\Component\EventDispatcher\EventDispatcher;

class RedisAdapterTest extends BaseTest {

  private $_cache = 'Redis';


  protected function setUp() {

    if ( !extension_loaded( 'redis' ) ) {
      $this->markTestSkipped( 'Redis extension is not available' );
    }

  } // setUp


  /**
   * Ensures that simple wrapper functions will target cache instance correctly
   *
   * @test
   * @dataProvider passthruProvider
   */
  public function passthru( $method, $translated, $args, $with, $expected ) {

    $redis = $this->createMock( $this->_cache );

    if ( $with === null ) {

      $redis->expects( $this->once() )
        ->method( $translated )
        ->willReturn( $expected );

    } // if !with

    else {

      $redis->expects( $this->once() )
        ->method( $translated )
        ->willReturn( $expected )
        ->with( $with );

    } // else (with)

    $adapter = new RedisAdapter( null, $redis );
    $results = call_user_func_array( [ $adapter, $method ], $args );

    $this->assertEquals( $expected, $results );

  } // passthru


  /**
   * @return array
   */
  public function passthruProvider() {

    $key    = 'abcdefg';
    $value  = 12345;
    $keys   = [ 'abc', 'def', 'ghi' ];
    // $values = [ 123, 456, 789 ];

    return [
        [ 'get',         'get',         [ $key ],            $key,  $value ],
        [ 'increment',   'incrBy',      [ $key, $value, 1 ], null, true ],
        [ 'decrement',   'decrBy',      [ $key, $value, 1 ], null, true ],
        [ 'delete',      'delete',      [ $key ],            $key,  true ],
        [ 'deleteMulti', 'delete',      [ $keys ],           $keys, true ],
        [ 'flush',       'flushDb',     [],                  null, true ],
        [ 'close',       'close',       [],                  null, true ]
    ];

  } // passthruProvider


  /**
   * @test
   * @dataProvider binaryProvider
   */
  public function set( $default_ttl ) {

    $redis = $this->createMock( $this->_cache );
    $key   = 'abc';
    $value = 123;
    $ttl   = $this->_getTtl( $default_ttl );

    $redis->expects( $this->once() )
      ->method( 'setEx' )
      ->with( $key, $ttl, $value );

    $adapter = new RedisAdapter( null, $redis );

    if ( $default_ttl ) {
      $adapter->set( $key, $value );
    }
    else {
      $adapter->set( $key, $value, $ttl );
    }

  } // set


  /**
   * Since memcache has a TTL setting for unlimited or permanent keys, and Redis
   * uses a different mechanism for this, swap "permanent" for a pseudo max length
   * Otherwise, Redis will *not* cache this value
   *
   * @test
   */
  public function setPseudoPermanent() {

    $redis = $this->createMock( $this->_cache );
    $key   = 'abc';
    $value = 123;
    $ttl   = RedisAdapter::PSEUDO_MAX;

    $redis->expects( $this->once() )
      ->method( 'setEx' )
      ->with( $key, $ttl, $value );

    $adapter = new RedisAdapter( null, $redis );

    $adapter->set( $key, $value, 0 );

  } // setPseudoPermanent


  /**
   * @test
   * @dataProvider binaryProvider
   */
  public function add( $default_ttl ) {

    $redis = $this->createMock( $this->_cache );
    $key   = 'abc';
    $value = 123;
    $ttl   = $this->_getTtl( $default_ttl );

    $redis->expects( $this->once() )
      ->method( 'set' )
      ->with( $key, $value, [ 'nx', 'ex' => $ttl ] );

    $adapter = new RedisAdapter( null, $redis );

    if ( $default_ttl ) {
      $adapter->add( $key, $value );
    }
    else {
      $adapter->add( $key, $value, $ttl );
    }

  } // add


  /**
   * @test
   * @dataProvider binaryProvider
   */
  public function replace( $default_ttl ) {

    $redis = $this->createMock( $this->_cache );
    $key   = 'abc';
    $value = 123;
    $ttl   = $this->_getTtl( $default_ttl );

    $redis->expects( $this->once() )
      ->method( 'set' )
      ->with( $key, $value, [ 'xx', 'ex' => $ttl ] );

    $adapter = new RedisAdapter( null, $redis );

    if ( $default_ttl ) {
      $adapter->replace( $key, $value );
    }
    else {
      $adapter->replace( $key, $value, $ttl );
    }

  } // replace

  /**
   * @return array
   */
  public function binaryProvider() {

    return [
        'true'  => [ true ],
        'false' => [ false ],
    ];

  } // binaryProvider

  /**
   * @test
   */
  public function addServer() {

    $memcache = $this->getMockBuilder( $this->_cache )
      ->setMethods( [ 'pconnect' ] )
      ->getMock();

    $host     = 'cache1.com';
    $port     = 11211;

    $memcache->expects( $this->once() )
      ->method( 'pconnect' )
      ->with( $host, $port );

    $adapter = new RedisAdapter( null, $memcache );

    $adapter->addServer( $host, $port );

  } // addServer


  /**
   * @test
   */
  public function addServers() {

    $redis = $this->getMockBuilder( $this->_cache )
      ->setMethods( [ 'pconnect', 'setOption' ] )
      ->getMock();

    $host1    = 'cache1.com';
    $host2    = 'cache2.com';
    $port     = 11211;

    $servers  = [
        [ 'host' => $host1, 'port' => $port ],
        [ 'host' => $host2, 'port' => $port ],
    ];

    $redis->expects( $this->exactly( count( $servers ) ) )
      ->method( 'pconnect' )
      ->withConsecutive( [ $host1, $port ], [ $host2, $port ] );


    $adapter = new RedisAdapter( null, $redis );

    $adapter->addServers( $servers );

  } // addServers


  /**
   * @test
   */
  public function getMulti() {

    $redis  = $this->createMock( $this->_cache );
    $keys   = [ 'abc', 'def', 'ghi' ];
    $values = [ 123, 456, 789 ];

    $redis->expects( $this->once() )
      ->method( 'getMultiple' )
      ->with( $keys )
      ->willReturn( $values );

    $adapter = new RedisAdapter( null, $redis );

    $this->assertEquals( array_combine( $keys, $values ), $adapter->getMulti( $keys ) );

  } // getMulti


  /**
   * @test
   */
  public function getAllKeys() {

    $redis  = $this->createMock( $this->_cache );
    $keys_a = [ 'abc', 'def', 'ghi' ];
    $keys_b = [ 'jkl', 'mno', 'pqr' ];

    $redis->expects( $this->once() )
      ->method( 'setOption' )
      ->with( \Redis::OPT_SCAN, \Redis::SCAN_RETRY );

    $redis->expects( $this->exactly( 3 ) )
      ->method( 'scan' )
      ->will( $this->onConsecutiveCalls( $keys_a, $keys_b, null ) );

    $adapter = new RedisAdapter( null, $redis );

    $this->assertEquals( $keys_a + $keys_b, $adapter->getAllKeys() );

  } // getAllKeys


  /**
   * @test
   */
  public function getStats() {

    $redis  = $this->createMock( $this->_cache );

    $redis->expects( $this->once() )
      ->method( 'info' )
      ->willReturn( [] );

    $adapter = new RedisAdapter( null, $redis );

    $this->assertInternalType( 'array', $adapter->getStats() );

  } // getStats


  /**
   * @test
   */
  public function executeFail() {

    $result = false;
    $redis  = $this->createMock( $this->_cache );

    $redis->expects( $this->once() )
      ->method( 'get' )
      ->will( $this->throwException( new \RedisException( "Deliberate!" ) ) );

    $dispatcher = new EventDispatcher();
    $adapter    = new RedisAdapter( $dispatcher, $redis );
    $hit        = false;

    $dispatcher->addListener( AdapterInterface::EVENT_QUERY_FAIL, function() use ( &$hit ) {
      $hit = true;
    } );

    $this->assertEquals( $result, $adapter->get( 'abc' ) );
    $this->assertTrue( $hit );

  } // executeFail


  /**
   * @return int
   */
  private function _getTtl( $default_ttl ) {

    return ( $default_ttl )
           ? RedisAdapter::EXPIRATION_DEFAULT
           : 123456;

  } // _getTtl

} // RedisAdapterTest
