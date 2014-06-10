<?php

namespace Heystack\Deals\Traits;


use Heystack\Deals\Interfaces\CouponHolderInterface;

trait HasCouponHolderTrait
{
    protected $couponHolder;

    /**
     * @param CouponHolderInterface $couponHolder
     */
    public function setCouponHolder(CouponHolderInterface $couponHolder)
    {
        $this->couponHolder = $couponHolder;
    }

    /**
     * @return CouponHolderInterface
     */
    public function getCouponHolder()
    {
        return $this->couponHolder;
    }
} 