<?php

namespace Behance\NBD\Cache\Factories;

use Behance\NBD\Cache\Services\ConfigService;

class ConnectionFactory {

  const NAMESPACE_ADAPTERS = '\\Behance\\NBD\\Cache\\Adapters\\';

  /**
   * @param Behance\NBD\Cache\Services\ConfigService $config
   * @param string $type
   *
   * @return AdapterInterface
   */
  public static function createConnection( ConfigService $config, $type ) {

    $servers = $config->getServers();
    $class   = self::NAMESPACE_ADAPTERS . $type;
    $adapter = new $class();

    foreach ( $servers as $server ) {
      $adapter->addServer( $server['host'], $server['port'] );
    }

    return $adapter;

  } // createConnection

} // ConnectionFactory
