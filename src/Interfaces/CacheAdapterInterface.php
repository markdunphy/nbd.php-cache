<?php

namespace Behance\NBD\Dbal\Interfaces;

interface CacheAdapterInterface {

  const EXPIRATION_DEFAULT = 1209600; // Two weeks in seconds


  /**
   * @param string $host
   * @param int    $port
   */
  public function addServer( $host, $port );

  /**
   * @param string $key
   *
   * @return mixed|bool  false when key is missing
   */
  public function get( $key );


  /**
   * @param array $keys
   *
   * @return array  key indexes present, but with false as value when not available
   */
  public function getMulti( array $keys );


  /**
   * @param string $key
   * @param mixed  $value
   * @param int    $ttl
   *
   * @return bool
   */
  public function set( $key, $value, $ttl = self::EXPIRATION_DEFAULT );


  /**
   * @param string $key
   * @param mixed  $value
   * @param int    $ttl
   *
   * @return bool
   */
  public function add( $key, $value, $ttl = self::EXPIRATION_DEFAULT );

  /**
   * @param string $key
   * @param mixed  $value
   * @param int    $ttl
   *
   * @return bool
   */
  public function replace( $key, $value, $ttl = self::EXPIRATION_DEFAULT );


  /**
   * @param string $key
   * @param int    $value
   *
   * @return bool
   */
  public function increment( $key, $value = 1 );


  /**
   * @param string $key
   * @param int    $value
   *
   * @return bool
   */
  public function decrement( $key, $value = 1 );


  /**
   * @param string $key
   *
   * @return bool
   */
  public function delete( $key );


  /**
   * @param array $keys
   *
   * @return bool
   */
  public function deleteMulti( array $keys );


  /**
   * Disconnects from server(s)
   */
  public function close();

} // CacheAdapterInterface
