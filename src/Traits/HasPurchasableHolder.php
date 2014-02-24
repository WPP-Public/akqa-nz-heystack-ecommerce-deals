<?php

namespace Heystack\Deals\Traits;

use Heystack\Ecommerce\Purchasable\Interfaces\PurchasableHolderInterface;

trait HasPurchasableHolder
{
    /**
     * @var \Heystack\Ecommerce\Purchasable\Interfaces\PurchasableHolderInterface
     */
    protected $purchasableHolder;

    public function setPurchasableHolder(PurchasableHolderInterface $purchasableHolder)
    {
        $this->purchasableHolder = $purchasableHolder;
    }

    /**
     * @return \Heystack\Ecommerce\Purchasable\Interfaces\PurchasableHolderInterface
     */
    public function getPurchasableHolder()
    {
        return $this->purchasableHolder;
    }
}