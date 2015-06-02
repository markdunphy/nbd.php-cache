<?php

namespace Behance\NBD\Cache;

use Behance\NBD\Cache\Services\ConfigService;
use Behance\NBD\Cache\Exceptions\SystemRequirementException;
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
   * @param string $type  which kind of adapter to build, if omitted will choose automatically
   * @param Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *
   * @return Behance\NBD\Cache\Interfaces\AdapterInterface
   */
  public static function create( ConfigService $config, $type = null, EventDispatcherInterface $event_dispatcher = null ) {

    $servers = $config->getServers();
    $class   = self::NAMESPACE_ADAPTERS . static::_chooseAdapterType( $type ) . self::ADAPTER_SUFFIX;
    $adapter = new $class( $event_dispatcher );

    $adapter->addServers( $servers );

    return $adapter;

  } // create


  /**
   * @throws Behance\NBD\Cache\Exceptions\SystemRequirementException  when no cache backends are available
   *
   * @param string|null $type
   *
   * @return string
   */
  protected static function _chooseAdapterType( $type ) {

    // Adapter already provided? Use it
    if ( !empty( $type ) ) {
      return $type;
    }

    // Highest priority, memcached adapter
    if ( extension_loaded( 'memcached' ) ) {
      return self::TYPE_MEMCACHED;
    }

    if ( extension_loaded( 'memcache' ) ) {
      return self::TYPE_MEMCACHE;
    }

    throw new SystemRequirementException( "No cache extensions installed or available" );

  } // _chooseAdapterType

} // Factory