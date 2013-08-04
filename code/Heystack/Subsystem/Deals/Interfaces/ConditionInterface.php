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
     *
     * @return boolean
     */
    public function met();
    /**
     * Returns a short string that describes what the condition does
     * @return string
     */
    public function getDescription();

    /**
     * Returns a string indicating the type of condition
     * @return string
     */
    public function getType();
}
