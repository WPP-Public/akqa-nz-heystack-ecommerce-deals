<?php

namespace Heystack\Deals\Condition;


use Heystack\Deals\Interfaces\ConditionAlmostMetInterface;
use Heystack\Deals\Interfaces\ConditionInterface;

class HasCoupon implements ConditionInterface, ConditionAlmostMetInterface
{
    const CONDITION_TYPE = 'HasCoupon';
    const COUPON_IDENTIFIERS = 'coupon_identifiers';

    protected $couponIdentifiers;

    /**
     * Check if the condition is almost met
     *
     * Almost met is when one more action completed by the user to the cart will promote this deal to being completed.
     * When a condition will complete regardless of user action, return $this->met()
     *
     * @see Heystack\Deals\Interfaces\DealHandlerInterface
     * @return boolean
     */
    public function almostMet()
    {
        return $this->met();
    }

    /**
     * Return a boolean indicating whether the condition has been met
     *
     * @return boolean
     */
    public function met()
    {

    }

    /**
     * Returns a short string that describes what the condition does
     * @return string
     */
    public function getDescription()
    {

    }

    /**
     * Returns a string indicating the type of condition
     * @return string
     */
    public function getType()
    {

    }


} 