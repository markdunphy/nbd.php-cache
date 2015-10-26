<?php

namespace Behance\NBD\Cache\Test;

use Behance\NBD\Cache\Factory;

abstract class IntegrationTest extends BaseTest {

  /**
   * @return array
   */
  public function typeProvider() {

    return [
        'Memcache'  => [ Factory::TYPE_MEMCACHE ],
        'Memcached' => [ Factory::TYPE_MEMCACHED ],
    ];

  } // typeProvider


  /**
   * 1. Determines if test is currently running in integration mode, skips otherwise
   * 2. If necessary creations
   */
  protected function setUp() {

    $config = $this->_getEnvironmentConfig();

    // IMPORTANT: only run integration tests when a cache instance is provided and working
    if ( empty( $config['host'] ) ) {
      $this->markTestSkipped( 'Cache not available' );
    }

    parent::setUp();

  } // setUp

  /**
   * Destroys any cache data that was in use
   */
  protected function tearDown() {

    $adapter = $this->_getLiveAdapter();

    $adapter->flush();

  } // tearDown

  /**
   * @param string|null $type
   *
   * @return Behance\NBD\Dbal\AdapterInterface
   */
  protected function _getLiveAdapter( $type = null ) {

    $configs = [
        'master' => $this->_getEnvironmentConfig()
    ];

    return Factory::create( $configs, $type );

  } // _getLiveAdapter


  /**
   * @return array
   */
  private function _getEnvironmentConfig() {

    return [
        'host' => getenv( 'CFG_CACHE_HOST' ),
        'port' => getenv( 'CFG_CACHE_PORT' ),
    ];

  } // _getEnvironmentConfig

} // IntegrationTest
