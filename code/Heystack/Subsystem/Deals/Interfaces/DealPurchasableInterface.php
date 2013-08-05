<?php

namespace Heystack\Subsystem\Deals\Interfaces;

use Heystack\Subsystem\Core\Identifier\Identifier;
use Heystack\Subsystem\Ecommerce\Purchasable\Interfaces\PurchasableInterface;

/**
 *
 * @copyright  Heyday
 * @author Glenn Bautista <glenn@heyday.co.nz>
 * @package Ecommerce-Deals
 */
interface DealPurchasableInterface extends PurchasableInterface
{
    /**
     * @param \Heystack\Subsystem\Core\Identifier\Identifier $dealIdentifier
     * @param int $quantity
     */
    public function setFreeQuantity(Identifier $dealIdentifier, $quantity);

    /**
     * @param \Heystack\Subsystem\Core\Identifier\Identifier $dealIdentifier
     * @param int $quantity
     */
    public function addFreeQuantity(Identifier $dealIdentifier, $quantity = 1);

    /**
     * @param Identifier $dealIdentifier
     * @param int $quantity
     */
    public function subtractFreeQuantity(Identifier $dealIdentifier, $quantity = 1);

    /**
     * @param \Heystack\Subsystem\Core\Identifier\Identifier $dealIdentifier
     * @return bool
     */
    public function hasFreeItems(Identifier $dealIdentifier = null);

    /**
     * @param \Heystack\Subsystem\Core\Identifier\Identifier $dealIdentifier
     * @return int
     */
    public function getFreeQuantity(Identifier $dealIdentifier = null);
}