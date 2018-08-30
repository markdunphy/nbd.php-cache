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

use Behance\NBD\Cache\Test\BaseTest;

class ConfigServiceTest extends BaseTest {

  private $_host = 'cache1.com',
          $_host2 = 'cache2.com',
          $_port = 11211,
          $_config;


  protected function setUp() {

    $this->_config = new ConfigService();

  }


  /**
   * @test
   * @expectedException Behance\NBD\Cache\Exceptions\ConfigRequirementException
   */
  public function addServerMissingHost() {

    $this->_config->addServer(['port' => $this->_port]);

  }


  /**
   * @test
   * @expectedException Behance\NBD\Cache\Exceptions\ConfigRequirementException
   */
  public function addServerMissingPort() {

    $this->_config->addServer(['host' => $this->_host]);

  }


  /**
   * @test
   */
  public function addGetServer() {

    $server = [
      'host' => $this->_host,
      'port' => $this->_port
    ];

    $this->_config->addServer($server);

    // IMPORTANT: current server is one OF many server configs, will be wrapped in another array
    $this->assertEquals([$server], $this->_config->getServers());

  }


  /**
   * @test
   *
   * @expectedException Behance\NBD\Cache\Exceptions\ConfigRequirementException
   */
  public function getServersEmpty() {

    $this->_config->getServers();

  }


  /**
   * @test
   */
  public function addServers() {

    $servers = [
      [
        'host' => $this->_host,
        'port' => $this->_port
      ],
      [
        'host' => $this->_host2,
        'port' => $this->_port
      ]
    ];

    $this->_config->addServers($servers);

    $this->assertEquals($servers, $this->_config->getServers());

  }

}
