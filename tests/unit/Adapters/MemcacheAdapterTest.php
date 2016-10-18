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

    $memcache = $this->getMockBuilder( $this->_cache )
                     ->setMethods( [ $method ] )
                     ->getMock();

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
        'flush'     => [ 'flush',     [],                  true ],
        'close'     => [ 'close',     [],                  true ],
        'getStats'  => [ 'getStats',  [],                  true ],
    ];

  } // passthruProvider


  /**
   * @test
   */
  public function getMulti() {

    $keys  = [ 'abcdef', 'defghi' ];
    $value = [ 'abcdef' => 12345, 'defghi' => 67890 ];

    // IMPORTANT: this adapter takes multiple argument types through `get`
    $memcache = $this->getMockBuilder( $this->_cache )
                     ->setMethods( [ 'get' ] )
                     ->getMock();

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
    $memcache = $this->createMock( $this->_cache );

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
    $memcache = $this->getMockBuilder( $this->_cache )
                     ->setMethods( [ 'delete' ] )
                     ->getMock();

    $memcache->expects( $this->exactly( count( $keys ) ) )
      ->method( 'delete' );

    $adapter = new MemcacheAdapter( null, $memcache );

    $adapter->deleteMulti( $keys );

  } // deleteMulti


  /**
   * @test
   */
  public function addServer() {

    $memcache = $this->getMockBuilder( $this->_cache )
                     ->setMethods( [ 'addServer' ] )
                     ->getMock();

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

    $mock = $this->getMockBuilder( $this->_target )
                 ->setMethods( [ 'addServer' ] )
                 ->getMock();

    $mock->expects( $this->exactly( count( $this->_server_config ) ) )
      ->method( 'addServer' );

    $mock->addServers( $this->_server_config );

  } // addServers


  /**
   * @test
   * @dataProvider extendedStatsProvider
   */
  public function getAllKeys( $results, $slabs, $cachedumps = [] ) {

    $callback = ( function( $key, $slab_id = null ) use ( $slabs, $cachedumps ) {

      if ( $key === MemcacheAdapter::STAT_KEY_SLABS ) {
        return $slabs;
      }

      if ( $key === MemcacheAdapter::STAT_KEY_DUMP ) {
        return $cachedumps[ $slab_id ];
      }

    } );


    $memcache = $this->getMockBuilder( $this->_cache )
                     ->setMethods( [ 'getExtendedStats' ] )
                     ->getMock();

    $memcache->expects( $this->atLeastOnce() )
      ->method( 'getExtendedStats' )
      ->will( $this->returnCallback( $callback ) );

    $adapter = new MemcacheAdapter( null, $memcache );

    $this->assertEquals( $results, $adapter->getAllKeys() );

  } // getAllKeys


  /**
   * @return array
   */
  public function extendedStatsProvider() {

    $server1  = 'cache1.com:11211';
    $server2  = 'cache2.com:11212';

    $slab_id1 = 1234;
    $slab_id2 = 5678;
    $slab_id3 = 9012;
    $slab_id4 = 3456;

    $key1  = 'key1';
    $key2  = 'key2';
    $key3  = 'key3';
    $key4  = 'key4';
    $key5  = 'key5';
    $key6  = 'key6';
    $key7  = 'key7';
    $key8  = 'key8';
    $key9  = 'key9';
    $key10 = 'key10';
    $key11 = 'key11';
    $key12 = 'key12';
    $key13 = 'key13';
    $key14 = 'key14';
    $key15 = 'key15';
    $key16 = 'key16';

    $slabs = [
        $server1 => [
            $slab_id1 => [ 'chunk_size' => 96,   'chunks_per_page' => 10922, 'total_pages' => 1 ],
            $slab_id2 => [ 'chunk_size' => 200,  'chunks_per_page' => 40923, 'total_pages' => 3 ]
        ],
        $server2 => [
            $slab_id3 => [ 'chunk_size' => 2196, 'chunks_per_page' => 51922, 'total_pages' => 6 ],
            $slab_id4 => [ 'chunk_size' => 4191, 'chunks_per_page' => 122,   'total_pages' => 12 ]
        ]
    ];

    $cachedump1 = [
        $server1 => [ $key1 => [ 1, 2 ], $key2 => [ 3, 4 ] ],
        $server2 => [ $key3 => [ 5, 6 ], $key4 => [ 7, 8 ] ]
    ];

    $cachedump2 = [
        $server1 => [ $key5 => [ 9,  10 ], $key6 => [ 11, 12 ] ],
        $server2 => [ $key7 => [ 14, 15 ], $key8 => [ 15, 16 ] ]
    ];

    $cachedump3 = [
        $server1 => [ $key9  => [ 1, 2 ], $key10 => [ 3, 4 ] ],
        $server2 => [ $key11 => [ 5, 6 ], $key12 => [ 7, 8 ] ]
    ];

    $cachedump4 = [
        $server1 => [ $key13 => [ 1, 2 ], $key14 => [ 3, 4 ] ],
        $server2 => [ $key15 => [ 5, 6 ], $key16 => [ 7, 8 ] ]
    ];

    $cachedumps  = [
        $slab_id1 => $cachedump1,
        $slab_id2 => $cachedump2,
        $slab_id3 => $cachedump3,
        $slab_id4 => $cachedump4
    ];

    $key_results = [ $key1, $key2, $key3, $key4, $key5, $key6, $key7, $key8, $key9, $key10, $key11, $key12, $key13, $key14, $key15, $key16 ];

    $partial_slabs = [
        $server1 => [
            $slab_id1 => [ 'chunk_size' => 96,   'chunks_per_page' => 10922, 'total_pages' => 1 ],
        ]
    ];

    $partial_cachedump1 = [
        $server1 => [ $key1 => [ 1, 2 ], $key2 => 'not-an-array', $key3 => [ 5, 6 ], $key4 => [ 7, 8 ] ],
    ];

    $partial_results    = [ $key1, $key3, $key4 ];
    $partial_cachedumps = [
        $slab_id1 => $partial_cachedump1
    ];

    return [
        'Empty Slabs'  => [ [], [] ],
        'Full Slabs'   => [ $key_results, $slabs, $cachedumps ],
        'Partial Slab' => [ $partial_results, $partial_slabs, $partial_cachedumps ]
    ];

  } // extendedStatsProvider

} // MemcacheAdapterTest
