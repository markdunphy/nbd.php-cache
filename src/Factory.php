<?php

namespace Behance\NBD\Cache;

use Behance\NBD\Cache\Services\ConfigService;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class Factory {

  const NAMESPACE_ADAPTERS = '\\Behance\\NBD\\Cache\\Adapters\\';
  const ADAPTER_SUFFIX     = 'Adapter';

  const TYPE_MEMCACHE      = 'Memcache';
  const TYPE_MEMCACHED     = 'Memcached';

  /**
   * TODO: auto-choose type based on available extensions
   *
   * @param Behance\NBD\Cache\Services\ConfigService $config
   * @param string $type
   * @param Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *
   * @return Behance\NBD\Cache\Interfaces\AdapterInterface
   */
  public static function create( ConfigService $config, $type, EventDispatcherInterface $event_dispatcher = null ) {

    $servers = $config->getServers();
    $class   = self::NAMESPACE_ADAPTERS . $type . self::ADAPTER_SUFFIX;
    $adapter = new $class( $event_dispatcher );

    $adapter->addServers( $servers );

    return $adapter;

  } // create

} // Factory
