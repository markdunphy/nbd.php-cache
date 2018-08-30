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


namespace Behance\NBD\Cache;

use Behance\NBD\Cache\ConfigService;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class Factory {

  const NAMESPACE_ADAPTERS = '\\Behance\\NBD\\Cache\\Adapters\\';
  const ADAPTER_SUFFIX = 'Adapter';

  const TYPE_MEMCACHE = 'Memcache';
  const TYPE_MEMCACHED = 'Memcached';
  const TYPE_REDIS = 'Redis';

  /**
   * @var string[] list of valid adapter types, in preferred priority order
   */
  protected static $_ADAPTER_TYPES = [
    self::TYPE_MEMCACHED,
    self::TYPE_MEMCACHE,
    self::TYPE_REDIS
  ];


  /**
   * @var string
   */
  private $_type;


  /**
   * @param array[] $config  each host + port pair designates a server in a cache pool
   * @param string  $type    which kind of adapter to build, if omitted will be chosen automatically (memcache vs memcached)
   * @param Behance\NBD\Cache\ConfigService $config
   * @param Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *
   * @return Behance\NBD\Cache\Interfaces\AdapterInterface
   */
  public static function create(array $config, $type = null, ConfigService $config_service = null, EventDispatcherInterface $event_dispatcher = null) {

    $config_service = $config_service ?: new ConfigService();

    foreach ($config as $server) {
      $config_service->addServer($server);
    }

    $servers = $config_service->getServers();
    $class = self::NAMESPACE_ADAPTERS . (new static($type))->getType() . self::ADAPTER_SUFFIX;
    $adapter = new $class($event_dispatcher);

    $adapter->addServers($servers);

    return $adapter;

  }


  /**
   * NOTE: Only use ::create() directly, this is only public to provide simple test interface
   *
   * @throws Behance\NBD\Cache\CacheException when an invalid type is selected
   *
   * @param string|null $type
   */
  public function __construct($type = null) {

    // When type is specified manually, determine its validity + availability
    if (!empty($type)) {

      if (!in_array($type, self::$_ADAPTER_TYPES)) {
        throw new CacheException("Invalid cache adapter: " . var_export($type, 1));
      }

      if (!$this->_isExtensionLoaded($this->_getExtensionName($type))) {
        throw new Exceptions\SystemRequirementException("Selected cache adapter not available: " . var_export($type, 1));
      }

      $this->_type = $type;
      return;

    }

    // Otherwise, automatically select a type from the prioritized list
    foreach (self::$_ADAPTER_TYPES as $adapter_type) {

      if ($this->_isExtensionLoaded($this->_getExtensionName($adapter_type))) {
        $this->_type = $adapter_type;
        break;
      }

    }

    if (empty($this->_type)) {
      throw new Exceptions\SystemRequirementException("No cache extensions installed or available");
    }

  }


  /**
   * @return string
   */
  public function getType() {

    return $this->_type;

  }


  /**
   * @param string $type
   *
   * @return bool
   */
  protected function _isExtensionLoaded($type) {

    return extension_loaded($type);

  }


  /**
   * @param string $type
   *
   * @return string
   */
  private function _getExtensionName($type) {

    return mb_strtolower($type);

  }

}
