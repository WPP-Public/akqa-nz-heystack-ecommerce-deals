<?php

namespace Heystack\Subsystem\Deals\Interfaces;

use Heystack\Subsystem\Ecommerce\Purchasable\Interfaces\PurchasableHolderInterface;

/**
 * Class HasPurchasableHolderInterface
 * @package Heystack\Subsystem\Deals\Interfaces
 */
interface HasPurchasableHolderInterface
{
    /**
     * @return PurchasableHolderInterface
     */
    public function getPurchasableHolder();

    /**
     * @param PurchasableHolderInterface $purchasableHolder
     * @return mixed
     */
    public function setPurchasableHolder(PurchasableHolderInterface $purchasableHolder);
}