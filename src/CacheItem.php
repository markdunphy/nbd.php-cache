<?php

namespace Behance\NBD\Cache;

use Psr\Cache;

class CacheItem implements Cache\CacheItemInterface
{
    const DEFAULT_VALUE = [false, null, null];

    /** @var string */
    private $_key;

    /** @var ?Closure */
    private $_fetch;

    /** @var mixed */
    private $_value;

    /** @var bool */
    private $_hit;

    /** @var int */
    private $_ttl = AdapterInterface::EXPIRATION_DEFAULT;

    public function __construct(string $key, \Closure $fetch)
    {
        $this->_key = $key;
        $this->_fetch = $fetch;
    }

    public function getKey() : string
    {
        return $this->_key;
    }

    public function get()
    {
        $this->_init();
        return $this->_value;
    }

    public function isHit() : bool
    {
        $this->_init();
        return $this->_hit;
    }

    public function set($value) : CacheItem
    {
        $this->_value = $value;

        return $this;
    }


    public function expiresAt($expiration) : CacheItem
    {
        if (null === $expiration || \is_int($expiration)) {
            $this->_ttl = null;
        } elseif (\is_int($expiration)) {
            $this->_ttl = $expiration - time();
        } elseif ($expiration instanceof \DateTimeInterface) {
            $this->_ttl = $expiration->getTimestamp() - time();
        } else {
            throw new Exceptions\InvalidArgumentException(
                'Expiration must be an integer, \DateTimeInterface, or null'
            );
        }

        return $this;
    }

    public function expiresAfter($time) : CacheItem
    {
        if (null === $time || \is_int($time)) {
            $this->_ttl = $time;
        } elseif ($time instanceof \DateInterval) {
            $now = new \DateTimeImmutable();
            $expiry = $now->add($time);
            $this->_ttl = $expiry->getTimestamp() - $now->getTimestamp();
        } else {
            throw new Exceptions\InvalidArgumentException(
                'Time must be an integer, \DateInterval, or null'
            );
        }

        return $this;
    }

    public function getTtl() : ?int
    {
        $this->_init();
        return $this->_ttl;
    }

    public function getExpiresAt() : ?int
    {
        if (null === $this->getTtl()) {
            return null;
        }

        return $this->getTtl() + time();
    }

    private function _init() : void
    {
        if ($this->_fetch === null) {
            return;
        }

        [$hit, $value, $expiration_timestamp] = ($this->_fetch)();

        $this->_hit = (bool) $hit;
        $this->_value = $value;
        $this->expiresAt($expiration_timestamp);
        $this->_fetch = null;
    }
}
