<?php

namespace Heystack\Deals\Traits;

use Heystack\Deals\Interfaces\CouponHolderInterface;

trait HasCouponHolderTrait
{
    /**
     * @var \Heystack\Deals\Interfaces\CouponHolderInterface
     */
    protected $couponHolder;

    /**
     * @param \Heystack\Deals\Interfaces\CouponHolderInterface $couponHolder
     * @return void
     */
    public function setCouponHolder(CouponHolderInterface $couponHolder)
    {
        $this->couponHolder = $couponHolder;
    }

    /**
     * @return \Heystack\Deals\Interfaces\CouponHolderInterface
     */
    public function getCouponHolder()
    {
        return $this->couponHolder;
    }
} 