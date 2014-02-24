<?php

namespace Heystack\Deals\Interfaces;


interface ResultWithConditionsInterface {
    /**
     * @return array Heystack\Deals\Interfaces\ConditionInterface
     */
    public function getConditions();

}