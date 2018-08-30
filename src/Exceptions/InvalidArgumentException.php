<?php

namespace Behance\NBD\Cache\Exceptions;

use Behance\NBD\Cache\CacheException;

class InvalidArgumentException extends CacheException implements \Psr\Cache\InvalidArgumentException
{
}
