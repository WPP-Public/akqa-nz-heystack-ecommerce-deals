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
    public function getConfig($identifier);

    public function hasConfig($identifier);
}
