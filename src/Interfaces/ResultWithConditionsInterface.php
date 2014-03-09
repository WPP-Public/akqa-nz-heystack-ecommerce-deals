<?php

namespace Heystack\Deals\Interfaces;

/**
 * Interface ResultWithConditionsInterface
 * @package Heystack\Deals\Interfaces
 */
interface ResultWithConditionsInterface
{
    /**
     * @return array Heystack\Deals\Interfaces\ConditionInterface
     */
    public function getConditions();
}