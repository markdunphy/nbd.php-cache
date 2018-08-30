<?php

namespace Behance\NBD\Cache;

use Behance\NBD\Cache\Exceptions\ConfigRequirementException;

/**
 * Validates and holds configuration for multiple cache servers together
 */
class ConfigService
{

  /**
   * @var array holds configuration for all servers together
   */
    private $_servers = [];


  /**
   * @param array $servers
   */
    public function addServers(array $servers)
    {

        foreach ($servers as $server) {
            $this->addServer($server);
        }
    } // addServers


  /**
   * @param array $config
   */
    public function addServer(array $config)
    {

        $this->_checkParameters($config);

        $this->_servers[] = $config;
    } // addServer


  /**
   * @throws Behance\NBD\Cache\Exceptions\ConfigRequirementException
   *
   * @return array
   */
    public function getServers()
    {

        if (empty($this->_servers)) {
            throw new ConfigRequirementException(
                'No server configurations, call ->addServer() or ->addServers() first'
            );
        }

        return $this->_servers;
    } // getServers


  /**
   * @throws Behance\NBD\Cache\Exceptions\ConfigRequirementException
   *
   * @param array $config
   */
    private function _checkParameters(array $config)
    {

        $required = [
        'host',
        'port',
        ];

        $difference = array_diff($required, array_keys($config));

        if (!empty($difference)) {
            throw new ConfigRequirementException("Missing: " . implode(', ', $difference));
        }
    } // _checkParameters
} // ConfigService
