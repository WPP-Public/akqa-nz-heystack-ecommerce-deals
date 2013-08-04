<?php

namespace Heystack\Subsystem\Deals\Traits;

use Heystack\Subsystem\Ecommerce\Purchasable\Interfaces\PurchasableHolderInterface;

trait HasPurchasableHolder
{
    /**
     * @var \Heystack\Subsystem\Ecommerce\Purchasable\Interfaces\PurchasableHolderInterface
     */
    protected $purchasableHolder;

    public function setPurchasableHolder(PurchasableHolderInterface $purchasableHolder)
    {
        $this->purchasableHolder = $purchasableHolder;
    }

    /**
     * @return \Heystack\Subsystem\Ecommerce\Purchasable\Interfaces\PurchasableHolderInterface
     */
    public function getPurchasableHolder()
    {
        return $this->purchasableHolder;
    }
}