<?php

namespace Heystack\Subsystem\Deals\Interfaces;


interface ResultWithConditionsInterface {
    /**
     * @return array Heystack\Subsystem\Deals\Interfaces\ConditionInterface
     */
    public function getConditions();

}