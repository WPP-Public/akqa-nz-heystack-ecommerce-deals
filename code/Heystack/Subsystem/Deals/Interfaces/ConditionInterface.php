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
     * @param array $data If present this is the data that will be used to determine whether the condition has been met
     * @return mixed
     */
    public function met(array $data = null);

    /**
     * Returns a short string that describes what the condition does
     */
    public function description();
}
