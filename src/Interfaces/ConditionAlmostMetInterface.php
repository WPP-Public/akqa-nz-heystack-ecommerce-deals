<?php

namespace Heystack\Deals\Interfaces;

/**
 * @copyright  Heyday
 * @author Stevie Mayhew <stevie@heyday.co.nz>
 * @package Heystack\Deals\Interfaces
 */
interface ConditionAlmostMetInterface
{
    /**
     * Check if the condition is almost met
     *
     * Almost met is when one more action completed by the user to the cart will promote this deal to being completed.
     * When a condition will complete regardless of user action, return $this->met()
     *
     * @see Heystack\Deals\Interfaces\DealHandlerInterface
     * @return boolean
     */
    public function almostMet();
}