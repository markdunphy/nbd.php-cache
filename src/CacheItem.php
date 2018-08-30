<?php

namespace Behance\NBD\Cache;

use Psr\Cache;

class CacheItem implements Cache\CacheItemInterface {

  /** @var string */
  private $_key;

  /** @var ?Closure */
  private $_fetch;

  /** @var mixed */
  private $_value;

  /** @var bool */
  private $_hit;

  public function __construct(string $key, Closure $fetch) {
    $this->_key = $key;
    $this->_fetch = $fetch;
  }

  public function getKey() : string {
    return $this->_key;
  }

  public function get() {
    if (!$this->isHit()) {
      return;
    }

    return $this->_value;
  }

  public function isHit() : bool {
    if ($this->_fetch) {
      $this->_init();
    }

    return $this->_hit;
  }

  public function set($value) : CacheItem {
    $this->_value = $value;

    return $this;
  }

  // TODO
  public function expiresAt(?\DateTimeInterface $expiration) : CacheItem {
    return $this;
  }

  // TODO
  public function expiresAfter($time) : CacheItem {
    return $this;
  }

  private function _init() : void {
    if ($this->_fetch === null) {
      return;
    }

    [$hit, $value, $expiration_timestamp] = ($this->_fetch)();

    $this->_hit = (bool) $hit;
    $this->_value = $value;
    $this->_expiration = (int) $expiration_timestamp;
    $this->_fetch = null;
  }

}