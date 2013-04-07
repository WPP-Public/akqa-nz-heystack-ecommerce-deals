<?php

namespace Heystack\Subsystem\Deals\Interfaces;

/**
 *
 * @copyright  Heyday
 * @author Glenn Bautista <glenn@heyday.co.nz>
 * @package Ecommerce-Deals
 */
interface ConditionInterface
{
    /**
     * Return a boolean indicating whether the condition has been met
     */
    public function met();

    /**
     * Returns a short string that describes what the condition does
     */
    public function description();
}
