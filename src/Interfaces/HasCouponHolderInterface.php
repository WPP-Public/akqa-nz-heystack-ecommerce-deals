<?php

namespace Heystack\Deals\Interfaces;


interface HasCouponHolderInterface
{
    public function getCouponHolder();

    public function setCouponHolder(CouponHolderInterface $couponHolder);
} 