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

use Behance\NBD\Cache\Factory;

abstract class IntegrationTest extends BaseTest {

  /**
   * @return array
   */
  public function typeProvider() {

    return [
      'Memcache' => [Factory::TYPE_MEMCACHE],
      'Memcached' => [Factory::TYPE_MEMCACHED],
      'Redis' => [Factory::TYPE_REDIS]
    ];

  }


  /**
   * 1. Determines if test is currently running in integration mode, skips otherwise
   * 2. If necessary creations
   */
  protected function setUp() {

    $types = $this->typeProvider();

    foreach ($types as $type_name_array) {

      $config = $this->_getEnvironmentConfig($type_name_array[0]);
      // IMPORTANT: only run integration tests when a cache instance is provided and working
      if (empty($config['host'])) {
        $this->markTestSkipped('Cache not available');
      }

    }


    parent::setUp();

  }

  /**
   * Destroys any cache data that was in use
   */
  protected function tearDown() {

    $memcache = $this->_getLiveAdapter(Factory::TYPE_MEMCACHE);
    $memcache->flush();

    $redis = $this->_getLiveAdapter(Factory::TYPE_REDIS);
    $redis->flush();

  }

  /**
   * @param string|null $type
   *
   * @return Behance\NBD\Dbal\AdapterInterface
   */
  protected function _getLiveAdapter($type = null) {

    $configs = [
      'master' => $this->_getEnvironmentConfig($type)
    ];

    return Factory::create($configs, $type);

  }


  /**
   * @return array
   */
  private function _getEnvironmentConfig($type) {

    // Redis vs. non-Redis configurations
    return ($type === Factory::TYPE_REDIS)
           ? ['host' => getenv('CFG_REDIS_HOST'), 'port' => getenv('CFG_REDIS_PORT')]
           : ['host' => getenv('CFG_CACHE_HOST'), 'port' => getenv('CFG_CACHE_PORT')];

  }

}
