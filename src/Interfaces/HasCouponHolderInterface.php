<?php

namespace Heystack\Deals\Interfaces;

/**
 * @package Heystack\Deals\Interfaces
 */
interface HasCouponHolderInterface
{
    /**
     * @return \Heystack\Deals\Interfaces\CouponHolderInterface
     */
    public function getCouponHolder();

    /**
     * @param \Heystack\Deals\Interfaces\CouponHolderInterface $couponHolder
     * @return mixed
     */
    public function setCouponHolder(CouponHolderInterface $couponHolder);
} 