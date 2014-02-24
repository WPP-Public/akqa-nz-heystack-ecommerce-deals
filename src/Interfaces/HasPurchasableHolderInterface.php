<?php

namespace Heystack\Deals\Interfaces;

use Heystack\Ecommerce\Purchasable\Interfaces\PurchasableHolderInterface;

/**
 *
 * @copyright  Heyday
 * @author Stevie Mayhew <stevie@heyday.co.nz>
 * @package Heystack\Deals\Interfaces
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