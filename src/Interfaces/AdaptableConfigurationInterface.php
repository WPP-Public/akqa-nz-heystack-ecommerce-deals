<?php

namespace Heystack\Deals\Interfaces;

/**
 *
 * @copyright  Heyday
 * @author Glenn Bautista <glenn@heyday.co.nz>
 * @package Ecommerce-Deals
 */
interface AdaptableConfigurationInterface
{
    /**
     * @param string $identifier
     * @return mixed
     */
    public function getConfig($identifier);

    /**
     * @param string $identifier
     * @return mixed
     */
    public function hasConfig($identifier);
}
