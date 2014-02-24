<?php

namespace Heystack\Deals;

use Heystack\Deals\Interfaces\AdaptableConfigurationInterface;

/**
 *
 * @copyright  Heyday
 * @author Glenn Bautista <glenn@heyday.co.nz>
 * @package Ecommerce-Deals
 */
class AdaptableConfiguration implements AdaptableConfigurationInterface
{
    /**
     * @var array
     */
    protected $config;

    /**
     * @param array $configuration
     * @throws \Exception
     */
    public function __construct(array $configuration)
    {
        if (is_array($configuration)) {
            $this->config = $configuration;
        } else {
            throw new \Exception('Configuration needs to be an array');
        }
    }

    /**
     * @param $identifier
     * @return mixed
     * @throws \Exception
     */
    public function getConfig($identifier)
    {
        if (isset($this->config[$identifier])) {
            return $this->config[$identifier];
        }

        throw new \Exception($identifier . ' configuration not found');
    }

    /**
     * @param $identifier
     * @return bool
     */
    public function hasConfig($identifier)
    {
        return isset($this->config[$identifier]);
    }
}
