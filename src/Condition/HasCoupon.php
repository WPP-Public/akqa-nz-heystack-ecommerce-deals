<?php

namespace Heystack\Deals\Condition;


use Heystack\Deals\Interfaces\AdaptableConfigurationInterface;
use Heystack\Deals\Interfaces\ConditionAlmostMetInterface;
use Heystack\Deals\Interfaces\ConditionInterface;
use Heystack\Deals\Interfaces\CouponHolderInterface;
use Heystack\Deals\Interfaces\HasCouponHolderInterface;
use Heystack\Deals\Interfaces\HasDealHandlerInterface;
use Heystack\Deals\Traits\HasCouponHolderTrait;
use Heystack\Deals\Traits\HasDealHandlerTrait;

class HasCoupon
    implements
    ConditionInterface,
    ConditionAlmostMetInterface,
    HasCouponHolderInterface,
    HasDealHandlerInterface
{
    use HasCouponHolderTrait;
    use HasDealHandlerTrait;

    const CONDITION_TYPE = 'HasCoupon';
    const COUPON_IDENTIFIERS = 'coupon_identifiers';

    protected $couponIdentifiers = [];

    public function __construct(CouponHolderInterface $couponHolder, AdaptableConfigurationInterface $configuration)
    {
        $this->couponHolder = $couponHolder;

        if ($configuration->hasConfig(self::COUPON_IDENTIFIERS)
            && is_array(
                $configuration->getConfig(self::COUPON_IDENTIFIERS)
            )
        ) {

            $this->couponIdentifiers = $configuration->getConfig(self::COUPON_IDENTIFIERS);

        } else {

            throw new \Exception('Has Coupon Condition requires an array of coupon identifiers');

        }
    }

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
        $couponsInDeal = $this->getCouponHolder()->getCoupons($this->getDealHandler()->getIdentifier());

        return count(array_intersect($this->couponIdentifiers, array_keys($couponsInDeal))) > 0;
    }

    /**
     * Returns a short string that describes what the condition does
     * @return string
     */
    public function getDescription()
    {
        return 'The coupon holder must contain at least one of the following coupons: ' . implode(', ', $this->couponIdentifiers);
    }

    /**
     * Returns a string indicating the type of condition
     * @return string
     */
    public function getType()
    {
        return self::CONDITION_TYPE;
    }
} 