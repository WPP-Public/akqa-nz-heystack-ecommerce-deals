<?php

namespace Heystack\Deals\Interfaces;


use Heystack\Core\Storage\Interfaces\ParentReferenceInterface;
use Heystack\Core\Storage\StorableInterface;

interface CouponInterface extends StorableInterface, ParentReferenceInterface
{
    /**
     * Retrieves a unique identifier for the Coupon object
     * @return \Heystack\Core\Identifier\IdentifierInterface
     */
    public function getIdentifier();

    /**
     * Retrieves the Coupon Code from the Coupon object
     * @return string
     */
    public function getCode();

    /**
     * Returns a boolean indicating the Coupon's validity
     * @return boolean
     */
    public function isValid();

    /**
     * Retrieves the associated deal's identifier
     * @return \Heystack\Core\Identifier\IdentifierInterface
     */
    public function getDealIdentifier();
} 