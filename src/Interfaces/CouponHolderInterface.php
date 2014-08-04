<?php

namespace Heystack\Deals\Interfaces;

use Heystack\Core\Identifier\IdentifierInterface;

interface CouponHolderInterface
{
    /**
     * Retrieves a unique identifier for the CouponHolder service
     * @return \Heystack\Core\Identifier\IdentifierInterface
     */
    public function getIdentifier();

    /**
     * Adds a Coupon to the CouponHolder
     * @param \Heystack\Deals\Interfaces\CouponInterface $coupon
     * @return void
     */
    public function addCoupon(CouponInterface $coupon);

    /**
     * Removes a Coupon based on the identifier
     * @param \Heystack\Core\Identifier\IdentifierInterface $identifier
     * @return void
     */
    public function removeCoupon(IdentifierInterface $identifier);

    /**
     * Retrieves a Coupon based on the identifier
     * @param \Heystack\Core\Identifier\IdentifierInterface $identifier
     * @return \Heystack\Deals\Interfaces\CouponInterface|null
     */
    public function getCoupon(IdentifierInterface $identifier);

    /**
     * Retrieves all the Coupons held by the CouponHolder. If a deal identifier is
     * passed through as an argument only coupons that are associated with that deal
     * is retrieved.
     * @param IdentifierInterface $dealIdentifier
     * @return mixed
     */
    public function getCoupons(IdentifierInterface $dealIdentifier = null);

    /**
     * Sets multiple Coupons on the CouponHolder
     * @param array $coupons
     * @return void
     */
    public function setCoupons(array $coupons);

} 