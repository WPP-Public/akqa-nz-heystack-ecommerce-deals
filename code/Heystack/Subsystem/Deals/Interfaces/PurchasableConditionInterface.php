<?php

namespace Heystack\Subsystem\Deals\Interfaces;


interface PurchasableConditionInterface extends ConditionInterface
{
    /**
     * @return \Heystack\Subsystem\Core\Identifier\Identifier
     */
    public function getPurchasableIdentifier();

}