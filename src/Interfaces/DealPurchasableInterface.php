<?php

namespace Heystack\Deals\Interfaces;

use Heystack\Core\Identifier\IdentifierInterface;
use Heystack\Ecommerce\Purchasable\Interfaces\PurchasableInterface;

/**
 *
 * @copyright  Heyday
 * @author Glenn Bautista <glenn@heyday.co.nz>
 * @package Ecommerce-Deals
 */
interface DealPurchasableInterface extends PurchasableInterface
{
    /**
     * @param \Heystack\Core\Identifier\IdentifierInterface $dealIdentifier
     * @param int $quantity
     */
    public function setFreeQuantity(IdentifierInterface $dealIdentifier, $quantity);

    /**
     * @param \Heystack\Core\Identifier\IdentifierInterface $dealIdentifier
     * @param int $quantity
     */
    public function addFreeQuantity(IdentifierInterface $dealIdentifier, $quantity = 1);

    /**
     * @param \Heystack\Core\Identifier\IdentifierInterface $dealIdentifier
     * @param int $quantity
     */
    public function subtractFreeQuantity(IdentifierInterface $dealIdentifier, $quantity = 1);

    /**
     * @param \Heystack\Core\Identifier\IdentifierInterface $dealIdentifier
     * @return bool
     */
    public function hasFreeItems(IdentifierInterface $dealIdentifier = null);

    /**
     * @param \Heystack\Core\Identifier\IdentifierInterface $dealIdentifier
     * @return int
     */
    public function getFreeQuantity(IdentifierInterface $dealIdentifier = null);
}