<?php

namespace Heystack\Subsystem\Deals;

use Heystack\Subsystem\Deals\Interfaces\AdaptableConfigurationInterface;

/**
 *
 * @copyright  Heyday
 * @author Glenn Bautista <glenn@heyday.co.nz>
 * @package Ecommerce-Deals
 */
class AdaptableConfiguration implements AdaptableConfigurationInterface
{
    protected $config;

    public function __construct(Array $configuration)
    {
        if (is_array($configuration) && $this->is_assoc($configuration)) {
            $this->config = $configuration;
        } else {
            throw new \Exception('Configuration needs to be an associative array');
        }
    }

    protected function is_assoc($array)
    {
        return (bool) count(array_filter(array_keys($array), 'is_string'));
    }

    public function getConfig($identifier)
    {
        if (isset($this->config[$identifier])) {
            return $this->config[$identifier];
        }

        throw new \Exception($identifier . ' configuration not found');
    }

    public function hasConfig($identifier)
    {
        return isset($this->config[$identifier]);
    }

}
